
(() => {
    "use strict";

    const React = window.React;
    const wcBlocksRegistry = window.wc.wcBlocksRegistry;
    const i18n = window.wp.i18n;
    const wcSettings = window.wc.wcSettings;
    const htmlEntities = window.wp.htmlEntities;

    const settings = wcSettings.getSetting( 'ppp4woo_ideal_data', {} );
    const label = htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'iDEAL', 'professional-payment-portal-for-woocommerce' );
    
    const decodeDescription = () => htmlEntities.decodeEntities(settings.description || "");

    const paymentMethod = {
        name: 'ppp4woo_ideal',
        label: React.createElement("div", {
            style: {
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                width: '95%',
            }
        }, [
            React.createElement("span", { key: 'label' }, label),
            React.createElement("img", {
                src: htmlEntities.decodeEntities(settings.icon),
                alt: label,
                key: 'icon'
            }),
        ]),
        placeOrderButtonLabel: i18n.__('Proceed to iDEAL', 'professional-payment-portal-for-woocommerce'),
        content: React.createElement(decodeDescription, null),
        edit: React.createElement(decodeDescription, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };
    
    wcBlocksRegistry.registerPaymentMethod( paymentMethod );
})();