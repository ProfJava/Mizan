<?php

class Mizan_API {

	public $errors;
	public $result;

	private $api_route = 'http://istorejed.gotdns.com:8010/';
	private $all_products = 'api/ProdAPI/GetProducts';
	private $security_key = 'rvgU7mF8cWBn9b4o4KH3uKH9';

	public function __construct() {

	}

	public function request() {
		$response = $this->get_request( $this->all_products_route() );
		if ( \is_wp_error( $response ) ) {
			PSM_Helpers::Log( 'Request Failed ' . print_r( $response, true ), 'Error' );//todo remove this

			$this->errors = new WP_Error( 'mizan_request_failed', 'Failed to request' );
		} else {
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( $status_code == 200 ) {
				PSM_Helpers::Log( 'Response ' . print_r( wp_remote_retrieve_body( $response ), true ), 'Info' );//todo remove this
				$this->result = wp_remote_retrieve_body( $response );
			} else {
				//Helpers::Log( 'Error in class ' . get_class( $this ) . ' and status code is ' . $status_code, 'Error' );
				PSM_Helpers::Log( 'Response ' . print_r( wp_remote_retrieve_body( $response ), true ), 'Error' );//todo remove this

				$this->errors = wp_remote_retrieve_body( $response );
			}
		}
	}

	/**
	 * Create get requests
	 *
	 * @param $url string the end point
	 *
	 * @return string|WP_Error
	 */
	protected function get_request( $url ) {
		return wp_remote_get( $url, array(
			'headers' => array(
				'Securitykey' => $this->security_key,
			)
		) );
	}

	private function all_products_route() {
		return $this->api_route . $this->all_products;
	}
}
