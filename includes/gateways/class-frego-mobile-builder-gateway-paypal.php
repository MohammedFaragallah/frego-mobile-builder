<?php

/**
 * REST API endpoint for WooCommerce Payment via PayPal Standard.
 *
 * @see https://docs.woocommerce.com/document/paypal-standard/ PayPal Standard
 *
 * @author Ngoc Dang
 */
class Mobile_Builder_Gateway_PayPal
{




    /**
     * The ID of the corresponding WooCommerce Payment Gateway.
     *
     * @var string the ID of the corresponding Gateway
     *
     * @see https://github.com/woocommerce/woocommerce/blob/master/includes/gateways/paypal/class-wc-gateway-paypal.php#L40 Gateway ID
     *
     * @author Ngoc Dang
     */
    public $gateway_id = 'paypal';

    /**
     * The version of this plugin.
     *
     * @var string the current version of corresponding Gateway
     *
     * @see https://github.com/woocommerce/woocommerce/blob/master/includes/gateways/paypal/class-wc-gateway-paypal.php#L9 Gateway Version
     *
     * @author Ngoc Dang
     */
    private $gateway_version = '2.3.0';

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
        $parameters['pre_process_result'] = true;

        return $parameters;
    }
}