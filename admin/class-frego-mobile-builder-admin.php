<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Mobile_Builder_Admin
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
     * @param string $plugin_name the name of this plugin
     * @param string $version     the version of this plugin
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles()
    {
        /*
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Mobile_Builder_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Mobile_Builder_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts()
    {
        /**
        * This function is provided for demonstration purposes only.
        *
        * An instance of this class should be passed to the run() function
        * defined in Wp_Auth_Loader as all of the hooks are defined
        * in that particular class.
        *
        * The Wp_Auth_Loader will then create the relationship
        * between the defined hooks and the functions defined in this
        * class.
        */
        wp_enqueue_media();
    }

    /**
     * Registers a REST API route.
     */
    public function add_api_routes()
    {
    }

    /**
     * @return null|array|object
     */
    public function template_configs()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . MOBILE_BUILDER_TABLE_NAME;

        return $wpdb->get_results("SELECT * FROM {$table_name}", OBJECT);
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function get_template_config($request)
    {
        return new WP_REST_Response($this->template_configs(), 200);
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function add_template_config($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . MOBILE_BUILDER_TABLE_NAME;

        $data = $request->get_param('data');

        $results = $wpdb->insert($table_name, $data);

        return new WP_REST_Response($results, 200);
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function update_template_config($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . MOBILE_BUILDER_TABLE_NAME;

        $data  = $request->get_param('data');
        $where = $request->get_param('where');

        $results = $wpdb->update($table_name, $data, $where);

        return new WP_REST_Response($results, 200);
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function delete_template_config($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . MOBILE_BUILDER_TABLE_NAME;

        $where = $request->get_param('where');

        $results = $wpdb->delete($table_name, $where);

        return new WP_REST_Response($results, 200);
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function get_configs($request)
    {
        $configs = get_option(
            'mobile_builder_configs',
            [
                'requireLogin'       => false,
                'toggleSidebar'      => false,
                'isBeforeNewProduct' => 5,
            ]
        );

        return new WP_REST_Response(maybe_unserialize($configs), 200);
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function update_configs($request)
    {
        $data = $request->get_param('data');

        if (get_option('mobile_builder_configs')) {
            $status = update_option(
                'mobile_builder_configs',
                maybe_serialize($data)
            );
        } else {
            $status = add_option(
                'mobile_builder_configs',
                maybe_serialize($data)
            );
        }

        return new WP_REST_Response([ 'status' => $status ], 200);
    }

    /**
     * Add license code.
     *
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function add_license($request)
    {
        $license = $request->get_param('data');

        delete_option('mobile_builder_license');
        $status = add_option(
            'mobile_builder_license',
            maybe_serialize($license)
        );

        return new WP_REST_Response([ 'status' => $status ], 200);
    }

    /**
     * @param $request
     *
     * @return mixed
     */
    public function admin_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_plugin_admin_menu()
    {
        // Add a settings page for this plugin to the Settings menu.
        $hook_suffix = add_options_page(
            __('Mobile Builder', $this->plugin_name),
            __('Mobile Builder', $this->plugin_name),
            'manage_options',
            $this->plugin_name,
            [ $this, 'display_plugin_admin_page' ]
        );

        $hook_suffix = add_menu_page(
            __('Mobile Builder', $this->plugin_name),
            __('Mobile Builder', $this->plugin_name),
            'manage_options',
            $this->plugin_name,
            [ $this, 'display_plugin_admin_page' ],
            'dashicons-excerpt-view'
        );

        // Load enqueue styles and script
        add_action(
            "admin_print_styles-{$hook_suffix}",
            [
                $this,
                'enqueue_styles',
            ]
        );
        add_action(
            "admin_print_scripts-{$hook_suffix}",
            [
                $this,
                'enqueue_scripts',
            ]
        );
    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_admin_page()
    {
        ?>
<?php
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @param mixed $links
     */
    public function add_plugin_action_links($links)
    {
        return array_merge(
            [
                'settings' => '<a href="' .
                    admin_url(
                        'options-general.php?page=' . $this->plugin_name
                    ) .
                    '">' .
                    __('Settings', $this->plugin_name) .
                    '</a>',
            ],
            $links
        );
    }
}