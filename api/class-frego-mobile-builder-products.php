<?php

/**
 * The public-facing functionality of the plugin.
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class Mobile_Builder_Products
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
        $this->version     = $version;
    }

    /**
     * Registers a REST API route.
     */
    public function add_api_routes()
    {
        $product = new WC_REST_Products_Controller();

        register_rest_route(
            'wc/v3',
            'min-max-prices',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_min_max_prices' ],
                'permission_callback' => [ $product, 'get_items_permissions_check' ],
                'args'                => array(),

            ]
        );

        register_rest_route(
            'wc/v3',
            'term-product-counts',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_filtered_term_product_counts' ],
                'permission_callback' => [ $product, 'get_items_permissions_check' ],
                'args'                => array(),

            ]
        );
    }

    /**
     * @param WP_REST_Request $request Request object.
     */
    public function get_min_max_prices($request)
    {
        global $wpdb;

        $tax_query = [];

        if (isset($request['category']) && $request['category']) {
            $tax_query[] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'cat_id',
                    'terms'    => [ $request['category'] ],
                ],
            ];
        }

        $meta_query = [];

        $meta_query = new WP_Meta_Query($meta_query);
        $tax_query  = new WP_Tax_Query($tax_query);

        $meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
        $tax_query_sql  = $tax_query->get_sql($wpdb->posts, 'ID');

        $sql =
            "
			SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN (
				SELECT ID FROM {$wpdb->posts}
				" .
            $tax_query_sql['join'] .
            $meta_query_sql['join'] .
            "
				WHERE {$wpdb->posts}.post_type IN ('" .
            implode(
                "','",
                array_map(
                    'esc_sql',
                    apply_filters(
                        'woocommerce_price_filter_post_type',
                        [
                            'product',
                        ]
                    )
                )
            ) .
            "')
				AND {$wpdb->posts}.post_status = 'publish'
				" .
            $tax_query_sql['where'] .
            $meta_query_sql['where'] .
            '
			)';

        $sql = apply_filters(
            'woocommerce_price_filter_sql',
            $sql,
            $meta_query_sql,
            $tax_query_sql
        );

        return $wpdb->get_row($sql); // WPCS: unprepared SQL ok.
    }

    /**
     * @param WP_REST_Request $request Request object.
     */
    public function get_filtered_term_product_counts($request)
    {
        global $wpdb;

        $term_ids = wp_list_pluck(
            get_terms($request['taxonomy'], [ 'hide_empty' => '1' ]),
            'term_id'
        );

        $tax_query  = [];
        $meta_query = [];

        if (isset($request['attrs']) && $request['attrs']) {
            $attrs = $request['attrs'];
            foreach ($attrs as $attr) {
                $tax_query[] = [
                    'taxonomy' => $attr['taxonomy'],
                    'field'    => $attr['field'],
                    'terms'    => $attr['terms'],
                ];
            }
        }

        if (isset($request['category']) && $request['category']) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'cat_id',
                'terms'    => [ $request['category'] ],
            ];
        }

        $meta_query     = new WP_Meta_Query($meta_query);
        $tax_query      = new WP_Tax_Query($tax_query);
        $meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
        $tax_query_sql  = $tax_query->get_sql($wpdb->posts, 'ID');

        // Generate query.
        $query           = [];
        $query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) as term_count, terms.term_id as term_count_id";
        $query['from']   = "FROM {$wpdb->posts}";
        $query['join']   =
            "
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			" .
            $tax_query_sql['join'] .
            $meta_query_sql['join'];

        $query['where'] =
            "
			WHERE {$wpdb->posts}.post_type IN ( 'product' )
			AND {$wpdb->posts}.post_status = 'publish'" .
            $tax_query_sql['where'] .
            $meta_query_sql['where'] .
            'AND terms.term_id IN (' .
            implode(',', array_map('absint', $term_ids)) .
            ')';

        $query['group_by'] = 'GROUP BY terms.term_id';
        $query             = apply_filters(
            'woocommerce_get_filtered_term_product_counts_query',
            $query
        );
        $query             = implode(' ', $query);

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function woocommerce_rest_product_object_query($args, $request)
    {
        $tax_query = [];

        if (isset($request['attrs']) && $request['attrs']) {
            $attrs = $request['attrs'];
            foreach ($attrs as $attr) {
                $tax_query[] = [
                    'taxonomy' => $attr['taxonomy'],
                    'field'    => $attr['field'],
                    'terms'    => $attr['terms'],
                ];
            }
            $args['tax_query'] = $tax_query;
        }

        return $args;
    }
}