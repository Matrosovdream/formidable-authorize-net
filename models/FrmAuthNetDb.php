<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetDb' ) ) {
	return;
}

/**
 * Create / Upgrade Database Class (Model)
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetDb {

	/**
	 * Upgrade tbe plugin
	 *
	 * @param int|false $old_db_version
	 */
	public function upgrade( $old_db_version = false ) {
		global $wpdb;

		$db_version = FrmAuthNetController::$db_version; // $db_version is the version of the database we're moving to
		$db_opt_name = FrmAuthNetController::$db_opt_name;

		if ( ! $old_db_version ) {
			$old_db_version = get_option( $db_opt_name );
		}

		if ( $db_version != $old_db_version ) {

			/***** SAVE DB VERSION *****/
			update_option( $db_opt_name, $db_version );

			if ( is_callable( 'FrmXMLController::add_default_templates' ) ) {
				FrmXMLController::add_default_templates();
			}
		}
	}
}
