<?php
/**
 * All the functions and classes in this file are deprecated.
 * You shouldn't use them. The functions and classes will be
 * removed in a later version.
 */

function wpcf8_add_shortcode( $tag, $func, $has_name = false ) {
	wpcf8_deprecated_function( __FUNCTION__, '4.6', 'wpcf8_add_form_tag' );

	return wpcf8_add_form_tag( $tag, $func, $has_name );
}

function wpcf8_remove_shortcode( $tag ) {
	wpcf8_deprecated_function( __FUNCTION__, '4.6', 'wpcf8_remove_form_tag' );

	return wpcf8_remove_form_tag( $tag );
}

function wpcf8_do_shortcode( $content ) {
	wpcf8_deprecated_function( __FUNCTION__, '4.6',
		'wpcf8_replace_all_form_tags' );

	return wpcf8_replace_all_form_tags( $content );
}

function wpcf8_scan_shortcode( $cond = null ) {
	wpcf8_deprecated_function( __FUNCTION__, '4.6', 'wpcf8_scan_form_tags' );

	return wpcf8_scan_form_tags( $cond );
}

class wpcf8_ShortcodeManager {

	private static $form_tags_manager;

	private function __construct() {}

	public static function get_instance() {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::get_instance' );

		self::$form_tags_manager = wpcf8_FormTagsManager::get_instance();
		return new self;
	}

	public function get_scanned_tags() {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::get_scanned_tags' );

		return self::$form_tags_manager->get_scanned_tags();
	}

	public function add_shortcode( $tag, $func, $has_name = false ) {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::add' );

		return self::$form_tags_manager->add( $tag, $func, $has_name );
	}

	public function remove_shortcode( $tag ) {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::remove' );

		return self::$form_tags_manager->remove( $tag );
	}

	public function normalize_shortcode( $content ) {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::normalize' );

		return self::$form_tags_manager->normalize( $content );
	}

	public function do_shortcode( $content, $exec = true ) {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::replace_all' );

		if ( $exec ) {
			return self::$form_tags_manager->replace_all( $content );
		} else {
			return self::$form_tags_manager->scan( $content );
		}
	}

	public function scan_shortcode( $content ) {
		wpcf8_deprecated_function( __METHOD__, '4.6',
			'wpcf8_FormTagsManager::scan' );

		return self::$form_tags_manager->scan( $content );
	}
}

class wpcf8_Shortcode extends wpcf8_FormTag {

	public function __construct( $tag ) {
		wpcf8_deprecated_function( 'wpcf8_Shortcode', '4.6', 'wpcf8_FormTag' );

		parent::__construct( $tag );
	}
}
