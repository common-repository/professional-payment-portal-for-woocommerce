=== Professional Payment Portal for WooCommerce ===
Contributors: codebrainbv
Tags: professional, rabobank, payment, ideal 2, woocommerce
Requires at least: 6.4
Tested up to: 6.6.1
Stable tag: 1.0.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

One of the easiest and best ways to integration Rabobank in your WooCommerce webshop!

== Description ==

You want a lot of freedom and the possibility to integrate iDEAL in your webshop or your own online checkout. In addition, you receive a lot of payments in your webshop or you want to have your online services paid for with iDEAL. Then Rabo iDEAL Professional is ideal.


**What is Rabo iDEAL Professional?**

You can seamlessly integrate iDEAL Professional into your webshop or your own online checkout. Ideal if you receive many payments through your webshop or if you want your customers to pay for your online services. The customer is recognized via an iDEAL profile, which means that your customers can quickly and easily make the payment via their own trusted bank. You immediately receive a payment guarantee (push) and you receive the payment quickly, usually the same day on your account.

**The advantages of Rabo iDEAL Professional**

* Most used online payment method in the Netherlands
* The money is quickly in your account
* The customer can specify a preferred account via an iDEAL profile. When making a payment, the customer is recognized and immediately forwarded to their own payment environment.
* Use of iDEAL Professional Dashboard

**What do you need?**

* A Rabo iDEAL Professional contract. You can easily request this via https://ideal.rabobank.nl/
* The WooCommerce Rabo Professional plugin
* A free CodeBrain PPP account, this can be created at: https://codebrain-ppp.nl

**This plugin uses a 3rd party for the API connection.**
We communicate with the Professional Payment Portal to process the payments. This is a secure connection and we use the latest encryption methods to ensure that your data is safe. No user data is stored on our servers, we only use the data to process the payment and to check the status of the payment.
More information is found at https://codebrain-ppp.nl.
Privacy Policy: https://codebrain-ppp.nl/privacy-policy

== Installation ==

1. Upload the zip file in the wordpress backend plugin uploader (Plugins -> Add new plugin) and click on the "Upload plugin" button, or use the Wordpress store.
2. Activate the plug-in via the 'plugins' screen in WordPress or during the installation.
3. Navigate to WooCommerce/Settings -> Payments and configure the plug-in/payment method, or click on Settings when viewing the plugin in the Installed plug-ins list.

## Configuration
1. Enter the API Key provided by Professional Payment Portal https://codebrain-ppp.nl.
2. Enable the iDEAL payment method.
3. Save

## iDEAL Dashboard Configuration - Certificate

1. Download the Certificate found on the Professional Payment Portal https://codebrain-ppp.nl.
2. Navigate to https://ideal.rabobank.nl and login with your credentials.
3. Click on "Merchant data" -> "iDEAL Service data" and then scroll down to "Certificates".
4. Click on "Certificates" and upload the downloaded certificate from step 1.

## iDEAL Dashboard Configuration - Webhook

1. Navigate to https://ideal.rabobank.nl and login with your credentials.
2. Click on "Merchant data" -> "iDEAL Service data" and then scroll down to "Specific details".
3. There are 2 fields on the right that we need to fill out, these are called the "Status Notification URL" and "Notification BearerToken".
4. In field "Status Notification URL" enter your webshop URL and add /v3 to the end (example: https://webshop.nl/v3).
5. In field "Notification BearerToken" you need to enter the API key that you have also placed in the plug-in.


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.0.2 =
* Removed fast checkout because it was causing issues for merchants, will reintroduce when fixed.
* Fixed issue with the shipping cost

== Upgrade Notice ==


== Arbitrary section ==

**Features**

* Payment Methods: iDEAL
* Easy to use dashboard
* Automatic webhook messages for processing transactions/orders

**Security**

* Uses Rabobank SHA256 encryption method
* SSL/TLS supported
* Signs and checks every message to and from Rabobank
* Secure webhook supported
* oAuth implemented
