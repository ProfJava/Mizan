<?php
//create psm-get-products task every hour
add_action( 'init', 'psm_get_products' );
function psm_get_products() {
//	delete_transient('mizan_process_latest_store_details');
	$data = get_transient( 'mizan_process_latest_store_details' );
//	as_unschedule_action( 'psm_update_products', array(), 'mizan_sync' );
	if ( $data == false ) {
		$mizan = new Mizan_API();
		$mizan->request();
		if ( $mizan->errors ) {
			//there are errors
			psm_insert_log( 'Failed to connect', 0 );//log product_id from store as success
		} elseif ( $mizan->result ) {
			//PSM_Helpers::delete_option( 'store_list' );
			PSM_Helpers::update_option( 'store_latest_results', $mizan->result );
			if ( $mizan->result ) {
				set_transient( 'mizan_process_latest_store_details', 'ok', PSM_Helpers::get_option( 'sync_period' ) ? PSM_Helpers::get_option( 'sync_period' ) : ( 30 * MINUTE_IN_SECONDS ) );
				if ( false === as_next_scheduled_action( 'psm_update_all_products', array(), 'mizan_sync' ) ) {
					as_schedule_single_action( time(), 'psm_update_all_products', array(), 'mizan_sync' );
				}
			}
		}
	}
}

add_action( 'psm_update_all_products', function ( $data = null ) {
	if ( $data = PSM_Helpers::get_option( 'store_latest_results' ) ) {
		$products        = json_decode( $data, true );
		$store_reduction = PSM_Helpers::get_option( 'sync_reduction' ) ? PSM_Helpers::get_option( 'sync_reduction' ) : 0;
		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				$product_sku = $product['p_prodidco'];
				$product_id  = wc_get_product_id_by_sku( $product_sku );
				if ( $product_id ) {
					$product_obj = wc_get_product( $product_id );
					$product_obj->set_stock_quantity( $product['p_curnbals'] ); //update stock quantity
					if ( is_numeric( $product['p_curnbals'] ) ) {
						if ( $product['p_curnbals'] > $store_reduction ) {
							$quantity = $product['p_curnbals'] - $store_reduction;
						} else {
							$quantity = 0;
						}
						$quantity = $product['p_curnbals'];//remove this tonight
						wc_update_product_stock( $product_obj, $quantity );
						wc_delete_product_transients( $product_id );
						psm_insert_log( $product_obj->get_id(), 1 );//log product_id from store as success
					}
				} else {
//					psm_insert_log( $product['p_prodidco'], 0 ); //log product sku from api as failed
				}
			}
		} else {
			return false;
		}

		return true;
	}

	return false;
} );


function psm_insert_log( $product_id, $sync_status ) {
	global $wpdb;
	$wpdb->insert( $wpdb->prefix . 'psm_sync_log', array(
		'product_id' => $product_id,
		'status'     => $sync_status,
	) );
}

function psm_display_log_results() {
	global $wpdb;

	return $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "psm_sync_log ORDER BY `id` DESC LIMIT 200;", ARRAY_A );
}

function psm_get_product_sync_log_results( $product_id ) {
	global $wpdb;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "psm_sync_log WHERE product_id = '" . $product_id . "' LIMIT 100;" ), ARRAY_A );
}

add_filter( 'action_scheduler_queue_runner_time_limit', 'psm_increase_time_limit' );
function psm_increase_time_limit( $time_limit ) {
	return 800;
}
