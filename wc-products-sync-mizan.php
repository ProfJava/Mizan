<?php
/**
 * Plugin Name: Wc Products Sync Mizan
 * Plugin URI: https://github.com/DevWael/Wc-Products-Sync-Mizan
 * Description: Sync WC Products With Mizan API.
 * Version: 1.0
 * Author: AhmadWael
 * Author URI: https://github.com/DevWael
 * License: GPL2
 */

define( 'PSM_DIR', plugin_dir_path( __FILE__ ) );
define( 'PSM_URI', plugin_dir_url( __FILE__ ) );

//check if woocommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	require_once PSM_DIR . 'inc/action-scheduler/action-scheduler.php';
	require_once PSM_DIR . 'inc/PSM_Helpers.php'; //helper functions
//require_once PSM_DIR . 'inc/functions.php'; //Hooked functions
	require_once PSM_DIR . 'inc/PSM_Sync_Tasks.php'; //Tasks Queue
	require_once PSM_DIR . 'inc/Mizan_API.php'; //Mizan API
	require_once PSM_DIR . 'inc/admin_page.php'; //admin page
}

register_activation_hook( __FILE__, 'psm_database_table' );
function psm_database_table() {
	global $wpdb;
	$tblname   = 'psm_sync_log';
	$log_table = $wpdb->prefix . $tblname;

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$log_table'" ) != $log_table ) {
		$sql = "CREATE TABLE `" . $log_table . "` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `product_id` TEXT NOT NULL , `sync_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `status` TEXT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";

		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}

