<?php

require_once WPCF8_PLUGIN_DIR . '/includes/functions.php';
require_once WPCF8_PLUGIN_DIR . '/includes/l10n.php';
require_once WPCF8_PLUGIN_DIR . '/includes/formatting.php';
require_once WPCF8_PLUGIN_DIR . '/includes/pipe.php';
require_once WPCF8_PLUGIN_DIR . '/includes/form-tag.php';
require_once WPCF8_PLUGIN_DIR . '/includes/form-tags-manager.php';
require_once WPCF8_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WPCF8_PLUGIN_DIR . '/includes/capabilities.php';
require_once WPCF8_PLUGIN_DIR . '/includes/contact-form-template.php';
require_once WPCF8_PLUGIN_DIR . '/includes/contact-form.php';
require_once WPCF8_PLUGIN_DIR . '/includes/contact-form-functions.php';
require_once WPCF8_PLUGIN_DIR . '/includes/mail.php';
require_once WPCF8_PLUGIN_DIR . '/includes/submission.php';
require_once WPCF8_PLUGIN_DIR . '/includes/upgrade.php';
require_once WPCF8_PLUGIN_DIR . '/includes/integration.php';
require_once WPCF8_PLUGIN_DIR . '/includes/config-validator.php';
require_once WPCF8_PLUGIN_DIR . '/includes/rest-api.php';

if ( is_admin() ) {
	require_once WPCF8_PLUGIN_DIR . '/admin/admin.php';
} else {
	require_once WPCF8_PLUGIN_DIR . '/includes/controller.php';
}

class WPCF8 {

	public static function load_modules() {
		self::load_module( 'acceptance' );
		self::load_module( 'akismet' );
		self::load_module( 'checkbox' );
		self::load_module( 'count' );
		self::load_module( 'date' );
		self::load_module( 'file' );
		self::load_module( 'flamingo' );
		self::load_module( 'listo' );
		self::load_module( 'number' );
		self::load_module( 'quiz' );
		self::load_module( 'really-simple-captcha' );
		self::load_module( 'recaptcha' );
		self::load_module( 'response' );
		self::load_module( 'select' );
		self::load_module( 'submit' );
		self::load_module( 'text' );
		self::load_module( 'textarea' );
		self::load_module( 'hidden' );
	}

	protected static function load_module( $mod ) {
		$dir = WPCF8_PLUGIN_MODULES_DIR;

		if ( empty( $dir ) || ! is_dir( $dir ) ) {
			return false;
		}

		$file = path_join( $dir, $mod . '.php' );

		if ( file_exists( $file ) ) {
			include_once $file;
		}
	}

	public static function get_option( $name, $default = false ) {
		$option = get_option( 'WPCF8' );

		if ( false === $option ) {
			return $default;
		}

		if ( isset( $option[$name] ) ) {
			return $option[$name];
		} else {
			return $default;
		}
	}

	public static function update_option( $name, $value ) {
		$option = get_option( 'WPCF8' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, array( $name => $value ) );
		update_option( 'WPCF8', $option );
	}
}

add_action( 'plugins_loaded', 'WPCF8' );

function WPCF8() {
	WPCF8_load_textdomain();
	WPCF8::load_modules();

	/* Shortcodes */
	add_shortcode( 'contact-form-7', 'WPCF8_contact_form_tag_func' );
	add_shortcode( 'contact-form', 'WPCF8_contact_form_tag_func' );
}

add_action( 'init', 'WPCF8_init' );

function WPCF8_init() {
	WPCF8_get_request_uri();
	WPCF8_register_post_types();

	do_action( 'WPCF8_init' );
}

add_action( 'admin_init', 'WPCF8_upgrade' );

function WPCF8_upgrade() {
	$old_ver = WPCF8::get_option( 'version', '0' );
	$new_ver = WPCF8_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'WPCF8_upgrade', $new_ver, $old_ver );

	WPCF8::update_option( 'version', $new_ver );
}

/* Install and default settings */

add_action( 'activate_' . WPCF8_PLUGIN_BASENAME, 'WPCF8_install' );

function WPCF8_install() {
	if ( $opt = get_option( 'WPCF8' ) ) {
		return;
	}

	WPCF8_load_textdomain();
	WPCF8_register_post_types();
	WPCF8_upgrade();

	if ( get_posts( array( 'post_type' => 'WPCF8_contact_form' ) ) ) {
		return;
	}

	$contact_form = WPCF8_ContactForm::get_template(
		array(
			'title' => sprintf( __( 'Contact form %d', 'contact-form-7' ), 1 ),
		)
	);

	$contact_form->save();

	WPCF8::update_option( 'bulk_validate',
		array(
			'timestamp' => current_time( 'timestamp' ),
			'version' => WPCF8_VERSION,
			'count_valid' => 1,
			'count_invalid' => 0,
		)
	);
}
