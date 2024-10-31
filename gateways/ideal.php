<?php

class ppp4woo_ideal extends ppp4woo_abstract
{
    public function getPaymentCode()
    {
        return 'ppp4woo_ideal';
    }

    public function getPaymentName()
    {
        return 'iDEAL';
    }

    public function init_form_fields()
    {
        $this->form_fields = [];

        $this->form_fields['enabled'] = [
                'title' => __('Enable/Disable', 'professional-payment-portal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PPP ', 'professional-payment-portal-for-woocommerce').' - '.$this->getPaymentName(),
                'default' => 'no',
        ];

        $this->form_fields['api_key'] = [
                'title' => __('API Key', 'professional-payment-portal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('The API Key can be found on the Professional Payment Portal dashboard.', 'professional-payment-portal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
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

        $this->form_fields['usertoken'] = [
            'title' => __('Usertokens', 'professional-payment-portal-for-woocommerce'),
            'type' => 'checkbox',
            'label' => __('Enable the use of UserTokens to allow customers to pay faster ', 'professional-payment-portal-for-woocommerce'),
            'default' => 'no',
        ];
    }
}
