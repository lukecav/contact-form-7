<?php
/*
Plugin Name: Contact Form 8
Plugin URI: https://github.com/lukecav/contact-form-8/
Description: Just another contact form plugin. Simple but flexible.
Author: Takayuki Miyoshi
Author URI: http://ideasilo.wordpress.com/
Text Domain: contact-form-8
Domain Path: /languages/
Version: 4.7
*/

define( 'WPCF8_VERSION', '4.7' );

define( 'WPCF8_REQUIRED_WP_VERSION', '4.6' );

define( 'WPCF8_PLUGIN', __FILE__ );

define( 'WPCF8_PLUGIN_BASENAME', plugin_basename( WPCF8_PLUGIN ) );

define( 'WPCF8_PLUGIN_NAME', trim( dirname( WPCF8_PLUGIN_BASENAME ), '/' ) );

define( 'WPCF8_PLUGIN_DIR', untrailingslashit( dirname( WPCF8_PLUGIN ) ) );

define( 'WPCF8_PLUGIN_MODULES_DIR', WPCF8_PLUGIN_DIR . '/modules' );

if ( ! defined( 'WPCF8_LOAD_JS' ) ) {
	define( 'WPCF8_LOAD_JS', true );
}

if ( ! defined( 'WPCF8_LOAD_CSS' ) ) {
	define( 'WPCF8_LOAD_CSS', true );
}

if ( ! defined( 'WPCF8_AUTOP' ) ) {
	define( 'WPCF8_AUTOP', true );
}

if ( ! defined( 'WPCF8_USE_PIPE' ) ) {
	define( 'WPCF8_USE_PIPE', true );
}

if ( ! defined( 'WPCF8_ADMIN_READ_CAPABILITY' ) ) {
	define( 'WPCF8_ADMIN_READ_CAPABILITY', 'edit_posts' );
}

if ( ! defined( 'WPCF8_ADMIN_READ_WRITE_CAPABILITY' ) ) {
	define( 'WPCF8_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
}

if ( ! defined( 'WPCF8_VERIFY_NONCE' ) ) {
	define( 'WPCF8_VERIFY_NONCE', true );
}

if ( ! defined( 'WPCF8_USE_REALLY_SIMPLE_CAPTCHA' ) ) {
	define( 'WPCF8_USE_REALLY_SIMPLE_CAPTCHA', false );
}

if ( ! defined( 'WPCF8_VALIDATE_CONFIGURATION' ) ) {
	define( 'WPCF8_VALIDATE_CONFIGURATION', true );
}

// Deprecated, not used in the plugin core. Use WPCF8_plugin_url() instead.
define( 'WPCF8_PLUGIN_URL', untrailingslashit( plugins_url( '', WPCF8_PLUGIN ) ) );

require_once WPCF8_PLUGIN_DIR . '/settings.php';
