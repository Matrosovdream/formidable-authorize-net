<?php
/*
Plugin Name: Formidable Authorize.Net
Description: Authorize.net Payment Gateway
Version: 2.04
Plugin URI: http://formidablepro.com/
Author URI: http://strategy11.com
Author: Strategy11
Text Domain: frmauthnet
*/

/**
 * @package FrmAuthNet
 */
function frm_authorizenet_forms_autoloader( $class_name ) {

	// Only load FrmAuthNet classes here
	if ( ! preg_match( '/^FrmAuthNet.+$/', $class_name ) && $class_name != 'FrmAuthNet' ) {
		return;
	}

	$filepath = dirname( __FILE__ );

	if ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$filepath .= '/helpers';
	} elseif ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$filepath .= '/controllers';
	} else {
		$filepath .= '/models';
	}

	$filepath .= '/' . $class_name . '.php';

	if ( file_exists( $filepath ) ) {
		include $filepath;
	}
}

// Add the autoloader
spl_autoload_register( 'frm_authorizenet_forms_autoloader' );

if ( ! function_exists( 'frm_trans_autoloader' ) ) {
	include( dirname( __FILE__ ) . '/formidable-payments/formidable-payments.php' );
}

FrmAuthNetHooksController::load_hooks();
