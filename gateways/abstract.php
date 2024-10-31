<?php

class ppp4woo_abstract extends WC_Payment_Gateway
{
    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = $this->getPaymentCode();
        $this->icon = $this->getIcon();
        $this->has_fields = true;
        $this->method_title = $this->getPaymentName().$this->getLabel();

        // Translators: This is the description for the payment method in the backend
        $this->method_description = sprintf(__('Enable this method to receive transactions with PPP - %s ', 'professional-payment-portal-for-woocommerce'), $this->getPaymentName());

        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_'.$this->id, [$this, 'process_admin_options']);

        // Set hooks for the return and webhook call
        add_action('woocommerce_api_wc_ppp_gateway_return', [$this, 'doReturn']);
        add_action('woocommerce_api_wc_ppp_gateway_notify', [$this, 'doNotify']);
    }

    /**
     * Get the icon for this payment method.
     *
     * @return string
     */
    public function getIcon()
    {
        $sGatewayCode = $this->getPaymentCode();

        if (file_exists(PPP4WOO_ROOT_PATH.'assets'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$sGatewayCode.'.png')) {
            return plugins_url('assets/images/'.$sGatewayCode.'.png', dirname(__FILE__));
        } elseif (file_exists(PPP4WOO_ROOT_PATH.'assets'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$sGatewayCode.'.svg')) {
            return plugins_url('assets/images/'.$sGatewayCode.'.svg', dirname(__FILE__));
        } else {
            return '';
        }
    }

    /**
     * Get the label for this payment method.
     *
     * @return string
     */
    public function getLabel()
    {
        return __(' via Professional Payment Portal', 'professional-payment-portal-for-woocommerce');
    }

    /**
     * Get the payment code for this payment method
     * Override this in gateway classes.
     *
     * @return string
     */
    public function getPaymentCode()
    {
        throw new Exception('Forgot the getPaymentCode method for this payment method?');
    }

    /**
     * Get the payment name for this payment method
     * Override this in gateway classes.
     *
     * @return string
     */
    public function getPaymentName()
    {
        throw new Exception('Forgot the getPaymentName method for this payment method?');
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        return true;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = [];

        $this->form_fields['enabled'] = [
                'title' => __('Enable/Disable', 'professional-payment-portal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PPP ', 'professional-payment-portal-for-woocommerce').' - '.$this->getPaymentName(),
                'default' => 'no',
            ];

        $this->form_fields['title'] = [
                'title' => __('Title', 'professional-payment-portal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'professional-payment-portal-for-woocommerce'),
                'default' => $this->getPaymentName(),
                'desc_tip' => true,
            ];

        $this->form_fields['description'] = [
                'title' => __('Customer Message after payment select', 'professional-payment-portal-for-woocommerce'),
                'type' => 'textarea',
                'default' => 'Pay with '.$this->getPaymentName(),
            ];
    }

    /**
     * Custom payment fields in checkout
     * Override this in your gateway class if needed.
     *
     * @return void
     */
    public function payment_fields()
    {
        $sPaymentDescription = $this->get_option('description');

        echo esc_html($sPaymentDescription);
    }

    public static function getMetadataUsertoken($iUserId)
    {
        return get_user_meta($iUserId, 'ppp_userToken', true);
    }

    public static function setMetadataUsertoken($iUserId, $sUserToken)
    {
        return update_user_meta($iUserId, 'ppp_userToken', $sUserToken);
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $sOrderId
     *
     * @return array|void
     */
    public function process_payment($sOrderId)
    {
        global $wpdb;
        global $woocommerce;

        $oOrder = wc_get_order($sOrderId);

        // Get all order related data
        $aOrderData = $oOrder->get_data();

        $sOrderNumber = $aOrderData['number'];

        // Order amount in Cents
        $fOrderAmount = $aOrderData['total'];
        $sCurrencyCode = $aOrderData['currency'];

        $sLanguageCode = get_bloginfo('language');

        $sReturnUrl = add_query_arg('wc-api', 'Wc_Ppp_Gateway_Return', home_url('/'));
        $sNotifyUrl = add_query_arg('wc-api', 'Wc_Ppp_Gateway_Notify', home_url('/'));

        // Setup message for order announcement
        $aRequest['timestamp'] = time();
        $aRequest['datetime'] = gmdate('c', time());

        $aRequest['currency'] = $sCurrencyCode;
        $aRequest['amount'] = $fOrderAmount;

        $aRequest['return_url'] = $sReturnUrl;
        $aRequest['report_url'] = $sNotifyUrl;

        $aRequest['payment_method'] = substr($this->getPaymentCode(), 8);

        $aRequest['order_id'] = $oOrder->get_id();
        $aRequest['order_number'] = $sOrderNumber;
        $aRequest['language'] = $sLanguageCode;
        $aRequest['ip_address'] = $aOrderData['customer_ip_address'];

        // Get stored usertoken, if user is logged in
        if ($iUserId = get_current_user_id()) {
            $sUserToken = self::getMetadataUsertoken($iUserId);

            if (!empty($sUserToken)) {
                $aRequest['additional_data']['user_token'] = $sUserToken;
                $aRequest['additional_data']['user_agent'] = wc_get_user_agent();
            }
        }

        $sApiUrl = 'https://codebrain-ppp.nl/api/v1/paymentjobs/';

        if ($sAccessToken = $this->getAccessToken()) {
            $sPostData = wp_json_encode($aRequest);
            $sSignature = hash_hmac('sha512', $sPostData, $sAccessToken);

            $aHeaders = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$this->get_option('api_key'), 'x-signature' => $sSignature];

            $aArguments = [];
            $aArguments['body'] = $sPostData;
            $aArguments['timeout'] = '10';
            $aArguments['redirection'] = '5';
            $aArguments['httpversion'] = '1.1';
            $aArguments['blocking'] = true;
            $aArguments['headers'] = $aHeaders;
            $aArguments['cookies'] = [];

            $oResponse = wp_remote_post($sApiUrl, $aArguments);
            $sResponse = wp_remote_retrieve_body($oResponse);
            $aHeaders = wp_remote_retrieve_headers($oResponse);

            if (!empty($sResponse)) {
                // Check hash
                $sSignature = $aHeaders['x-signature'];

                $sHash = hash_hmac('sha512', $sResponse, $sAccessToken);

                if (strcmp($sSignature, $sHash) === 0) {
                    $aPaymentJob = json_decode($sResponse, true);

                    if (is_array($aPaymentJob) && !empty($aPaymentJob)) {
                        if (isset($aPaymentJob['paymentJob']) && isset($aPaymentJob['pay_url'])) {
                            // Add note for chosen method:
                            $oOrder->add_order_note(__('iDEAL 2.0 payment started with:', 'professional-payment-portal-for-woocommerce').$this->getPaymentName().'<br>'.__('Using Payment Job:', 'professional-payment-portal-for-woocommerce').'<br>'.$aPaymentJob['paymentJob']);

                            // Update order meta
                            $oOrder->update_meta_data('ppp_transaction_id', $aPaymentJob['paymentJob']);
                            $oOrder->save();

                            if ($this->isRedirect()) {
                                return ['result' => 'success', 'redirect' => $aPaymentJob['pay_url']];
                            } else {
                                // Return thankyou redirect
                                return [
                                    'result' => 'success',
                                    'redirect' => $this->get_return_url($oOrder),
                                ];
                            }
                        } elseif (isset($aPaymentJob['error'])) {
                            $sErrorMessage = $aPaymentJob['error'];

                            $oOrder->add_order_note(printf(
                                /* translators: %s is replaced with the error message that is set in $sErrorMessage. */
                                esc_html__('PPP returned an error! Message: %s', 'professional-payment-portal-for-woocommerce'),
                                esc_html($sErrorMessage),
                            ));
                        }
                    } else {
                        wc_print_notice(__('Order announcement could not be decoded, something wrong with the data received?', 'professional-payment-portal-for-woocommerce'), 'error');
                    }
                } else {
                    wc_print_notice(__('Response could not be validated, please try again', 'professional-payment-portal-for-woocommerce'), 'error');
                }
            } else {
                wc_print_notice(__('No response received from the Rabobank.', 'professional-payment-portal-for-woocommerce'), 'error');
            }
        } else {
            $oOrder->add_order_note(__('Professional Payment Portal returned an error! No Token could be generated based on the API key.', 'professional-payment-portal-for-woocommerce'));
        }
    }

    /**
     * Process payment return from PPP.
     *
     * @return void
     */
    public function doReturn()
    {
        global $woocommerce;

        if (empty($_GET['order_number']) && empty($_GET['order_code']) && empty($_GET['payment_job']) && empty($_GET['signature'])) {
            wp_redirect($woocommerce->cart->get_cart_url());
        } else {
            $sOrderNumber = sanitize_text_field($_GET['order_number']);
            $sOrderCode = sanitize_text_field($_GET['order_code']);
            $sPaymentJob = sanitize_text_field($_GET['payment_job']);
            $sSignature = sanitize_text_field($_GET['signature']);
            $bUtmOverride = array_key_exists('utm_nooverride', $_GET);

            // Check paymentjob
            if (!ppp4woo_isUuid($sPaymentJob)) {
                wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
            }

            $oOrder = wc_get_order($sOrderNumber);

            // Check transaction ID of the order
            if (!$oOrder || ($oOrder->get_meta('ppp_transaction_id') !== $sPaymentJob)) {
                wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
            }

            $sAccessToken = $this->getAccessToken();
            $sHashString = $sOrderNumber.','.$sOrderCode.','.$sPaymentJob;

            $sHash = hash_hmac('sha512', $sHashString, $sAccessToken);

            if (hash_equals($sSignature, $sHash)) {
                // Do status pull
                $statusInfo = $this->getStatus($sPaymentJob);
                $sStatus = $statusInfo['status'];

                $sPaymentMethod = $oOrder->get_payment_method_title();

                if (isset($statusInfo['debtorInformation']['ContactDetails']) && isset($statusInfo['debtorInformation']['ShippingAddress']) && isset($statusInfo['debtorInformation']['BillingAddress'])) {
                    $contactDetails = $statusInfo['debtorInformation']['ContactDetails'];
                    $shippingAddress = $statusInfo['debtorInformation']['ShippingAddress'];
                    $billingAddress = $statusInfo['debtorInformation']['BillingAddress'];

                    $oOrder->set_billing_first_name($contactDetails['FirstName']);
                    $oOrder->set_billing_last_name($contactDetails['LastName']);
                    $oOrder->set_billing_email($contactDetails['Email']);
                    $oOrder->set_billing_phone($contactDetails['PhoneNumber']);

                    // Build street
                    $sBillingStreet = $billingAddress['StreetName'].' '.$billingAddress['BuildingNumber'];

                    if (!empty($billingAddress['Floor'])) {
                        $sBillingStreet .= ' '.$billingAddress['Floor'];
                    }

                    $oOrder->set_billing_address_1($sBillingStreet);
                    // Not sure what returns here
                    // $oOrder->set_billing_address_2($billingAddress['HouseExtension']);
                    $oOrder->set_billing_postcode($billingAddress['PostCode']);
                    $oOrder->set_billing_city($billingAddress['TownName']);
                    $oOrder->set_billing_country($billingAddress['Country']);

                    $oOrder->set_shipping_first_name($contactDetails['FirstName']);
                    $oOrder->set_shipping_last_name($contactDetails['LastName']);
                    $oOrder->set_shipping_phone($contactDetails['PhoneNumber']);

                    // Build street
                    $sShippingStreet = $shippingAddress['StreetName'].' '.$shippingAddress['BuildingNumber'];

                    if (!empty($shippingAddress['Floor'])) {
                        $sShippingStreet .= ' '.$shippingAddress['Floor'];
                    }

                    $oOrder->set_shipping_address_1($sShippingStreet);
                    // Not sure what returns here
                    // $oOrder->set_shipping_address_2($shippingAddress['HouseExtension']);
                    $oOrder->set_shipping_postcode($shippingAddress['PostCode']);
                    $oOrder->set_shipping_city($shippingAddress['TownName']);
                    $oOrder->set_shipping_country($shippingAddress['Country']);
                    $oOrder->save();
                }

                // self::setMetadataCustomerReference($oOrder->get_user_id(), $statusInfo['debtorInformation']['CustomerReference']);

                if (strcmp($sStatus, 'SUCCESS') === 0) {
                    $sReturnUrl = $oOrder->get_checkout_order_received_url();

                    $aPaymentStatuses = wc_get_is_paid_statuses();
                    $aPaymentStatuses[] = 'refunded';

                    if (!in_array($oOrder->get_status(), $aPaymentStatuses)) {
                        $oOrder->add_order_note(__('Status received from Customer Return:', 'professional-payment-portal-for-woocommerce').$sStatus.'. '.__('Order updated, check PPP dashboard for status before sending products', 'professional-payment-portal-for-woocommerce').'. '.__('Payment-method: ', 'professional-payment-portal-for-woocommerce').$sPaymentMethod);

                        $oOrder->payment_complete($sPaymentJob);
                    } else {
                        $oOrder->add_order_note(__('Status received from Customer Return:', 'professional-payment-portal-for-woocommerce').$sStatus.'. '.__('Payment status already received: Order status not updated, check PPP dashboard for status', 'professional-payment-portal-for-woocommerce').'. '.__('Payment-method: ', 'professional-payment-portal-for-woocommerce').$sPaymentMethod);
                    }

                    wp_redirect($sReturnUrl.($bUtmOverride ? '&utm_nooverride=1' : ''));
                } elseif (in_array($sStatus, ['CANCELLED', 'EXPIRED'])) {
                    // Check if WooCommerce cancels automaticly for the stock management
                    $iHoldStockMinutes = get_option('woocommerce_hold_stock_minutes');

                    if (!empty($iHoldStockMinutes) && ($iHoldStockMinutes > 0)) {
                        // Happens automaticly, we dont need to do anything
                        $sMessage = __('Payment is cancelled or expired, but will be cancelled automaticly by WooCommerce.', 'professional-payment-portal-for-woocommerce');
                        $oOrder->add_order_note($sMessage);

                        wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
                    } else {
                        if (strcmp($sStatus, 'EXPIRED') === 0) {
                            $sMessage = __('Update order status: Failed.', 'professional-payment-portal-for-woocommerce');
                            $oOrder->add_order_note($sMessage);

                            // $oOrder->update_status('failed');

                            wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
                        } elseif (strcmp($sStatus, 'CANCELLED') === 0) {
                            $sMessage = __('Update order status: Cancelled.', 'professional-payment-portal-for-woocommerce');
                            $oOrder->add_order_note($sMessage);

                            // $oOrder->update_status('cancelled');
                            wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
                        }
                    }
                } else {
                    // No final status
                    $sMessage = __('No Final status has been found.', 'professional-payment-portal-for-woocommerce');
                    $oOrder->add_order_note($sMessage);

                    wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
                }
            } else {
                wp_redirect(wc_get_cart_url().($bUtmOverride ? '&utm_nooverride=1' : ''));
            }
        }
    }

    /**
     * Process payment notification from PPP
     * Set status to received status.
     *
     * @return void
     */
    public function doNotify()
    {
        $sJsonData = @file_get_contents('php://input');

        if (empty($sJsonData)) {
            http_response_code(422);
            echo 'Invalid notification call';
        } else {
            $sAccessToken = $this->getAccessToken();
            $sHash = hash_hmac('sha512', $sJsonData, $sAccessToken);

            $aHeaders = ppp4woo_getHeaders();
            $sSignature = '';

            if (!empty($aHeaders['http_x_signature'])) {
                $sSignature = $aHeaders['http_x_signature'];
            }

            if (hash_equals($sSignature, $sHash)) {
                $aPostData = json_decode($sJsonData, true);
                $oOrder = wc_get_order(sanitize_text_field($aPostData['orderNumber']));

                if (empty($oOrder)) {
                    http_response_code(422);
                    echo 'Invalid order';
                    exit;
                }

                if (!ppp4woo_isUuid($aPostData['paymentJob'])) {
                    http_response_code(422);
                    echo 'Invalid paymentJob';
                    exit;
                }

                $sPaymentJob = sanitize_text_field($aPostData['paymentJob']);

                // Check transaction ID of the order
                if (!$oOrder || ($oOrder->get_meta('ppp_transaction_id') !== $sPaymentJob)) {
                    http_response_code(422);
                    echo 'Invalid transaction ID for this order';
                    exit;
                }

                // Do status pull
                $statusInfo = $this->getStatus($sPaymentJob);
                $sStatus = $statusInfo['status'];

                $sOrderStatus = $oOrder->get_status();

                $sMessage = __('Status received from PPP Webhook:', 'professional-payment-portal-for-woocommerce').$sStatus.__(' for Payment job:', 'professional-payment-portal-for-woocommerce').'<br>'.$sPaymentJob;
                $oOrder->add_order_note($sMessage);

                if (isset($aPostData['debtorInformation']['ContactDetails']) && isset($aPostData['debtorInformation']['ShippingAddress']) && isset($aPostData['debtorInformation']['BillingAddress'])) {
                    $contactDetails = sanitize_text_field($aPostData['debtorInformation']['ContactDetails']);
                    $shippingAddress = sanitize_text_field($aPostData['debtorInformation']['ShippingAddress']);
                    $billingAddress = sanitize_text_field($aPostData['debtorInformation']['BillingAddress']);

                    $oOrder->set_billing_first_name($contactDetails['FirstName']);
                    $oOrder->set_billing_last_name($contactDetails['LastName']);
                    $oOrder->set_billing_email($contactDetails['Email']);
                    $oOrder->set_billing_phone($contactDetails['PhoneNumber']);

                    // Build street
                    $sBillingStreet = $billingAddress['StreetName'].' '.$billingAddress['BuildingNumber'];

                    if (!empty($billingAddress['Floor'])) {
                        $sBillingStreet .= ' '.$billingAddress['Floor'];
                    }

                    $oOrder->set_billing_address_1($sBillingStreet);
                    // Not sure what returns here
                    // $oOrder->set_billing_address_2($billingAddress['HouseExtension']);
                    $oOrder->set_billing_postcode($billingAddress['PostCode']);
                    $oOrder->set_billing_city($billingAddress['TownName']);
                    $oOrder->set_billing_country($billingAddress['Country']);

                    $oOrder->set_shipping_first_name($contactDetails['FirstName']);
                    $oOrder->set_shipping_last_name($contactDetails['LastName']);
                    $oOrder->set_shipping_phone($contactDetails['PhoneNumber']);

                    // Build street
                    $sShippingStreet = $shippingAddress['StreetName'].' '.$shippingAddress['BuildingNumber'];

                    if (!empty($shippingAddress['Floor'])) {
                        $sShippingStreet .= ' '.$shippingAddress['Floor'];
                    }

                    $oOrder->set_shipping_address_1($sShippingStreet);
                    // Not sure what returns here
                    // $oOrder->set_shipping_address_2($shippingAddress['HouseExtension']);
                    $oOrder->set_shipping_postcode($shippingAddress['PostCode']);
                    $oOrder->set_shipping_city($shippingAddress['TownName']);
                    $oOrder->set_shipping_country($shippingAddress['Country']);
                    $oOrder->save();
                }

                if (in_array($sOrderStatus, ['pending', 'failed', 'cancelled'])) {
                    if (strcmp($sStatus, 'SUCCESS') === 0) {
                        $aPaymentStatuses = wc_get_is_paid_statuses();
                        $aPaymentStatuses[] = 'refunded';

                        if (!in_array($sOrderStatus, $aPaymentStatuses)) {
                            $sMessage = __('Update order status: Payment Completed', 'professional-payment-portal-for-woocommerce');
                            $oOrder->add_order_note($sMessage);
                            $oOrder->payment_complete($sPaymentJob);
                        } else {
                            $sMessage = __('Order status not updated: Payment status already received', 'professional-payment-portal-for-woocommerce');
                            $oOrder->add_order_note($sMessage);
                        }
                    } elseif (in_array($sStatus, ['CANCELLED', 'EXPIRED'])) {
                        // Check if WooCommerce cancels automaticly for the stock management
                        $iHoldStockMinutes = get_option('woocommerce_hold_stock_minutes');

                        if (!empty($iHoldStockMinutes) && ($iHoldStockMinutes > 0)) {
                            // Happens automaticly, we dont need to do anything
                            $sMessage = __('Payment is cancelled or expired, but will be cancelled automaticly by WooCommerce.', 'professional-payment-portal-for-woocommerce');
                            $oOrder->add_order_note($sMessage);
                        } else {
                            if (strcmp($sStatus, 'EXPIRED') === 0) {
                                $sMessage = __('Update order status: Failed.', 'professional-payment-portal-for-woocommerce');
                                $oOrder->add_order_note($sMessage);

                                $oOrder->update_status('failed');
                            } elseif (strcmp($sStatus, 'CANCELLED') === 0) {
                                $sMessage = __('Update order status: Cancelled.', 'professional-payment-portal-for-woocommerce');
                                $oOrder->add_order_note($sMessage);

                                $oOrder->update_status('cancelled');
                            } else {
                                // Possibly another status to be implemented?
                                $oOrder->add_order_note($sMessage);
                            }
                        }
                    } else {
                        // No final status
                        $sMessage = __('No Final status has been found.', 'professional-payment-portal-for-woocommerce');
                        $oOrder->add_order_note($sMessage);
                    }
                } else { // pending
                    if ($sPaymentJob && (strcmp($sStatus, 'SUCCESS') === 0)) {
                        $sMessage = __('Payment Job updated.', 'professional-payment-portal-for-woocommerce');
                        $oOrder->add_order_note($sMessage);
                        $oOrder->set_transaction_id($sPaymentJob);
                        $oOrder->save();
                    } else {
                        $sMessage = __('Order doesnt have the correct status to be changed by the payment method.', 'professional-payment-portal-for-woocommerce');
                        $oOrder->add_order_note($sMessage);
                    }
                }
            } else {
                http_response_code(422);
                echo 'Invalid signature';
                exit;
            }
        }
    }

    /**
     * Get access token from cache or from API.
     *
     * @return string token
     */
    private function getAccessToken()
    {
        global $wp_filesystem;

        if (!$wp_filesystem) {
            require_once ABSPATH.'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $sCacheFile = false;
        $sCachePath = PPP4WOO_ROOT_PATH.'cache'.DIRECTORY_SEPARATOR;

        // Get current site id
        $iSiteId = get_current_blog_id();

        // Used cached access token?
        if ($sCachePath) {
            $sStoreHost = md5($_SERVER['SERVER_NAME']);
            $sCacheFile = $sCachePath.'token.'.$sStoreHost.'-'.$iSiteId.'.cache';

            if (!$wp_filesystem->exists($sCacheFile)) {
                // Attempt to create cache file
                if ($wp_filesystem->touch($sCacheFile)) {
                    $wp_filesystem->chmod($sCacheFile, 0600);
                }
            } elseif ($wp_filesystem->is_readable($sCacheFile) && $wp_filesystem->is_writable($sCacheFile)) {
                // Read data from cache file
                if ($sData = $wp_filesystem->get_contents($sCacheFile)) {
                    $aToken = json_decode($sData, true);

                    // Get current time to compare expiration of the access token
                    $sCurrentTimestamp = time();

                    if (isset($aToken['expiration'])) {
                        // Change the valid until ISO notation to UNIX timestamp
                        $sExpirationTimestamp = $aToken['expiration'];

                        if ($sCurrentTimestamp <= $sExpirationTimestamp) {
                            return $aToken['token'];
                        }
                    }
                }
            } else {
                $sCacheFile = false;
            }
        }

        $sApiUrl = 'https://codebrain-ppp.nl/api/v1/guardian';

        $aArguments = [];
        $aArguments['body'] = '';
        $aArguments['timeout'] = '10';
        $aArguments['redirection'] = '5';
        $aArguments['httpversion'] = '1.1';
        $aArguments['blocking'] = true;
        $aArguments['headers'] = ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$this->get_option('api_key')];
        $aArguments['cookies'] = [];

        $oResponse = wp_remote_get($sApiUrl, $aArguments);
        $sResponse = wp_remote_retrieve_body($oResponse);

        if (!empty($sResponse)) {
            if ($aToken = json_decode($sResponse, true)) {
                if (isset($aToken['token']) && isset($aToken['expiration'])) {
                    // Save data in cache?
                    if ($sCacheFile) {
                        $wp_filesystem->put_contents($sCacheFile, wp_json_encode($aToken));
                    }

                    return $aToken['token'];
                }
            }
        }

        return false;
    }

    /**
     * Get payment status from API.
     *
     * @param string $sPaymentJobId
     *
     * @return array|bool paymentInfo including ['status']|false
     */
    private function getStatus($sPaymentJobId)
    {
        $sAccessToken = $this->getAccessToken();

        if (!empty($sAccessToken)) {
            $sApiUrl = 'https://codebrain-ppp.nl/api/v1/paymentjobs/'.$sPaymentJobId;

            $aArguments = [];
            $aArguments['body'] = '';
            $aArguments['timeout'] = '10';
            $aArguments['redirection'] = '5';
            $aArguments['httpversion'] = '1.1';
            $aArguments['blocking'] = true;
            $aArguments['headers'] = ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$this->get_option('api_key')];
            $aArguments['cookies'] = [];

            $oResponse = wp_remote_get($sApiUrl, $aArguments);
            $sResponse = wp_remote_retrieve_body($oResponse);
            $aHeaders = wp_remote_retrieve_headers($oResponse);

            if (!empty($sResponse)) {
                // Check hash
                $sSignature = $aHeaders['x-signature'];

                $sHash = hash_hmac('sha512', $sResponse, $sAccessToken);

                if (strcmp($sSignature, $sHash) === 0) {
                    $aPaymentJob = json_decode($sResponse, true);

                    if (isset($aPaymentJob['status'])) {
                        return $aPaymentJob;
                    }
                }
            }
        }

        return false;
    }
}
