<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Ppp4woo_Ideal_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'ppp4woo_ideal'; // your payment gateway name

    public function initialize()
    {
        $this->settings = get_option('woocommerce_ppp4woo_ideal_settings', []);
        $this->gateway = new ppp4woo_ideal();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'ppp4woo_ideal-blocks-integration',
            PPP4WOO_ROOT_URL.'assets/js/ideal.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('ppp4woo_ideal-blocks-integration');
        }

        return ['ppp4woo_ideal-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->method_title,
            'supports' => $this->gateway->supports,
            'icon' => $this->gateway->icon,
            'description' => $this->gateway->description,
        ];
    }
}
