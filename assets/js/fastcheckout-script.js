jQuery(
	function (jQuery) {
		jQuery("a.single_add_to_cart_button.fastcheckout_button").on(
			'click',
			function ()
			{
				if(fastcheckout_params.page_name === "cart") 
                {
					jQuery('div.wc-proceed-to-checkout').block(
						{
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						}
					);

					if('yes' === fastcheckout_params.wpc_plugin) 
                    {
						location.href = jQuery('.fastcheckout_button').attr( 'href' );
					}
				} 
                else 
                {

					jQuery('div.eh_payapal_express_checkout_button').block(
						{
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						}
					);
				}
			}
		);
		jQuery("span.edit_eh_pe_address").on(
			'click',
			function ()
			{
					jQuery(".woocommerce-billing-fields p").removeClass("eh_pe_checkout_fields_hide");
					jQuery(".woocommerce-billing-fields p").removeClass("eh_pe_checkout_fields_fill");
					jQuery(".woocommerce-billing-fields .eh_pe_address").hide();
			}
		);
	}
);
