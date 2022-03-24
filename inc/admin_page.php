<?php

class PSM_Admin_Interface {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu_item' ) );
		add_action( 'admin_post_mizan_sync_settings', array( $this, 'process_settings' ) );
	}

	public function admin_menu_item() {
		add_menu_page( 'Mizan Sync', 'Mizan Sync', 'manage_options',
			'psm-mizan-sync', array( $this, 'admin_page_content' ), 'dashicons-controls-repeat', 15 );
		add_submenu_page( 'psm-mizan-sync', 'Settings', 'Settings', 'manage_options', 'psm-mizan-sync-settings', array(
			$this,
			'admin_settings_page_content'
		) );
	}

	public function admin_page_content() {
		echo '<div class="wrap">';
		$sync_results = psm_display_log_results();
		?>
        <style>
            #sync-data {
                font-family: Arial, Helvetica, sans-serif;
                border-collapse: collapse;
                width: 70%;
            }

            #sync-data td, #sync-data th {
                border: 1px solid #ddd;
                padding: 5px;
                transition: 300ms;
            }

            #sync-data tr:nth-child(even) {
                background-color: #dcdcdc;
            }

            #sync-data tr:hover {
                background-color: #d0d0d0;
            }

            #sync-data th {
                padding-top: 12px;
                padding-bottom: 12px;
                text-align: left;
                background-color: #424242;
                color: white;
            }
        </style>
        <h3>
            Products Sync Details
        </h3>
        <table id="sync-data">
            <tr>
                <th>#</th>
                <th>Product ID</th>
                <th>Sync Date</th>
                <th>Status</th>
            </tr>
			<?php $i = 1;
			foreach ( $sync_results as $result ) { ?>
                <tr>
                    <td><?php echo esc_html( $i ) ?></td>
                    <td><?php
						if ( is_numeric( $result['product_id'] ) ) {
							$product = wc_get_product( $result['product_id'] );
							if ( $product ) {
								echo $product->get_title();
							} else {
								echo esc_html( $result['product_id'] );
							}
						} else {
							echo esc_html( $result['product_id'] );
						}
						?></td>
                    <td><?php echo esc_html( $result['sync_date'] ) ?></td>
                    <td><?php echo $result['status'] ? 'Successful' : 'failed'; ?></td>
                </tr>
				<?php $i ++;
			} ?>
        </table>
		<?php


		echo '</div>';
	}

	public function admin_settings_page_content() {
		?>
        <div class="wrap">
			<?php
			$success_transient = 'mizan_success_' . get_current_user_id();
			$error_transient   = 'mizan_error_' . get_current_user_id();

			if ( $transient = get_transient( $error_transient ) ) {
				?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo $transient; ?></p>
                </div>
				<?php
			}

			if ( $transient = get_transient( $success_transient ) ) {
				?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo $transient; ?></p>
                </div>
				<?php
			}
			delete_transient( $success_transient );
			delete_transient( $error_transient );
			?>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ) ?>">
                <input type="hidden" name="action" value="mizan_sync_settings">
				<?php wp_nonce_field() ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="sync_reduction">Store Reduction</label></th>
                        <td><input name="sync_reduction" type="number" id="sync_reduction"
                                   value="<?php echo PSM_Helpers::get_option( 'sync_reduction' ) ? PSM_Helpers::get_option( 'sync_reduction' ) : 0; ?>"
                                   class="regular-text code"> Units
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="period">Sync Every</label></th>
                        <td><input name="period" type="number" id="period"
                                   value="<?php echo PSM_Helpers::get_option( 'sync_period' ) ? PSM_Helpers::get_option( 'sync_period' ) : ( 30 * MINUTE_IN_SECONDS ); ?>"
                                   class="regular-text code"> Seconds
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Sync Now</th>
                        <td>
                            <fieldset>
                                <label for="sync_now">
                                    <input name="sync_now" type="checkbox" id="sync_now" value="1"></label>
                            </fieldset>
                        </td>
                    </tr>

                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                         value="Save Changes"></p>
            </form>
        </div>
		<?php
	}

	public function process_settings() {
		$success_transient = 'mizan_success_' . get_current_user_id();
		$error_transient   = 'mizan_error_' . get_current_user_id();

		if ( ! isset( $_POST['_wpnonce'] ) ) { //check nonce parameter
			set_transient( $error_transient, 'Missing Nonce', 10 );
			wp_safe_redirect( admin_url( 'admin.php?page=psm-mizan-sync-settings' ) );
			exit;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ) ) { //check nonce
			set_transient( $error_transient, 'Failed nonce check', 10 );
			wp_safe_redirect( admin_url( 'admin.php?page=psm-mizan-sync-settings' ) );
			exit;
		}

		if ( ! current_user_can('manage_options') ) { //check user permissions
			set_transient( $error_transient, 'You don\'t have permissions to edit these settings!', 10 );
			wp_safe_redirect( admin_url( 'admin.php?page=psm-mizan-sync-settings' ) );
			exit;
		}

		if ( isset( $_POST['sync_reduction'] ) ) { //store units reduction
			PSM_Helpers::update_option( 'sync_reduction', $_POST['sync_reduction'] );
		}

		if ( isset( $_POST['period'] ) ) { //sync every seconds
			PSM_Helpers::update_option( 'sync_period', $_POST['period'] );
		}

		if ( isset( $_POST['sync_now'] ) && $_POST['sync_now'] == 1 ) { //force sync now
			delete_transient( 'mizan_process_latest_store_details' );
			set_transient( $success_transient, 'Sync started and settings Saved Successfully', 10 );
			wp_safe_redirect( admin_url( 'admin.php?page=psm-mizan-sync-settings' ) );
			exit;
		}

		//all settings saved successfully
		set_transient( $success_transient, 'Settings Saved Successfully', 10 );
		wp_safe_redirect( admin_url( 'admin.php?page=psm-mizan-sync-settings' ) );
		exit;
	}
}

new PSM_Admin_Interface();
