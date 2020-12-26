<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartItemSchema;

add_filter('woocommerce_store_api_disable_nonce_check', '__return_true');

add_filter(
    'jwt_auth_whitelist',
    function ($endpoints) {
        return ['/wp-json/*', '/wp-admin/*', '/*'];
    }
);

add_filter('woocommerce_rest_prepare_product_cat', 'prepare_product_cat_response', 10, 3);

/**
 * @param WP_REST_Response $response
 * @param WC_Webhook $object
 * @param WP_REST_Request $request
 */
function prepare_product_cat_response($response, $object, $request)
{

    $data = $response->get_data();

    $content = get_term_meta($object->term_id, 'cat_meta');

    $data['top_content'] = do_shortcode($content[0]['cat_header']);
    $data['bottom_content'] = do_shortcode($content[0]['cat_footer']);

    $response->set_data($data);

    return $response;
}


// Add the shipping class to the bottom of each item in the cart
add_filter('woocommerce_cart_item_name', 'shipping_class_in_item_name', 20, 3);
function shipping_class_in_item_name($item_name, $cart_item, $cart_item_key)
{
    // If the page is NOT the Shopping Cart or the Checkout, then return the product title (otherwise continue...)
    if (!(is_cart() || is_checkout())) {
        return $item_name;
    }

    $product             = $cart_item['data']; // Get the WC_Product object instance
    $shipping_class_id   = $product->get_shipping_class_id(); // Shipping class ID
    $shipping_class_term = get_term(
        $shipping_class_id,
        'product_shipping_class'
    );

    // Return default product title (in case of no Shipping Class)
    if (empty($shipping_class_id)) {
        return $item_name;
    }

    // If the Shipping Class slug is either of these, then add a prefix and suffix to the output
    if (
        'flat-1995-per' == $shipping_class_term->slug
        || 'flat-4999-per' == $shipping_class_term->slug
    ) {
        $prefix = '$';
        $suffix = 'each';
    }

    $label = __('Shipping Class', 'woocommerce');

    // Output the Product Title and the new code which wraps the Shipping Class name
    return $item_name . '<br><p class="item-shipping_class" style="margin:0.25em 0 0; font-size: 0.875em;"><em>' . $label . ': </em>' . $prefix . $shipping_class_term->name . ' ' . $suffix . '</p>';
}

add_action(
    'rest_api_init',
    function () {

        register_rest_route(
            'frego/v1',
            '/setting',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => function ($request) {
                    var_dump(is_user_logged_in());
                    $responses = [];

                    foreach ($request->get_params() as $key => $value) {
                        $responses += [$key => get_option($key)];
                    }

                    return $responses;
                },
                'permission_callback' => '__return_true',
                'args'                => array(),

            ]
        );
    },
    10
);


// Enable the option show in rest
add_filter('acf/rest_api/field_settings/show_in_rest', '__return_true');

// Enable the option edit in rest
add_filter('acf/rest_api/field_settings/edit_in_rest', '__return_true');

add_action('init', function () {
    $extend_instance = Package::container()->get(ExtendRestApi::class);

    $extend_instance->register_endpoint_data(
        array(
            'endpoint'        =>            CartItemSchema::IDENTIFIER,
            'namespace'       => 'store',
            'schema_callback' => function () {
                return [
                    'id' => [
                        'description' => 'store ID',
                        'type' => 'integer',
                    ],
                    'first_name' => [
                        'description' => 'store first name',
                        'type' => 'string',
                    ],
                    'last_name' => [
                        'description' => 'store last name',
                        'type' => 'string',
                    ],
                    'shop_name' => [
                        'description' => 'shop name',
                        'type' => 'string',
                    ],
                    'shop_url' => [
                        'description' => 'shop url',
                        'format' => 'url',
                        'type' => 'string',
                    ],
                    'avatar' => [
                        'description' => 'shop avatar',
                        'type' => 'string',
                    ],
                    'banner' => [
                        'description' => 'shop banner',
                        'format' => 'url',
                        'type' => 'string',
                    ],
                    'address' => [
                        'description' => 'shop address',
                        'type' => 'object',
                        'properties' => [
                            'street_1' => [
                                'description' => 'shop street_1',
                                'type' => 'string',
                            ],
                            'street_2' => [
                                'description' => 'shop street_2',
                                'type' => 'string',
                            ],
                            'city' => [
                                'description' => 'shop city',
                                'type' => 'string',
                            ],
                            'zip' => [
                                'description' => 'shop zip',
                                'type' => 'string',
                            ],
                            'state' => [
                                'description' => 'shop state',
                                'type' => 'string',
                            ],
                            'country' => [
                                'description' => 'shop country',
                                'type' => 'string',
                            ],
                        ]
                    ],
                ];
            },
            'data_callback'   => function ($cart_item) {
                $vendor = dokan_get_vendor_by_product($cart_item['id']);

                return [
                    'id'         => $vendor->get_id(),
                    'first_name' => $vendor->get_first_name(),
                    'last_name'  => $vendor->get_last_name(),
                    'shop_name'  => $vendor->get_shop_name(),
                    'shop_url'   => $vendor->get_shop_url(),
                    'avatar'     => $vendor->get_avatar(),
                    'banner'     => $vendor->get_banner(),
                    'address'     => $vendor->get_address(),
                ];
            },
        )
    );
});

// function check_headers()
// {
// $headers =   array_change_key_case(getallheaders(), CASE_LOWER);

// if (isset($headers["authorization"])) {
// $user_id = get_current_user_id();

// if ($user_id > 0) {
// $user = get_user_by('id', $user_id);
// wp_set_current_user($user_id, $user->user_login);
// wp_set_auth_cookie($user_id);
// } else {
// wp_logout();
// }
// }
// }

// allow customers to manage their own data
// add_filter(
// 'woocommerce_rest_check_permissions',
// function ($permission, $context, $object_id, $post_type) {
// if ($post_type === 'user') {
// return $permission || $object_id === get_current_user_id();
// }

// return $permission;
// },
// 10,
// 4
// );

// function settings($request)
// {
// $decode = $request->get_param('decode');

// try {
// $languages    = apply_filters(
// 'wpml_active_languages',
// array(),
// 'orderby=id&order=desc'
// );
// $default_language = apply_filters('wpml_default_language', null);

// $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EGP';

// $result = array(
// 'default_language'       => $default_language,
// 'languages'              => $languages,
// 'currency'               => $currency,
// 'timezone_string'        => get_option('timezone_string') || wc_timezone_string(),
// 'date_format'            => get_option('date_format'),
// 'time_format'            => get_option('time_format'),
// );

// wp_cache_set('settings_' . $decode, $result, 'frego');

// wp_send_json($result);
// } catch (Exception $e) {
// return new WP_Error('error_setting',      __('Some thing wrong.', "frego-app-control"),      array(
// 'status' => 403,
// ));
// }
// }

// function auto_login($request)
// {
// $redirect    = $request->get_param('redirect');

// $user_id = get_current_user_id();
// $cookie = wp_generate_auth_cookie($user_id, 60 * 60 * 2);

// if ($user_id > 0) {
// $user = get_user_by('id', $user_id);
// wp_set_current_user($user_id, $user->user_login);
// wp_set_auth_cookie($user_id);
// } else {
// wp_logout();
// }

// if (isset($redirect)) {
// wp_redirect($redirect);
// exit;
// }
// }

// function create_ACF_meta_in_REST()
// {
// $postypes_to_exclude = ['acf-field-group', 'acf-field'];
// $extra_postypes_to_include = ["attachment"];
// $post_types = array_diff(get_post_types(["_builtin" => false], 'names'), $postypes_to_exclude);

// array_push($post_types, $extra_postypes_to_include);

// foreach ($post_types as $post_type) {
// register_rest_field($post_type, 'acf', [
// 'get_callback'    => 'expose_ACF_fields',
// 'schema'          => null,
// ]);
// }
// }

// function expose_ACF_fields($object)
// {
// $ID = $object['id'];
// return get_fields($ID);
// }

// function generate_token($request)
// {
// $id = $request->get_param('id');
// $providerID = $request->get_param('provider');
// $accessToken = $request->get_param('access_token');
// $provider = NextendSocialLogin::$enabledProviders[$providerID];

// try {
// $user = $provider->findUserByAccessToken($accessToken);

// $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
// $issued_at  = time();
// $not_before = $issued_at;
// $not_before = apply_filters('jwt_auth_not_before', $not_before, $issued_at);
// $expire     = $issued_at + (DAY_IN_SECONDS * 7);
// $expire     = apply_filters('jwt_auth_expire', $expire, $issued_at);

// $payload = array(
// 'iss'  => apply_filters('jwt_auth_iss', get_bloginfo('url')),
// 'iat'  => $issued_at,
// 'nbf'  => $not_before,
// 'exp'  => $expire,
// 'data' => array(
// 'user' => array(
// 'id' => $user,
// ),
// ),
// );

// $alg = apply_filters('jwt_auth_alg', 'HS256');

// Let the user modify the token data before the sign.
// $token = JWT::encode(apply_filters('jwt_auth_payload', $payload, null), $secret_key, $alg);

// return wp_send_json($token, 200);
// } catch (Exception $e) {
// wp_send_json(new WP_Error('error', $e->getMessage()));
// }
// }

// add_action('rest_api_init', 'create_ACF_meta_in_REST');

// add_action('init', 'check_headers', 1);
add_action(
    'rest_api_init',
    function () {
        // register_rest_route(
        // 'frego/v1',
        // 'jwt',
        // [
        // 'methods'             => WP_REST_Server::CREATABLE,
        // 'callback'            => 'generate_token',
        // 'permission_callback' => '__return_true',                'args' => array()

        // ]
        // );

        // register_rest_route('frego/v1', 'settings', array(
        // 'methods'  => WP_REST_Server::READABLE,
        // 'callback' => 'settings',
        // 'permission_callback'   => '__return_true',                'args' => array()

        // ));

        // register_rest_route('frego/v1', 'auto-login', array(
        // 'methods'             => WP_REST_Server::READABLE,
        // 'callback'            => 'auto_login',
        // 'permission_callback' => '__return_true',                'args' => array()

        // ));

        // register_rest_route(
        // 'frego/v1',
        // '/nonce',
        // [
        // 'methods'             => WP_REST_Server::CREATABLE,
        // 'callback'            => function ($request) {
        // $key  = $request->get_param('key');
        // if (is_user_logged_in()) {
        // return  wp_create_nonce($key||'wc_store_api');
        // }
        // },
        // 'permission_callback' => '__return_true',
        // 'args'                => [
        // 'key' => [
        // 'required'    => true,
        // 'type'        => 'string',
        // 'description' => 'Key.',
        // ],
        // ],
        // ]
        // );
    },
    10
);

/*
 * Add meta fields support in rest API for post type `Post`
 *
 * This function will allow custom parameters within API request URL. Add post meta support for post type `Post`.
 *
 * > How to use?
 * http://mysite.com/wp-json/wp/v2/posts?meta_key=<my_meta_key>&meta_value=<my_meta_value>
 *
 * > E.g. Get posts which post meta `already-visited` value is `true`.
 *
 * Request like: http://mysite.com/wp-json/wp/v2/post?meta_key=already-visited&meta_value=true
 *

 *
 * @link    https://codex.wordpress.org/Class_Reference/WP_Query
 *
 * @see     Wp-includes/Rest-api/Endpoints/Class-wp-rest-posts-controller.php
 *
 * @param   array   $args       Contains by default pre written params.
 * @param   array   $request    Contains params values passed through URL request.
 * @return  array   $args       New array with added custom params and its values.
 */
// if (! function_exists('post_meta_request_params')) :
// function post_meta_request_params($args, $request)
// {
// $args += array(
// 'meta_key'   => $request['meta_key'],
// 'meta_value' => $request['meta_value'],
// 'meta_query' => $request['meta_query'],
// );

// return $args;
// }
// add_filter('rest_post_query', 'post_meta_request_params', 99, 2);
// add_filter( 'rest_page_query', 'post_meta_request_params', 99, 2 ); // Add support for `page`
// add_filter('rest_my-custom-post_query', 'post_meta_request_params', 99, 2); // Add support for `my-custom-post`

// $postypes_to_exclude = ['acf-field-group', 'acf-field'];
// $extra_postypes_to_include = ["attachment"];
// $post_types = array_diff(get_post_types(["_builtin" => false], 'names'), $postypes_to_exclude);

// array_push($post_types, $extra_postypes_to_include);

// foreach (get_post_types() as $post_type) {
// add_filter('rest_'.$post_type.'_query', 'post_meta_request_params', 99, 2); // Add support for `my-custom-post`
// }

// endif;