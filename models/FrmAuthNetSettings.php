<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetSettings' ) ) {
	return;
}

/**
 * Settings Class (Model)
 *
 * Create and set the global settings for the plugin
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetSettings {
	/**
	 *  @since 1.0
	 */
	public $settings;
	public $merchant;

	/**
	 *  @since 1.0
	 */
	public function __construct() {
		// $this->merchant = new AuthorizeNetAIM();
		$this->set_default_options();
	}

	/**
	 * Defult options for Formidable AuthorizeNet Global Settings.
	 *
	 * @return array return default options
	 * @since 1.0
	 */
	public function default_options() {
		return array(
			'login_id'             => '',
			'transaction_key'      => '',
			'signature_key'        => '',
			'environment'          => 'sandbox',
			'reciept_heading_text' => '',
			'reciept_footer_text'  => '',
			'webhook_created'      => 0,
		);
	}

	/**
	 * @since 1.0
	 *
	 * @param array|false $settings
	 * @return void
	 */
	public function set_default_options( $settings = false ) {

		$default_settings = $this->default_options();

		if ( $settings === true ) {
			$settings = new stdClass();
		} elseif ( ! $settings ) {
			$settings = $this->get_options();
		}

		if ( ! isset( $this->settings ) ) {
			$this->settings = new stdClass();
		}

		foreach ( $default_settings as $setting => $default ) {
			if ( is_object( $settings ) && isset( $settings->{$setting} ) ) {
				$this->settings->{$setting} = $settings->{$setting};
			}

			if ( ! isset( $this->settings->{$setting} ) ) {
				$this->settings->{$setting} = $default;
			}
		}

		$this->settings = apply_filters( 'settings', $this->settings );
	}

	/**
	 * @since 1.0
	 */
	public function get_options() {

		$settings = get_option( 'frm_authnet_options' );

		if ( ! is_object( $settings ) ) {
			if ( $settings ) { // workaround for W3 total cache conflict
				$settings = unserialize( serialize( $settings ) );
			} else {
				// If unserializing didn't work
				if ( ! is_object( $settings ) ) {
					if ( $settings ) { // workaround for W3 total cache conflict
						$settings = unserialize( serialize( $settings ) );
					} else {
						$this->set_default_options( true );
					}
					$this->store();
				}
			}
		} else {
			$this->set_default_options( $settings );
		}

		return $this->settings;
	}

	/**
	 * @since 1.0
	 */
	public function update( $params ) {

		$settings = $this->default_options();

		foreach ( $settings as $setting => $default ) {
			if ( ! isset( $params[ 'frm_authnet_' . $setting ] ) ) {
				continue;
			}

			$this->settings->{$setting} = trim( $params[ 'frm_authnet_' . $setting ] );
		}
	}

	/**
	 * Store the posted settings in the database.
	 *
	 * @since 1.0
	 */
	public function store() {
		update_option( 'frm_authnet_options', $this->settings, 'no' );
	}
}
