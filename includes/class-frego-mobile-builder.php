<?php

/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class Mobile_Builder
{




    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var Mobile_Builder_Loader maintains and registers all hooks for the plugin
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string the string used to uniquely identify this plugin
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string the current version of the plugin
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function __construct()
    {
        $this->version    = MOBILE_BUILDER_CONTROL_VERSION;
        $this->plugin_name = MOBILE_BUILDER_PLUGIN_NAME;

        $this->load_dependencies();
        $this->set_locale();
        // $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_product_hooks();
        $this->define_api_hooks();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string the name of the plugin
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Mobile_Builder_Loader orchestrates the hooks of the plugin
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string the version number of the plugin
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Mobile_Builder_Loader. Orchestrates the hooks of the plugin.
     * - Mobile_Builder_i18n. Defines internationalization functionality.
     * - Mobile_Builder_Admin. Defines all hooks for the admin area.
     * - Mobile_Builder_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     */
    private function load_dependencies()
    {
        /**
           * load frego functions.
           */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'includes/functions.php';
        /**
         * Load function.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'helpers/frego-mobile-builder-functions.php';

        /**
         * Load dependency install by composer.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-frego-mobile-builder-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-frego-mobile-builder-i18n.php';

        /**
         * The class responsible for loading payment gateways.
         *
         * @author Ngoc Dang
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'includes/gateways/class-frego-mobile-builder-gateway-paypal.php';
        include_once plugin_dir_path(dirname(__FILE__)) .
            'includes/gateways/class-frego-mobile-builder-gateway-razorpay.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'admin/class-frego-mobile-builder-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'public/class-frego-mobile-builder-public.php';

        /**
         * The class responsible for defining all actions that occur in the product-facing
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'product/class-frego-mobile-builder-product.php';

        /**
         * The class responsible for defining all actions that occur in the api
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'api/class-frego-mobile-builder-auth.php';
        include_once plugin_dir_path(dirname(__FILE__)) .
            'api/class-frego-mobile-builder-cart.php';
        include_once plugin_dir_path(dirname(__FILE__)) .
            'api/class-frego-mobile-builder-wcfm.php';
        include_once plugin_dir_path(dirname(__FILE__)) .
            'api/class-frego-mobile-builder-products.php';

        /**
         * Load library.
         */
        include_once plugin_dir_path(dirname(__FILE__)) .
            'includes/libraries/class-frego-mobile-builder-public-key.php';

        $this->loader = new Mobile_Builder_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Mobile_Builder_i18n class in order to set the domain and to register the hook
     * with WordPress.
     */
    private function set_locale()
    {
        $plugin_i18n = new Mobile_Builder_i18n();

        $this->loader->add_action(
            'plugins_loaded',
            $plugin_i18n,
            'load_plugin_textdomain'
        );
    }

    private function define_api_hooks()
    {
        // Auth
        $plugin_auth = new Mobile_Builder_Auth(
            $this->get_plugin_name(),
            $this->get_version()
        );
        $this->loader->add_action(
            'rest_api_init',
            $plugin_auth,
            'add_api_routes',
            10
        );

        // Cart
        // $plugin_cart = new Mobile_Builder_Cart(
        //     $this->get_plugin_name(),
        //     $this->get_version()
        // );
        // $this->loader->add_action(
        //     'rest_api_init',
        //     $plugin_cart,
        //     'add_api_routes',
        //     10
        // );
        // $this->loader->add_action(
        //     'wp_loaded',
        //     $plugin_cart,
        //     'mobile_builder_pre_car_rest_api',
        //     5
        // );
        // $this->loader->add_filter(
        //     'woocommerce_persistent_cart_enabled',
        //     $plugin_cart,
        //     'mobile_builder_woocommerce_persistent_cart_enabled'
        // );
        // $this->loader->add_action(
        //     'woocommerce_load_cart_from_session',
        //     $plugin_cart,
        //     'load_cart_action',
        //     10
        // );
        // $this->loader->add_action(
        //     'woocommerce_thankyou',
        //     $plugin_cart,
        //     'handle_checkout_success',
        //     10
        // );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks()
    {
        $plugin_public = new Mobile_Builder_Public(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $gateways = [];

        // // Payment Gateway via PayPal Standard
        // $gateway_paypal = new Mobile_Builder_Gateway_PayPal();
        // array_push($gateways, $gateway_paypal);

        // // Payment Gateway via Razorpay Standard
        // $gateway_razorpay = new Mobile_Builder_Gateway_Razorpay();
        // array_push($gateways, $gateway_razorpay);

        // Register Payment Endpoint for all Gateways
        foreach ($gateways as &$gateway) {
            $this->loader->add_filter(
                'pre_process_' . $gateway->gateway_id . '_payment',
                $gateway,
                'pre_process_payment'
            );
        }

        $this->loader->add_action(
            'rest_api_init',
            $plugin_public,
            'add_api_routes'
        );
        $this->loader->add_filter(
            'determine_current_user',
            $plugin_public,
            'determine_current_user'
        );

        // Filter locate template
        // $this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'woocommerce_locate_template', 100, 3 );

        // Add style for checkout page
        $this->loader->add_action(
            'wp_enqueue_scripts',
            $plugin_public,
            'enqueue_styles'
        );

        // Filter token digits
        $this->loader->add_filter(
            'digits_rest_token_data',
            $plugin_public,
            'custom_digits_rest_token_data',
            100,
            2
        );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_product_hooks()
    {
        // $plugin_product = new Mobile_Builder_Product(
        //     $this->get_plugin_name(),
        //     $this->get_version()
        // );

        // $this->loader->add_action(
        //     'rest_api_init',
        //     $plugin_product,
        //     'add_api_routes'
        // );

        // // Product variation
        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_product_variation_object',
        //     $plugin_product,
        //     'custom_woocommerce_rest_prepare_product_variation_object'
        // );

        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_product_variation_object',
        //     $plugin_product,
        //     'prepare_product_variation_images',
        //     10,
        //     3
        // );

        // // Product
        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_product_object',
        //     $plugin_product,
        //     'custom_change_product_response',
        //     20,
        //     3
        // );

        // // Category
        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_product_cat',
        //     $plugin_product,
        //     'custom_change_product_cat',
        //     20,
        //     3
        // );

        // // Blog
        // $this->loader->add_filter(
        //     'the_title',
        //     $plugin_product,
        //     'custom_the_title',
        //     20,
        //     3
        // );

        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_product_object',
        //     $plugin_product,
        //     'prepare_product_images',
        //     30,
        //     3
        // );

        // // Product Attribute
        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_product_attribute',
        //     $plugin_product,
        //     'custom_woocommerce_rest_prepare_product_attribute',
        //     10,
        //     3
        // );

        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_pa_color',
        //     $plugin_product,
        //     'add_value_pa_color'
        // );
        // $this->loader->add_filter(
        //     'woocommerce_rest_prepare_pa_image',
        //     $plugin_product,
        //     'add_value_pa_image'
        // );

        // $this->loader->add_filter(
        //     'wcml_client_currency',
        //     $plugin_product,
        //     'mbd_wcml_client_currency'
        // );
    }
}