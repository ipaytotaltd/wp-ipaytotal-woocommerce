<?php 
/**
* @since 1.0.0
* @package wp-ipaytotal-woocommerce
* @author iPayTotal Ltd
* 
* Plugin Name: iPayTotal - WooCommerce Payment Gateway
* Plugin URI: https://ipaytotal.com/contact
* Description: WooCommerce custom payment gateway integration with iPayTotal.
* Version: 2.0.1
* Author: iPayTotal
* Author URI: https://ipaytotal.com/ipaytotal-high-risk-merchant-account/
* License: GNU General Public License v2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: wp-ipaytotal-woocommerce
* Domain Path: /languages/
* WC requires at least: 3.0.0 
* WC tested up to: 4.9.8 
*/
 

/**
 * Tell WordPress to load a translation file if it exists for the user's language
 */
function wowp_iptwpg_load_plugin_textdomain() {
    load_plugin_textdomain( 'wp-ipaytotal-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


add_action( 'plugins_loaded', 'wowp_iptwpg_load_plugin_textdomain' );


function wowp_iptwpg_ipaytotal_init() {
    //if condition use to do nothin while WooCommerce is not installed
	if ( ! class_exists( 'WC_Payment_Gateway_CC' ) ) return;
	include_once( 'includes/wp-ipaytotal-woocommerce-admin.php' );
	include_once( 'includes/wp-ipaytotal-woocommerce-api.php' );
	// class add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'wowp_iptwpg_add_ipaytotal_gateway' );
	function wowp_iptwpg_add_ipaytotal_gateway( $methods ) {
		$methods[] = 'wowp_iptwpg_ipaytotal';
		return $methods;
	}
}


add_action( 'plugins_loaded', 'wowp_iptwpg_ipaytotal_init', 0 );


/**
* Add custom action links
*/
function wowp_iptwpg_ipaytotal_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'wp-ipaytotal-woocommerce' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wowp_iptwpg_ipaytotal_action_links' );


/**
* Customize credict card form
*/
function wowp_iptwpg_ipaytotal_custom_credit_card_fields ($cc_fields , $payment_id){
	$new_fields = array(
	 'card-name-field' => '<p class="form-row form-row-wide"><label for="' . esc_attr( $payment_id ) . '-card-name">'
	 		. __( 'Cardholder Name', 'wp-ipaytotal-woocommerce' ) . ' <span class="required">*</span>
	 	</label>
	 	<input id="' . esc_attr( $payment_id ) . '-card-name" class="input-text wc-credit-card-form-card-name" type="text" maxlength="30" autocomplete="off" placeholder="' . __('CARDHOLDER NAME', 'wp-ipaytotal-woocommerce') . '" name="' . esc_attr( $payment_id ) . '-card-name' . '" />
	 </p>',
	 'card-number-field' => '<p class="form-row form-row-wide"><label for="' . esc_attr( $payment_id ) . '-card-number">'
	 		. __( 'Card Number', 'wp-ipaytotal-woocommerce' ) . ' <span class="required">*</span>
	 	</label>
	 	<input id="' . esc_attr( $payment_id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="' . esc_attr( $payment_id ) . '-card-number' . '" />
	 </p>',
	 'card-expiry-field' => '<p class="form-row form-row-first"><label for="' . esc_attr( $payment_id ) . '-card-expiry">'
	 		. __( 'Expiry (MM/YYYY)', 'wp-ipaytotal-woocommerce' ) . ' <span class="required">*</span>
	 	</label>
	 	<input id="' . esc_attr( $payment_id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . __('MM / YYYY', 'wp-ipaytotal-woocommerce') . '" name="' . esc_attr( $payment_id ) . '-card-expiry' . '" />
	 </p>',
	 'card-cvc-field' => '<p class="form-row form-row-last"><label for="' . esc_attr( $payment_id ) . '-card-cvc">'
	 		. __( 'Card Code', 'wp-ipaytotal-woocommerce' ) . ' <span class="required">*</span>
	 	</label>
	 	<input id="' . esc_attr( $payment_id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc"inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVV" style="width:100px" name="' . esc_attr( $payment_id ) . '-card-cvc' . '" />
	 </p>'
	);

	return $new_fields;
}

add_filter( 'woocommerce_credit_card_form_fields' , 'wowp_iptwpg_ipaytotal_custom_credit_card_fields' , 10, 2 );
