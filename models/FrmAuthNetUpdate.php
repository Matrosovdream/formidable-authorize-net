<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetUpdate' ) ) {
	return;
}
/**
 *
 * Update the plugin
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'Authorize.Net AIM';
	public $version = '2.04';
	public $download_id = 337527;

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-authorize-net.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmAuthNetUpdate();

		FrmTransAppController::install();
	}
}
