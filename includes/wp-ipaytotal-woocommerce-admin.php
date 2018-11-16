<?php
/**
* @package wp-ipaytotal-woocommerce
* @author  iPayTotal Ltd
* @since   1.0.0
 */

class wowp_iptwpg_ipaytotal extends WC_Payment_Gateway_CC {

	function __construct() {

		// global ID
		$this->id = "wowp_iptwpg_ipaytotal";

		// Show Title
		$this->method_title = __( "Credit/Debit Card", 'wp-ipaytotal-woocommerce' );

		// Show Description
		$this->method_description = __( "IPayTotal Payment Gateway Plug-in for WooCommerce", 'wp-ipaytotal-woocommerce' );

		// vertical tab title
		$this->title = __( "Credit/Debit Card", 'wp-ipaytotal-woocommerce' );


		$this->icon = null;

		$this->has_fields = true;

		// support default form with credit card
		$this->supports = array( 'default_credit_card_form' );

		// setting defines
		$this->init_form_fields();

		// load time variable setting
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// further check of SSL if you want
		add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );

		// Check if the keys have been configured
		if( !is_admin() ) {
				//wc_add_notice( __("This website is on test mode, so orders are not going to be processed. Please contact the store owner for more information or alternative ways to pay.", "wp-ipaytotal-woocommerce") );
		}

		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // Here is the  End __construct()

	// administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'			=> __( 'Enable / Disable', 'wp-ipaytotal-woocommerce' ),
				'label'			=> __( 'Enable this payment gateway', 'wp-ipaytotal-woocommerce' ),
				'type'			=> 'checkbox',
				'default'		=> 'no',
			),
			'ipt_key_secret' => array(
				'title'			=> __( 'API Secret Key', 'wp-ipaytotal-woocommerce' ),
				'type'			=> 'text',
				'desc_tip'	=> __( 'This is the API Secret Key provided by iPayTotal when you signed up for an account.', 'wp-ipaytotal-woocommerce' ),
			)
		);		
	}
        
        function getCreditCardType($cc, $extra_check = FALSE)
        {
            if (empty($cc)) {
                return false;
            }
            
            $cards = array(
                "visa" => "(4\d{12}(?:\d{3})?)",
                "amex" => "(3[47]\d{13})",
                "jcb" => "(35[2-8][89]\d\d\d{10})",
                "maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
                "solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
                "mastercard" => "(5[1-5]\d{14})",
                "switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
            );
            $names = array("Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Switch");
            $matches = array();
            $pattern = "#^(?:".implode("|", $cards).")$#";
            $result = preg_match($pattern, str_replace(" ", "", $cc), $matches);
            if($extra_check && $result > 0){
                $result = (validatecard($cc))?1:0;
            }
            $card = ($result>0)?$names[sizeof($matches)-2]:false;
            
//            Valid Following Card Type.
//            1 - For Amex 
//            2 - For Visa 
//            3 - For Mastercard 
//            4 - For Discover
            
            switch ($card):
                case 'Visa':
                    return '2';
                    break;
                case 'American Express':
                    return '1';
                    break;
                case 'Maestro':
                case 'Mastercard':
                    return '3';
                    break;
                default :
                    return '4';
                    break;
            endswitch;
            
        }
	
	// Response handled for payment gateway
	public function process_payment( $order_id ) {
		global $woocommerce;

		$customer_order = new WC_Order( $order_id );

		$products = $customer_order->get_items();

		$ipaytotal_card = new WOWP_IPTWPG_iPayTotal_API();
                
                $date_array = $_POST['wowp_iptwpg_ipaytotal-card-expiry'];
		$date_array = explode("/", str_replace(' ', '', $date_array));
                
                $data = array(
                            'api_key'       => $this->ipt_key_secret,
                            'first_name'    => $customer_order->get_billing_first_name(),
                            'last_name'     => $customer_order->get_billing_last_name(),
                            'address'       => $customer_order->get_billing_address_1(),
                            'sulte_apt_no'  => rand(1,99),
                            'country'       => $customer_order->get_billing_country(),
                            'state'         => $customer_order->get_billing_state(), // if your country US then use only 2 letter state code.
                            'city'          => $customer_order->get_billing_city(),
                            'zip'           => $customer_order->get_billing_postcode(),
                            'ip_address'    => $customer_order->get_customer_ip_address(),
                            'birth_date'    => rand(1,12).'/'.rand(1,30).'/'.rand(1985,1991),
                            'email'         => $customer_order->get_billing_email(),
                            'phone_no'      => $customer_order->get_billing_phone(),
                            'card_type'     => self::getCreditCardType($_POST['wowp_iptwpg_ipaytotal-card-number']), // See your card type in list
                            'amount'        => $customer_order->get_total(),
                            'currency'      => $customer_order->get_currency(),
                            
                            'card_no'       => str_replace( array(' ', '-' ), '', $_POST['wowp_iptwpg_ipaytotal-card-number'] ),
                            'ccExpiryMonth' => $date_array[0],
                            'ccExpiryYear'  => $date_array[1],
                            'cvvNumber'     => ( isset( $_POST['wowp_iptwpg_ipaytotal-card-cvc'] ) ) ? $_POST['wowp_iptwpg_ipaytotal-card-cvc'] : 'no',
                    
                            'shipping_first_name'   => $customer_order->get_shipping_first_name(),
                            'shipping_last_name'    => $customer_order->get_shipping_last_name(),
                            'shipping_address'      => $customer_order->get_shipping_address_1(),
                            'shipping_country'      => $customer_order->get_shipping_address_2(),
                            'shipping_state'        => $customer_order->get_shipping_country(),
                            'shipping_city'         => $customer_order->get_shipping_state(), // if 
                            'shipping_zip'          => $customer_order->get_shipping_city(),
                            'shipping_email'        => $customer_order->get_shipping_postcode(),
                            'shipping_phone_no'     => $customer_order->get_billing_phone(),
                        );

		// Decide which URL to post to
		$environment_url = "https://ipaytotal.solutions/api/transaction";
                
                $result = wp_remote_post( $environment_url, array( 
                    'method'    => 'POST', 
                    'body'      => json_encode( $data ), 
                    'timeout'   => 90, 
                    'sslverify' => true, 
                    'headers' => array( 'Content-Type' => 'application/json' ) 
                ) ); 

		if ( is_wp_error( $result ) ) {
					throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'wp-ipaytotal-woocommerce' ) );
				if ( empty( $result['body'] ) ) {
					throw new Exception( __( 'iPayTotal\'s Response was not get any data.', 'wp-ipaytotal-woocommerce' ) );	
				}
		}
                
		// get body response while get not error
		$response_body = $ipaytotal_card->get_response_body($result);
//
		// 100 o 200 means the transaction was a success
		if ( ( $response_body['status'] == 'success' )) 
		{

			// Payment successful
			$customer_order->add_order_note( __( $response_body['message'], 'wp-ipaytotal-woocommerce' ) );
												 
			// paid order marked
			//$customer_order->update_status('complete');
			$customer_order->payment_complete();
			// this is important part for empty cart
			$woocommerce->cart->empty_cart();
			
			wc_add_notice( __('Payment successful. ') . $response_body['message'] . ' - ' . $response_body['descripcion'] . '.', 'error' );

			 //Redirect to thank you page
			 return array( 'result'   => 'success','redirect' => $this->get_return_url( $customer_order ) );
		} 
		
		//else 
		
		//{
			//transiction fail
			//if( !current_user_can('edit_plugins') ) {
			//	wc_add_notice( $response_body['message'] . ' - ' . __('Payment failed. Please contact the store owner for more information or alternative ways to pay. ', 'wp-ipaytotal-woocommerce'), 'error');
		//	} 
			
			else  
			
			{
				wc_add_notice( __('Payment failed. ') .  iii . $response_body['message'] . iiii . $response_body['descripcion'] . '.', 'error' );
				$customer_order->update_status('failed');
			}

			//wc_add_notice( 'Full response: ' . json_encode($response_body) );

		//}

	}
	
	// Validate fields
	public function validate_fields() {
		return true;
	}


}
