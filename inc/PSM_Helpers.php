<?php

class PSM_Helpers {
	/**
	 * print data into log file named by date in plugin directory
	 *
	 * @param string|array|object $log_data
	 * @param string $type Log line type
	 */
	public static function Log( $log_data, $type = 'Info' ) {
		$log_filename = PSM_DIR . "log";
		if ( ! file_exists( $log_filename ) ) {
			// create directory/folder uploads.
			mkdir( $log_filename, 0755, true );
		}
		$log_file_data = $log_filename . '/log_' . date( 'd-M-Y' ) . '.log';
		file_put_contents( $log_file_data, $type . ' : ' . date( "d/m/Y h:i:sa" ) . ' ' . print_r( $log_data, true ) . "\n", FILE_APPEND );
	}

	public static function get_option( $key ) {
		return \get_option( 'psm_' . $key );
	}

	public static function update_option( $key, $value ) {
		return \update_option( 'psm_' . $key, $value );
	}

	public static function delete_option( $key ) {
		return \delete_option( 'psm_' . $key );
	}

	public static function get_product_by_sku( $sku ) {

		global $wpdb;

		$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

		if ( $product_id ) return new wc_get_product( $product_id );

		return null;
	}
}
