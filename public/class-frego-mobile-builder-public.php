<?php

use Firebase\JWT\JWT;

/**
 * The public-facing functionality of the plugin.
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class Mobile_Builder_Public
{
    public $token_schema = [
        '$schema'    => 'http://json-schema.org/draft-04/schema#',
        'title'      => 'Get Authentication Properties',
        'type'       => 'object',
        'properties' => [
            'token' => [
                'type'        => 'string',
                'description' => 'JWT token',
            ],
        ],
    ];

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
     *  Then key to encode token.
     *
     * @var string The key to encode token
     */
    private $key;

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
        $this->key         = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '';
    }

    public function get_token_schema()
    {
        return $this->token_schema;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles()
    {
        /*
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Blog_1_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Blog_1_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        if (isset($_GET['mobile'])) {
            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'css/checkout.css',
                [],
                $this->version,
                'all'
            );
        }
    }

    /**
     * Registers a REST API route.
     */
    public function add_api_routes()
    {
        $namespace = $this->plugin_name . '/v' . intval($this->version);
        $review    = new WC_REST_Product_Reviews_Controller();
        $customer  = new WC_REST_Customers_Controller();
        $user      = new WP_REST_Users_Controller();

        register_rest_route(
            $namespace,
            'reviews',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $review, 'create_item' ],
                    'permission_callback' => '__return_true',
                    'args'                => array_merge(
                        ([ $review, 'get_endpoint_args_for_item_schema' ])(
                            WP_REST_Server::CREATABLE
                        ),
                        [
                            'product_id'     => [
                                'required'    => true,
                                'description' => __(
                                    'Unique identifier for the product.',
                                    'woocommerce'
                                ),
                                'type'        => 'integer',
                            ],
                            'review'         => [
                                'required'    => true,
                                'type'        => 'string',
                                'description' => __(
                                    'Review content.',
                                    'woocommerce'
                                ),
                            ],
                            'reviewer'       => [
                                'required'    => true,
                                'type'        => 'string',
                                'description' => __(
                                    'Name of the reviewer.',
                                    'woocommerce'
                                ),
                            ],
                            'reviewer_email' => [
                                'required'    => true,
                                'type'        => 'string',
                                'description' => __(
                                    'Email of the reviewer.',
                                    'woocommerce'
                                ),
                            ],
                        ]
                    ),
                ],
                'schema' => [ $review, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'customers/(?P<id>[\\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $customer, 'update_item' ],
                    'permission_callback' => [
                        $this,
                        'update_item_permissions_check',
                    ],
                    'args'                => ([ $customer, 'get_endpoint_args_for_item_schema' ])(
                        WP_REST_Server::EDITABLE
                    ),
                ],
                'schema' => [ $customer, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'token',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'app_token' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        register_rest_route(
            $namespace,
            'login',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'login' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'username' => [
                            'required'    => true,
                            'type'        => 'string',
                            'description' => 'Login name for the user.',
                        ],
                        'password' => [
                            'required'    => true,
                            'type'        => 'string',
                            'description' => 'Password for the user.',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_token_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'logout',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'logout' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        register_rest_route(
            $namespace,
            'current',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'current' ],
                'permission_callback' => '__return_true',
                                'args' => array()

            ]
        );

        register_rest_route(
            $namespace,
            'facebook',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'login_facebook' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'token' => [
                            'required'    => true,
                            'type'        => 'string',
                            'description' => 'Facebook token.',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_token_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'google',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'login_google' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'token' => [
                            'required'    => true,
                            'type'        => 'string',
                            'description' => 'Facebook token.',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_token_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'apple',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'login_apple' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'token' => [
                            'required'    => true,
                            'type'        => 'string',
                            'description' => 'Facebook token.',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_token_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'register',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $user, 'create_item' ],
                    'permission_callback' => function ($request) {
                        $request->set_param('roles', [ 'customer' ]);

                        // METHOD 2: Be nice and provide an error message
                        // if (!current_user_can('create_users') && $request['roles'] !== array('subscriber')) {

                        // return new WP_Error(
                        // 'rest_cannot_create_user',
                        // __('Sorry, you are only allowed to create new users with the subscriber role.'),
                        // array('status' => rest_authorization_required_code())
                        // );
                        // }

                        return true;
                    },
                    'args'                => $user->get_endpoint_args_for_item_schema(
                        WP_REST_Server::CREATABLE
                    ),
                ],
                'schema' => [ $user, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $namespace,
            'lost-password',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'retrieve_password' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'user_login' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                ],
            ]
        );

        register_rest_route(
            $namespace,
            'settings',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'settings' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        register_rest_route(
            $namespace,
            'change-password',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'change_password' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'password_old' => [
                        'required'    => true,
                        'type'        => 'string',
                        'description' => 'The plaintext old user password',
                    ],
                    'password_new' => [
                        'required'    => true,
                        'type'        => 'string',
                        'description' => 'The plaintext new user password',
                    ],
                ],
            ]
        );

        register_rest_route(
            $namespace,
            'update-location',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'update_location' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        register_rest_route(
            $namespace,
            'zones',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'zones' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        register_rest_route(
            $namespace,
            'get-continent-code-for-country',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_continent_code_for_country' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        /*
         * Add payment router
         *
         * @author Ngoc Dang

         */
        register_rest_route(
            $namespace,
            'process_payment',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'process_payment' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        /*
         * Check mobile phone number
         *
         * @author Ngoc Dang

         */
        register_rest_route(
            $namespace,
            'check-phone-number',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'mbd_check_phone_number' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );

        /*
         * Check email and username
         *
         * @author Ngoc Dang

         */
        register_rest_route(
            $namespace,
            'check-info',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'mbd_validate_user_info' ],
                'permission_callback' => '__return_true',                'args' => array()

            ]
        );
    }

    /**
     * Check mobile phone number.
     *
     * @author Ngoc Dang
     *
     * @param WP_REST_Request $request Request object.
     */
    public function mbd_check_phone_number($request)
    {
        $digits_phone = $request->get_param('digits_phone');
        $type         = $request->get_param('type');

        $users = get_users(
            [
                'meta_key'     => 'digits_phone',
                'meta_value'   => $digits_phone,
                'meta_compare' => '=',
            ]
        );

        if ('register' == $type) {
            if (count($users) > 0) {
                $error = new WP_Error();
                $error->add(
                    403,
                    __(
                        'Your phone number already exist in database!',
                        'frego-mobile-builder'
                    ),
                    [ 'status' => 400 ]
                );

                return $error;
            }

            return new WP_REST_Response(
                [
                    'data' => __(
                        'Phone number not exits!',
                        'frego-mobile-builder'
                    ),
                ],
                200
            );
        }

        // Login folow
        if (0 == count($users)) {
            $error = new WP_Error();
            $error->add(
                403,
                __(
                    'Your phone number not exist in database!',
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }

        return new WP_REST_Response(
            [
                'data' => __(
                    'Phone number number exist!',
                    'frego-mobile-builder'
                ),
            ],
            200
        );
    }

    /**
     * Change the way encode token.
     *
     * @author Ngoc Dang
     *
     * @param mixed $token
     * @param mixed $user_id
     */
    public function custom_digits_rest_token_data($token, $user_id)
    {
        $user = get_user_by('id', $user_id);
        if ($user) {
            $token = $this->generate_token($user);
            $data  = [
                'token' => $token,
            ];
            wp_send_json_success($data);
        } else {
            wp_send_json_error(
                new WP_Error(
                    404,
                    __('Something wrong!.', 'frego-mobile-builder'),
                    [
                        'status' => 403,
                    ]
                )
            );
        }
    }

    /**
     * Change checkout template.
     *
     * @author Ngoc Dang
     *
     * @param mixed $template
     * @param mixed $template_name
     * @param mixed $template_path
     */
    public function woocommerce_locate_template(
        $template,
        $template_name,
        $template_path
    ) {
        if ('checkout/form-checkout.php' == $template_name
            && isset($_GET['mobile'])
        ) {
            return plugin_dir_path(__DIR__) .
                'templates/checkout/form-checkout.php';
        }

        if ('checkout/thankyou.php' == $template_name
            && isset($_GET['mobile'])
        ) {
            return plugin_dir_path(__DIR__) . 'templates/checkout/thankyou.php';
        }

        if ('checkout/form-pay.php' == $template_name
            && isset($_GET['mobile'])
        ) {
            return plugin_dir_path(__DIR__) . 'templates/checkout/form-pay.php';
        }

        return $template;
    }

    /**
     * Find the selected Gateway, and process payment.
     *
     * @author Ngoc Dang
     *
     * @param null|WP_REST_Request $request Request object.
     */
    public function process_payment($request = null)
    {
        // Create a Response Object
        $response = [];

        // Get parameters
        $order_id       = $request->get_param('order_id');
        $payment_method = $request->get_param('payment_method');

        $error = new WP_Error();

        // Perform Pre Checks
        if (! class_exists('WooCommerce')) {
            $error->add(
                400,
                __(
                    'Failed to process payment. WooCommerce either missing or deactivated.',
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }
        if (empty($order_id)) {
            $error->add(
                401,
                __("Order ID 'order_id' is required.", 'frego-mobile-builder'),
                [ 'status' => 400 ]
            );

            return $error;
        }
        if (false == wc_get_order($order_id)) {
            $error->add(
                402,
                __(
                    "Order ID 'order_id' is invalid. Order does not exist.",
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }
        if ('pending' !== wc_get_order($order_id)->get_status()
            && 'failed' !== wc_get_order($order_id)->get_status()
        ) {
            $error->add(
                403,
                __(
                    "Order status is '" .
                        wc_get_order($order_id)->get_status() .
                        "', meaning it had already received a successful payment. Duplicate payments to the order is not allowed. The allow status it is either 'pending' or 'failed'. ",
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }
        if (empty($payment_method)) {
            $error->add(
                404,
                __(
                    "Payment Method 'payment_method' is required.",
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }

        // Find Gateway
        $avaiable_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $gateway           = $avaiable_gateways[ $payment_method ];

        if (empty($gateway)) {
            $all_gateways = WC()->payment_gateways->payment_gateways();
            $gateway      = $all_gateways[ $payment_method ];

            if (empty($gateway)) {
                $error->add(
                    405,
                    __(
                        "Failed to process payment. WooCommerce Gateway '" .
                            $payment_method .
                            "' is missing.",
                        'frego-mobile-builder'
                    ),
                    [ 'status' => 400 ]
                );

                return $error;
            }
            $error->add(
                406,
                __(
                    "Failed to process payment. WooCommerce Gateway '" .
                        $payment_method .
                        "' exists, but is not available.",
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }
        if (! has_filter('pre_process_' . $payment_method . '_payment')) {
            $error->add(
                407,
                __(
                    "Failed to process payment. WooCommerce Gateway '" .
                        $payment_method .
                        "' exists, but 'REST Payment - " .
                        $payment_method .
                        "' is not available.",
                    'frego-mobile-builder'
                ),
                [ 'status' => 400 ]
            );

            return $error;
        }

        // Pre Process Payment
        $parameters = apply_filters(
            'pre_process_' . $payment_method . '_payment',
            [
                'order_id'       => $order_id,
                'payment_method' => $payment_method,
            ]
        );

        if (true === $parameters['pre_process_result']) {
            // Process Payment
            $payment_result = $gateway->process_payment($order_id);
            if ('success' === $payment_result['result']) {
                $response['code']    = 200;
                $response['message'] = __(
                    'Payment Successful.',
                    'frego-mobile-builder'
                );
                $response['data']    = $payment_result;

                // Return Successful Response
                return new WP_REST_Response($response, 200);
            }

            return new WP_Error(
                500,
                __(
                    'Payment Failed, Check WooCommerce Status Log for further information.',
                    'frego-mobile-builder'
                ),
                $payment_result
            );
        }

        return new WP_Error(
            408,
            __('Payment Failed, Pre Process Failed.', 'frego-mobile-builder'),
            $parameters['pre_process_result']
        );
    }

    /**
     * @param WP_REST_Request $request Request object.
     */
    public function get_continent_code_for_country($request)
    {
        $cc         = $request->get_param('cc');
        $wc_country = new WC_Countries();

        wp_send_json($wc_country->get_continent_code_for_country($cc));
    }

    public function zones()
    {
        $delivery_zones = (array) WC_Shipping_Zones::get_zones();

        $data = [];
        foreach ($delivery_zones as $key => $the_zone) {
            $shipping_methods = [];

            foreach ($the_zone['shipping_methods'] as $value) {
                $shipping_methods[] = [
                    'instance_id'        => $value->instance_id,
                    'id'                 => $value->instance_id,
                    'method_id'          => $value->id,
                    'method_title'       => $value->title,
                    'method_description' => $value->method_description,
                    'settings'           => [
                        'cost' => [
                            'value' => $value->cost,
                        ],
                    ],
                ];
            }

            $data[] = [
                'id'               => $the_zone['id'],
                'zone_name'        => $the_zone['zone_name'],
                'zone_locations'   => $the_zone['zone_locations'],
                'shipping_methods' => $shipping_methods,
            ];
        }

        wp_send_json($data);
    }

    /**
     * @param WP_REST_Request $request Request object.
     */
    public function change_password($request)
    {
        $current_user = wp_get_current_user();
        if (! $current_user->exists()) {
            return new WP_Error(
                'user_not_login',
                __('Please login first.', 'frego-mobile-builder'),
                [
                    'status' => 403,
                ]
            );
        }

        $username     = $current_user->user_login;
        $password_old = $request->get_param('password_old');
        $password_new = $request->get_param('password_new');

        // try login with username and password
        $user = wp_authenticate($username, $password_old);

        if (is_wp_error($user)) {
            $error_code = $user->get_error_code();

            return new WP_Error(
                $error_code,
                $user->get_error_message($error_code),
                [
                    'status' => 403,
                ]
            );
        }

        wp_set_password($password_new, $current_user->ID);

        return $current_user->ID;
    }

    /**
     * Update User Location.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return int|WP_Error
     */
    public function update_location($request)
    {
        $current_user = wp_get_current_user();

        if (! $current_user->exists()) {
            return new WP_Error(
                'user_not_login',
                __('Please login first.', 'frego-mobile-builder'),
                [
                    'status' => 403,
                ]
            );
        }

        $location = $request->get_param('location');

        update_user_meta($current_user->ID, 'mbd_location', $location);

        return $current_user->ID;
    }

    /**
     * @param WP_REST_Request $request Request object.
     */
    public function settings($request)
    {
        $result = $request->get_param('decode');

        if ($result) {
            return $result;
        }

        try {
            global $woocommerce_wpml;

            // $admin = new Mobile_Builder_Admin(
            //     MOBILE_BUILDER_PLUGIN_NAME,
            //     MOBILE_BUILDER_CONTROL_VERSION
            // );

            $currencies = [];

            $languages    = apply_filters(
                'wpml_active_languages',
                [],
                'orderby=id&order=desc'
            );
            $default_lang = apply_filters(
                'wpml_default_language',
                substr(get_locale(), 0, 2)
            );

            $currency = function_exists('get_woocommerce_currency')
                ? get_woocommerce_currency()
                : 'USD';

            if (! empty($woocommerce_wpml->multi_currency)
                && ! empty($woocommerce_wpml->settings['currencies_order'])
            ) {
                $currencies = $woocommerce_wpml->multi_currency->get_currencies(
                    'include_default = true'
                );
            }

            $configs = get_option(
                'mobile_builder_configs',
                [
                    'requireLogin'       => false,
                    'toggleSidebar'      => false,
                    'isBeforeNewProduct' => 5,
                ]
            );

            $gmw = get_option('gmw_options');

            $result = [
                'language'               => $default_lang,
                'languages'              => $languages,
                'currencies'             => $currencies,
                'currency'               => $currency,
                'enable_guest_checkout'  => get_option(
                    'woocommerce_enable_guest_checkout',
                    true
                ),
                'timezone_string'        => get_option('timezone_string')
                    ? get_option('timezone_string')
                    : wc_timezone_string(),
                'date_format'            => get_option('date_format'),
                'time_format'            => get_option('time_format'),
                'configs'                => maybe_unserialize($configs),
                'default_location'       => $gmw['post_types_settings'],
                'checkout_user_location' => apply_filters(
                    'wcfmmp_is_allow_checkout_user_location',
                    true
                ),
            ];

            wp_send_json($result);
        } catch (Exception $e) {
            return new WP_Error(
                'error_setting',
                __('Some thing wrong.', 'frego-mobile-builder'),
                [
                    'status' => 403,
                ]
            );
        }
    }

    /**
     * Create token for app.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return bool|WP_Error
     */
    public function app_token()
    {
        $wp_auth_user = defined('WP_AUTH_USER') ? WP_AUTH_USER : 'wp_auth_user';

        $user = get_user_by('login', $wp_auth_user);

        if ($user) {
            return $this->generate_token($user, [ 'read_only' => true ]);
        }

        return new WP_Error(
            'create_token_error',
            __('You did not create user wp_auth_user', 'frego-mobile-builder'),
            [
                'status' => 403,
            ]
        );
    }

    /**
     * Lost password for user.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return bool|WP_Error
     */
    public function retrieve_password($request)
    {
        $errors = new WP_Error();

        $user_login = $request->get_param('user_login');

        if (empty($user_login) || ! is_string($user_login)) {
            $errors->add(
                'empty_username',
                __(
                    '<strong>ERROR</strong>: Enter a username or email address.',
                    'frego-mobile-builder'
                )
            );
        } elseif (strpos($user_login, '@')) {
            $user_data = get_user_by('email', trim(wp_unslash($user_login)));
            if (empty($user_data)) {
                $errors->add(
                    'invalid_email',
                    __(
                        '<strong>ERROR</strong>: There is no account with that username or email address.',
                        'frego-mobile-builder'
                    )
                );
            }
        } else {
            $login     = trim($user_login);
            $user_data = get_user_by('login', $login);
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        if (! $user_data) {
            $errors->add(
                'invalidcombo',
                __(
                    '<strong>ERROR</strong>: There is no account with that username or email address.',
                    'frego-mobile-builder'
                )
            );

            return $errors;
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key        = get_password_reset_key($user_data);

        if (is_wp_error($key)) {
            return $key;
        }

        if (is_multisite()) {
            $site_name = get_network()->site_name;
        } else {
            /*
             * The blogname option is escaped with esc_html on the way into the database
             * in sanitize_option we want to reverse this for the plain text arena of emails.
             */
            $site_name = wp_specialchars_decode(
                get_option('blogname'),
                ENT_QUOTES
            );
        }

        $message =
            __(
                'Someone has requested a password reset for the following account:',
                'frego-mobile-builder'
            ) . "\r\n\r\n";
        // translators: %s: site name
        $message .=
            sprintf(__('Site Name: %s', 'frego-mobile-builder'), $site_name) .
            "\r\n\r\n";
        // translators: %s: user login
        $message .=
            sprintf(__('Username: %s', 'frego-mobile-builder'), $user_login) .
            "\r\n\r\n";
        $message .=
            __(
                'If this was a mistake, just ignore this email and nothing will happen.',
                'frego-mobile-builder'
            ) . "\r\n\r\n";
        $message .=
            __(
                'To reset your password, visit the following address:',
                'frego-mobile-builder'
            ) . "\r\n\r\n";
        $message .=
            '<' .
            network_site_url(
                "wp-login.php?action=rp&key={$key}&login=" .
                    rawurlencode($user_login),
                'login'
            ) .
            ">\r\n";

        // translators: Password reset notification email subject. %s: Site title
        $title = sprintf(
            __('[%s] Password Reset', 'frego-mobile-builder'),
            $site_name
        );

        /**
         * Filters the subject of the password reset email.
         *
         * @param string  $title      default email title
         * @param string  $user_login the username for the user
         * @param WP_User $user_data  WP_User object
         */
        $title = apply_filters(
            'retrieve_password_title',
            $title,
            $user_login,
            $user_data
        );

        /**
         * Filters the message body of the password reset mail.
         *
         * If the filtered message is empty, the password reset email will not be sent.
         *
         * @param string  $message    default mail message
         * @param string  $key        the activation key
         * @param string  $user_login the username for the user
         * @param WP_User $user_data  WP_User object
         */
        $message = apply_filters(
            'retrieve_password_message',
            $message,
            $key,
            $user_login,
            $user_data
        );

        if ($message
            && ! wp_mail($user_email, wp_specialchars_decode($title), $message)
        ) {
            return new WP_Error(
                'send_email',
                __(
                    'Possible reason: your host may have disabled the mail() function.',
                    'frego-mobile-builder'
                ),
                [
                    'status' => 403,
                ]
            );
        }

        return true;
    }

    /**
     *  Get current user login.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return mixed
     */
    public function current($request)
    {
        $current_user = wp_get_current_user();

        return $current_user->data;
    }

    /**
     *  Validate user.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return mixed
     */
    public function mbd_validate_user_info($request)
    {
        $email = $request->get_param('email');
        $name  = $request->get_param('name');

        // Validate email
        if (! is_email($email) || email_exists($email)) {
            return new WP_Error(
                'email',
                __(
                    'Your input email not valid or exist in database.',
                    'frego-mobile-builder'
                ),
                [
                    'status' => 403,
                ]
            );
        }

        // Validate username
        if (username_exists($name) || empty($name)) {
            return new WP_Error(
                'name',
                __('Your username exist.', 'frego-mobile-builder'),
                [
                    'status' => 403,
                ]
            );
        }

        return [ 'message' => __('success!', 'frego-mobile-builder') ];
    }

    public function getUrlContent($url)
    {
        $parts  = parse_url($url);
        $host   = $parts['host'];
        $ch     = curl_init();
        $header = [
            'GET /1575051 HTTP/1.1',
            "Host: {$host}",
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language:en-US,en;q=0.8',
            'Cache-Control:max-age=0',
            'Connection:keep-alive',
            'Host:adfoc.us',
            'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Login with google.
     *
     * @param WP_REST_Request $request Request object.
     */
    public function login_google($request)
    {
        $idToken = $request->get_param('idToken');

        $url  = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
        $data = [ 'idToken' => $idToken ];

        // use key 'http' even if you send the request to https://...
        $options = [
            'http' => [
                'header' => "application/json; charset=UTF-8\r\n",
                'method' => 'GET',
            ],
        ];

        $context = stream_context_create($options);
        $json    = $this->getUrlContent($url);
        $result  = json_decode($json);

        if (false === $result) {
            $error = new WP_Error();
            $error->add(
                403,
                __('Get Firebase user info error!', 'frego-mobile-builder'),
                [ 'status' => 400 ]
            );

            return $error;
        }

        // Email not exist
        $email = $result->email;
        if (! $email) {
            return new WP_Error(
                'email_not_exist',
                __('User not provider email', 'frego-mobile-builder'),
                [
                    'status' => 403,
                ]
            );
        }

        $user = get_user_by('email', $email);

        // Return data if user exist in database
        if ($user) {
            $token = $this->generate_token($user);

            return [
                'token' => $token,
            ];
        }
        $user_id = wp_insert_user(
            [
                'user_pass'     => wp_generate_password(),
                'user_login'    => $result->email,
                'user_nicename' => $result->name,
                'user_email'    => $result->email,
                'display_name'  => $result->name,
                'first_name'    => $result->given_name,
                'last_name'     => $result->family_name,
            ]
        );

        if (is_wp_error($user_id)) {
            $error_code = $user->get_error_code();

            return new WP_Error(
                $error_code,
                $user_id->get_error_message($error_code),
                [
                    'status' => 403,
                ]
            );
        }

        $user = get_user_by('id', $user_id);

        $token = $this->generate_token($user);
        $data  = [
            'token' => $token,
        ];

        add_user_meta($user_id, 'mbd_login_method', 'google', true);
        add_user_meta($user_id, 'mbd_avatar', $result->picture, true);

        return $data;
    }

    /**
     * Login With Apple.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @throws Exception
     *
     * @return array | object
     */
    public function login_apple($request)
    {
        try {
            $identityToken = $request->get_param('identityToken');
            $userIdentity  = $request->get_param('user');
            $fullName      = $request->get_param('fullName');

            $tks = \explode('.', $identityToken);
            if (3 != \count($tks)) {
                return new WP_Error(
                    'error_login_apple',
                    __('Wrong number of segments', 'frego-mobile-builder'),
                    [
                        'status' => 403,
                    ]
                );
            }

            list($headb64) = $tks;

            if (null ===            ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))
            ) {
                return new WP_Error(
                    'error_login_apple',
                    __('Invalid header encoding', 'frego-mobile-builder'),
                    [
                        'status' => 403,
                    ]
                );
            }

            if (! isset($header->kid)) {
                return new WP_Error(
                    'error_login_apple',
                    __(
                        '"kid" empty, unable to lookup correct key',
                        'frego-mobile-builder'
                    ),
                    [
                        'status' => 403,
                    ]
                );
            }

            $publicKeyDetails = Mobile_Builder_Public_Key::getPublicKey(
                $header->kid
            );
            $publicKey        = $publicKeyDetails['publicKey'];
            $alg              = $publicKeyDetails['alg'];

            $payload = JWT::decode($identityToken, $publicKey, [ $alg ]);

            if ($payload->sub !== $userIdentity) {
                return new WP_Error(
                    'validate-user',
                    __('User not validate', 'frego-mobile-builder'),
                    [
                        'status' => 403,
                    ]
                );
            }

            $user1 = get_user_by('email', $payload->email);
            $user2 = get_user_by('login', $userIdentity);

            // Return data if user exist in database
            if ($user1) {
                $token = $this->generate_token($user1);

                return [
                    'token' => $token,
                ];
            }

            if ($user2) {
                $token = $this->generate_token($user2);

                return [
                    'token' => $token,
                ];
            }

            $userdata = [
                'user_pass'    => wp_generate_password(),
                'user_login'   => $userIdentity,
                'user_email'   => $payload->email,
                'display_name' => $fullName['familyName'] . ' ' . $fullName['givenName'],
                'first_name'   => $fullName['familyName'],
                'last_name'    => $fullName['givenName'],
            ];

            $user_id = wp_insert_user($userdata);

            if (is_wp_error($user_id)) {
                $error_code = $user_id->get_error_code();

                return new WP_Error(
                    $error_code,
                    $user_id->get_error_message($error_code),
                    [
                        'status' => 403,
                    ]
                );
            }

            $user = get_user_by('id', $user_id);

            $token = $this->generate_token($user);

            add_user_meta($user_id, 'mbd_login_method', 'apple', true);

            return [
                'token' => $token,
            ];
        } catch (Exception $e) {
            return new WP_Error(
                'error_login_apple',
                $e->getMessage(),
                [
                    'status' => 403,
                ]
            );
        }
    }

    /**
     * @param WP_REST_Request $request Request object.
     */
    public function login_facebook($request)
    {
        $token = $request->get_param('token');

        $fb = new \Facebook\Facebook(
            [
                'app_id'                => FACEBOOK_APP_ID,
                'app_secret'            => FACEBOOK_APP_SECRET,
                'default_graph_version' => 'v2.10',
            // 'default_access_token' => '{access-token}', // optional
            ]
        );

        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $response = $fb->get(
                '/me?fields=id,first_name,last_name,name,picture,email',
                $token
            );
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo __('Graph returned an error: ', 'frego-mobile-builder') .
                $e->getMessage();
            exit();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo __(
                'Facebook SDK returned an error: ',
                'frego-mobile-builder'
            ) . $e->getMessage();
            exit();
        }

        $me = $response->getGraphUser();

        // Email not exist
        $email = $me->getEmail();
        if (! $email) {
            return new WP_Error(
                'email_not_exist',
                __('User not provider email', 'frego-mobile-builder'),
                [
                    'status' => 403,
                ]
            );
        }

        $user = get_user_by('email', $email);

        // Return data if user exist in database
        if ($user) {
            $token = $this->generate_token($user);

            return [
                'token' => $token,
            ];
        }
        // Will create new user
        $first_name  = $me->getFirstName();
        $last_name   = $me->getLastName();
        $picture     = $me->getPicture();
        $name        = $me->getName();
        $facebook_id = $me->getId();

        $user_id = wp_insert_user(
            [
                'user_pass'     => wp_generate_password(),
                'user_login'    => $email,
                'user_nicename' => $name,
                'user_email'    => $email,
                'display_name'  => $name,
                'first_name'    => $first_name,
                'last_name'     => $last_name,
            ]
        );

        if (is_wp_error($user_id)) {
            $error_code = $user->get_error_code();

            return new WP_Error(
                $error_code,
                $user_id->get_error_message($error_code),
                [
                    'status' => 403,
                ]
            );
        }

        $user = get_user_by('id', $user_id);

        $token = $this->generate_token($user);
        $data  = [
            'token' => $token,
        ];

        add_user_meta($user_id, 'mbd_login_method', 'facebook', true);
        add_user_meta($user_id, 'mbd_avatar', $picture, true);

        return $data;
    }

    /**
     * Do login with email and password.
     *
     * @param WP_REST_Request $request Request object.
     */
    public function login($request)
    {
        $username = $request->get_param('username');
        $password = $request->get_param('password');

        // try login with username and password
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return $user;
        }

        // Generate token
        $token = $this->generate_token($user);

        // Return data
        return [
            'token' => $token,
        ];
    }

    /**
     * Log out user.
     *
     * @return array
     */
    public function logout()
    {
        wp_logout();

        return [ 'success' => true ];
    }

    /**
     *  General token.
     *
     * @param $user
     * @param mixed $data
     *
     * @return string
     */
    public function generate_token($user, $data = [])
    {
        $iat = time();
        $nbf = $iat;
        $exp = $iat + DAY_IN_SECONDS * 30;

        $token = [
            'iss'  => get_bloginfo('url'),
            'iat'  => $iat,
            'nbf'  => $nbf,
            'exp'  => $exp,
            'data' => array_merge(
                [
                    'user_id' => $user->data->ID,
                    'user' => ["id"=>$user->data->ID],
                ],
                $data
            ),
        ];

        // Generate token
        return JWT::encode($token, $this->key);
    }

    public function determine_current_user($user)
    {
        // Run only on REST API
        if (! mobile_builder_is_rest_api_request()) {
            return $user;
        }

        $token = $this->decode();

        if (is_wp_error($token)) {
            return $user;
        }

        return $token->data->user_id;
    }

    /**
     * Decode token.
     *
     * @param null|mixed $token
     *
     * @return array|WP_Error
     */
    public function decode($token = null)
    {
        // Get token on header

        if (! $token) {
            $headers = $this->headers();

            if (isset($headers['authorization'])) {
                $headers['Authorization'] = $headers['authorization'];
            }

            if (! isset($headers['Authorization'])) {
                return new WP_Error(
                    'no_auth_header',
                    __(
                        'Authorization header not found.',
                        'frego-mobile-builder'
                    ),
                    [
                        'status' => 403,
                    ]
                );
            }

            $match = preg_match(
                '/Bearer\\s(\\S+)/',
                $headers['Authorization'],
                $matches
            );

            if (! $match) {
                return new WP_Error(
                    'token_not_validate',
                    __('Token not validate format.', 'frego-mobile-builder'),
                    [
                        'status' => 403,
                    ]
                );
            }

            $token = $matches[1];
        }

        // decode token
        try {
            $data = JWT::decode($token, $this->key, [ 'HS256' ]);

            if ($data->iss != get_bloginfo('url')) {
                return new WP_Error(
                    'bad_iss',
                    __(
                        'The iss do not match with this server',
                        'frego-mobile-builder'
                    ),
                    [
                        'status' => 403,
                    ]
                );
            }
            if (! isset($data->data->user_id)) {
                return new WP_Error(
                    'id_not_found',
                    __(
                        'User ID not found in the token',
                        'frego-mobile-builder'
                    ),
                    [
                        'status' => 403,
                    ]
                );
            }

            return $data;
        } catch (Exception $e) {
            return new WP_Error(
                'invalid_token',
                $e->getMessage(),
                [
                    'status' => 403,
                ]
            );
        }
    }

    public function get_featured_media_url($object, $field_name, $request)
    {
        $featured_media_url = '';
        $image_attributes   = wp_get_attachment_image_src(
            get_post_thumbnail_id($object['id']),
            'full'
        );
        if (is_array($image_attributes) && isset($image_attributes[0])) {
            $featured_media_url = (string) $image_attributes[0];
        }

        return $featured_media_url;
    }

    /**
     * Get request headers.
     *
     * @return array|false
     */
    public function headers()
    {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }
        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $key         = str_replace(
                    ' ',
                    '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($key, 5))))
                );
                $out[ $key ] = $value;
            } else {
                $out[ $key ] = $value;
            }
        }

        return $out;
    }

    /**
     * Check if a given request has access to read a customer.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request)
    {
        $id = (int) $request['id'];

        if (get_current_user_id() != $id) {
            return new WP_Error(
                'frego_mobile_builder',
                __('Sorry, you cannot change info.', 'frego-mobile-builder'),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }
}