<?php

/**
 * REST API endpoint for WooCommerce Payment via Razorpay Standard.
 *
 * @author     Ngoc Dang
 */
class Mobile_Builder_Gateway_Razorpay
{




    /**
     * The ID of the corresponding WooCommerce Payment Gateway.
     *
     * @var string the ID of the corresponding Gateway
     *
     * @author Ngoc Dang
     */
    public $gateway_id = 'razorpay';

    /**
     * The version of this plugin.
     *
     * @var string the current version of corresponding Gateway
     *
     * @author Ngoc Dang
     */
    private $gateway_version = '2.3.1';

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name the name of the plugin
     * @param string $version     the version of this plugin
     */
    public function __construct()
    {
    }

    /**
     * Add Action to precheck, prepare params before the Gateway calls 'process_payment'.
     *
     * @author Ngoc Dang
     *
     * @param mixed $parameters
     */
    public function pre_process_payment($parameters)
    {
        WC()->session = new WC_Session_Handler();
        WC()->session->init();

        // $_POST['payment_method'] = $this->gateway_id;

        // do_action('woocommerce_api_' . $this->gateway_id);

        $parameters['pre_process_result'] = true;

        return $parameters;
    }
}