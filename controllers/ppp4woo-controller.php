<?php

class Ppp4wooController
{
    /**
     * Flag to check if the plugin has been initiated.
     */
    private static $bInitiated = false;

    /**
     * Initiate the plugin.
     */
    public static function init()
    {
        if (self::$bInitiated) {
            return;
        }

        // Set hook for iDEAL Fast checkout
        add_action('woocommerce_api_wc_ppp_fastcheckout', ['ppp4woo_ideal_fast', 'doFastCheckout']);

        // Mark plugin initiated
        self::$bInitiated = true;
    }
}
