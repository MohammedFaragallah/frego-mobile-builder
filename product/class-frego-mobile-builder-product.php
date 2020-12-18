<?php

/**
 * The product-facing functionality of the plugin.
 */

/**
 * The product-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the product-facing stylesheet and JavaScript.
 */
class Mobile_Builder_Product
{




    /**
     * The ID of this plugin.
     *
     * @var string the ID of this plugin
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string the current version of this plugin
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name the name of the plugin
     * @param string $version     the version of this plugin
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version      = $version;
    }

    /**
     * Registers a REST API route.
     */
    public function add_api_routes()
    {
        $namespace = $this->plugin_name . '/v' . intval($this->version);

        $products = new WC_REST_Products_Controller();

        register_rest_route(
            'wc/v3',
            'products-distance',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_items' ],
                'permission_callback' => [ $products, 'get_items_permissions_check' ],
                'args'                => array(),

            ]
        );

        register_rest_route(
            $namespace,
            'variable/(?P<product_id>[a-zA-Z0-9-]+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'product_get_all_variable_data' ],
                'permission_callback' => '__return_true',
                'args'                => array(),

            ]
        );
    }

    /**
     * Get list products variable.
     *
     * @param $request
     *
     * @return array|WP_Error|WP_REST_Response
     */
    public function product_get_all_variable_data($request)
    {
        $product_id                = $request->get_param('product_id');
        $handle                     = new WC_Product_Variable($product_id);
        $variation_attributes       = $handle->get_variation_attributes();
        $variation_attributes_data  = [];
        $variation_attributes_label = [];
        foreach ($variation_attributes as $key => $attribute) {
            $variation_attributes_result[ 'attribute_' . sanitize_title($key) ] = $attribute;
            $variation_attributes_label[ 'attribute_' . sanitize_title($key) ]  = $key;
        }

        return [
            'variation_attributes_label' => $variation_attributes_label,
            'variation_attributes'       => $variation_attributes_result,
            'available_variations'       => $handle->get_available_variations(),
        ];
    }

    /**
     * Get products items.
     *
     * @param $request
     *
     * @return array|WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        global $wpdb;

        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');

        $productsClass = new WC_REST_Products_Controller();
        $response      = $productsClass->get_items($request);

        if ($lat && $lng) {
            $ids = [];
            foreach ($response->data as $key => $value) {
                $ids[] = $value['id'];
            }

            // Get all locations
            $table_name    = $wpdb->prefix . 'gmw_locations';
            $query         =
                "SELECT * FROM {$table_name} WHERE object_id IN (" .
                implode(',', $ids) .
                ')';
            $gmw_locations = $wpdb->get_results($query, OBJECT);

            // Calculator the distance
            $origins = [];
            foreach ($gmw_locations as $key => $value) {
                $origins[] = $value->latitude . ',' . $value->longitude;
            }

            $origin_string       = implode('|', $origins);
            $destinations_string = "{$lat},{$lng}";
            $key                 = MOBILE_BUILDER_GOOGLE_API_KEY;

            $distance_matrix = mobile_builder_distance_matrix(
                $origin_string,
                $destinations_string,
                $key
            );

            // map distance matrix to product
            $data = [];
            foreach ($response->data as $key => $item) {
                $index                   = array_search(
                    $item['id'],
                    array_column($gmw_locations, 'object_id')
                );
                $item['distance_matrix'] = $distance_matrix[ $index ];
                $data[]                  = $item;
            }

            // $data[] = array(
            // 'origin_string' => $origin_string,
            // 'destinations_string' => $destinations_string,
            // 'ids' => $ids,
            // 'gmw_locations' => $gmw_locations
            // );

            $response->data = $data;
        }

        return $response;
    }

    /**
     * Force currency for mobile checkout.
     *
     * @param mixed $client_currency
     */
    public function mbd_wcml_client_currency($client_currency)
    {
        if (
            isset($_GET['mobile']) &&
            1 == $_GET['mobile'] &&
            isset($_GET['currency'])
        ) {
            $client_currency = $_GET['currency'];
        }

        return $client_currency;
    }

    public function add_value_pa_color($response)
    {
        $term_id                 = $response->data['id'];
        $response->data['value'] = sanitize_hex_color(
            get_term_meta($term_id, 'product_attribute_color', true)
        );

        return $response;
    }

    public function add_value_pa_image($response)
    {
        $term_id       = $response->data['id'];
        $attachment_id = absint(
            get_term_meta($term_id, 'product_attribute_image', true)
        );
        $image_size    = function_exists('woo_variation_swatches') ? woo_variation_swatches()->get_optio('attribute_image_size') : 'thumbnail';

        $response->data['value'] = wp_get_attachment_image_url(
            $attachment_id,
            apply_filters('wvs_product_attribute_image_size', $image_size)
        );

        return $response;
    }

    /**
     * @param $response
     * @param mixed    $object
     * @param mixed    $request
     *
     * @return mixed
     */
    public function custom_change_product_response($response, $object, $request)
    {
        // echo $request->get_param('lng');
        // echo $request->get_param('lat'); die;

        $type = $response->data['type'];

        if ('variable' == $type) {
            $price_min                   = $object->get_variation_price();
            $price_max                   = $object->get_variation_price('max');
            $response->data['price_min'] = $price_min;
            $response->data['price_max'] = $price_max;
        }

        global $woocommerce_wpml;
        if (
            ! empty($woocommerce_wpml->multi_currency) &&
            ! empty($woocommerce_wpml->settings['currencies_order'])
        ) {
            $price = $response->data['price'];

            if ('grouped' == $type || 'variable' == $type) {
                foreach (
                    $woocommerce_wpml->settings['currencies_order']
                    as $currency
                ) {
                    if ($currency != get_option('woocommerce_currency')) {
                        $response->data['from-multi-currency-prices'][ $currency ]['price'] = $woocommerce_wpml->multi_currency->prices->raw_price_filter(
                            $price,
                            $currency
                        );
                    }
                }
            }
        }

        return $response;
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    public function custom_change_product_cat($response)
    {
        $response->data['name'] = wp_specialchars_decode(
            $response->data['name']
        );

        return $response;
    }

    /**
     * @param $title
     *
     * @return string
     */
    public function custom_the_title($title)
    {
        return wp_specialchars_decode($title);
    }

    /**
     * @param $product_data
     *
     * @return mixed
     */
    public function custom_woocommerce_rest_prepare_product_variation_object(
        $product_data
    ) {
        global $woocommerce_wpml;

        if (
            ! empty($woocommerce_wpml->multi_currency) &&
            ! empty($woocommerce_wpml->settings['currencies_order'])
        ) {
            $product_data->data['multi-currency-prices'] = [];

            $custom_prices_on = get_post_meta(
                $product_data->data['id'],
                '_wcml_custom_prices_status',
                true
            );

            foreach (
                $woocommerce_wpml->settings['currencies_order']
                as $currency
            ) {
                if ($currency != get_option('woocommerce_currency')) {
                    if ($custom_prices_on) {
                        $custom_prices = (array) $woocommerce_wpml->multi_currency->custom_prices->get_product_custom_prices(
                            $product_data->data['id'],
                            $currency
                        );
                        foreach ($custom_prices as $key => $price) {
                            $product_data->data['multi-currency-prices'][ $currency ][ preg_replace('#^_#', '', $key) ] = $price;
                        }
                    } else {
                        $product_data->data['multi-currency-prices'][ $currency ]['regular_price'] = $woocommerce_wpml->multi_currency->prices->raw_price_filter(
                            $product_data->data['regular_price'],
                            $currency
                        );
                        if (! empty($product_data->data['sale_price'])) {
                            $product_data->data['multi-currency-prices'][ $currency ]['sale_price'] = $woocommerce_wpml->multi_currency->prices->raw_price_filter(
                                $product_data->data['sale_price'],
                                $currency
                            );
                        }
                    }
                }
            }
        }

        return $product_data;
    }

    /**
     * Pre product attribute.
     *
     * @param $response
     * @param $item
     * @param $request
     *
     * @return mixed
     */
    public function custom_woocommerce_rest_prepare_product_attribute(
        $response,
        $item,
        $request
    ) {
        $options = get_terms(
            [
                'taxonomy'   => wc_attribute_taxonomy_name($item->attribute_name),
                'hide_empty' => false,
            ]
        );

        foreach ($options as $key => $term) {
            if ('color' == $item->attribute_type) {
                $term->value = sanitize_hex_color(
                    get_term_meta(
                        $term->term_id,
                        'product_attribute_color',
                        true
                    )
                );
            }

            if ('image' == $item->attribute_type) {
                $attachment_id = absint(
                    get_term_meta(
                        $term->term_id,
                        'product_attribute_image',
                        true
                    )
                );
                $image_size    = function_exists('woo_variation_swatches')
                    ? woo_variation_swatches()->get_option(
                        'attribute_image_size'
                    )
                    : 'thumbnail';

                $term->value = wp_get_attachment_image_url(
                    $attachment_id,
                    apply_filters(
                        'wvs_product_attribute_image_size',
                        $image_size
                    )
                );
            }

            $options[ $key ] = $term;
        }

        $response->data['options'] = $options;

        return $response;
    }

    /**
     * @param $response
     * @param $post
     * @param $request
     *
     * @return mixed
     */
    public function prepare_product_images($response, $post, $request)
    {
        global $_wp_additional_image_sizes;

        if (empty($response->data)) {
            return $response;
        }

        foreach ($response->data['images'] as $key => $image) {
            $image_urls = [];
            foreach ($_wp_additional_image_sizes as $size => $value) {
                $image_info                                = wp_get_attachment_image_src($image['id'], $size);
                $response->data['images'][ $key ][ $size ] = $image_info[0];
            }
        }

        return $response;
    }

    /**
     * @param $response
     * @param $post
     * @param $request
     *
     * @return mixed
     */
    public function prepare_product_variation_images($response, $post, $request)
    {
        global $_wp_additional_image_sizes;

        if (empty($response->data) || empty($response->data['image'])) {
            return $response;
        }

        foreach ($_wp_additional_image_sizes as $size => $value) {
            $image_info                       = wp_get_attachment_image_src(
                $response->data['image']['id'],
                $size
            );
            $response->data['image'][ $size ] = $image_info[0];
        }

        return $response;
    }
}