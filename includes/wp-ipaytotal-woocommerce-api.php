<?php

/**
* @package wp-ipaytotal-woocommerce
* @author  iPayTotal Ltd
* @since   1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Cardpay_Authnet_API
 */
 class WOWP_IPTWPG_iPayTotal_API {

	public $wc_pre_30;

	public function __construct() {
		$this->wc_pre_30 = version_compare( WC_VERSION, '3.0.0', '<' );

		$date_array = $_POST['wowp_iptwpg_ipaytotal-card-expiry'];
		$date_array = explode("/", str_replace(' ', '', $date_array));

		$this->credit_card_data = array(
				'nameCard'		=> mb_convert_encoding($_POST['wowp_iptwpg_ipaytotal-card-name'], 'HTML-ENTITIES'),
				'accountNumber'		=> str_replace( array(' ', '-' ), '', $_POST['wowp_iptwpg_ipaytotal-card-number'] ),
				'expirationMonth'	=> $date_array[0],
				'expirationYear'	=> $date_array[1], 
				'CVVCard'		=> ( isset( $_POST['wowp_iptwpg_ipaytotal-card-cvc'] ) ) ? $_POST['wowp_iptwpg_ipaytotal-card-cvc'] : 'no',
		);
	}

	/**
	 * get_detalle_data function
	 * 
	 * @return string
	 */
	public function get_detalle_data( $products ) {
		foreach ( $products as $product ) {
    	$detalle[] = array(
				'id_producto'	=> $product->get_product_id(),
				'cantidad'		=> $product->get_quantity(),
				'tipo'				=> $product->get_type(),
				'nombre'			=> $product->get_name(),
				'precio'			=> get_post_meta( $product->get_product_id(), '_regular_price', true),
				'Subtotal'		=> $product->get_total(),
			);
		}

		$detalle_data = json_encode( $detalle );
		return $detalle_data;
	}

	/**
	 * get_credit_card_data function
	 * 
	 * @return string
	 */
	public function get_credit_card_data( ) {

		$credit_card_data = json_encode( $this->credit_card_data );

		return $credit_card_data;
	}


    
	/**
	 * get_response_body function
	 * 
	 * @return string
	 */
	public function get_response_body($response) {

		// get body response while get not error
		$response_body = wp_remote_retrieve_body( $response);

		foreach ( preg_split( "/\r?\n/", $response_body ) as $line ) {
			$resp = explode( "|", $line );
		}

		// values get
		$r = json_decode( $resp[0], true );

		return $r;
	}

}

    
    
    
    
