<?php

add_action( 'wp_loaded', 'wpcf8_control_init' );

function wpcf8_control_init() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset( $_GET['_wpcf8_is_ajax_call'] ) ) {
			wpcf8_ajax_onload();
		}
	}

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset( $_POST['_wpcf8_is_ajax_call'] ) ) {
			wpcf8_ajax_json_echo();
		}

		wpcf8_submit_nonajax();
	}
}

function wpcf8_ajax_onload() {
	$echo = '';
	$items = array();

	if ( isset( $_GET['_wpcf8'] )
	&& $contact_form = wpcf8_contact_form( (int) $_GET['_wpcf8'] ) ) {
		$items = apply_filters( 'wpcf8_ajax_onload', $items );
	}

	$echo = wp_json_encode( $items );

	if ( wpcf8_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	}

	exit();
}

function wpcf8_ajax_json_echo() {
	$echo = '';

	if ( isset( $_POST['_wpcf8'] ) ) {
		$id = (int) $_POST['_wpcf8'];
		$unit_tag = wpcf8_sanitize_unit_tag( $_POST['_wpcf8_unit_tag'] );

		if ( $contact_form = wpcf8_contact_form( $id ) ) {
			$items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null,
			);

			$result = $contact_form->submit( true );

			if ( ! empty( $result['message'] ) ) {
				$items['message'] = $result['message'];
			}

			if ( 'mail_sent' == $result['status'] ) {
				$items['mailSent'] = true;
			}

			if ( 'validation_failed' == $result['status'] ) {
				$invalids = array();

				foreach ( $result['invalid_fields'] as $name => $field ) {
					$invalids[] = array(
						'into' => 'span.wpcf8-form-control-wrap.'
							. sanitize_html_class( $name ),
						'message' => $field['reason'],
						'idref' => $field['idref'],
					);
				}

				$items['invalids'] = $invalids;
			}

			if ( 'spam' == $result['status'] ) {
				$items['spam'] = true;
			}

			if ( ! empty( $result['scripts_on_sent_ok'] ) ) {
				$items['onSentOk'] = $result['scripts_on_sent_ok'];
			}

			if ( ! empty( $result['scripts_on_submit'] ) ) {
				$items['onSubmit'] = $result['scripts_on_submit'];
			}

			$items = apply_filters( 'wpcf8_ajax_json_echo', $items, $result );
		}
	}

	$echo = wp_json_encode( $items );

	if ( wpcf8_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}

	exit();
}

function wpcf8_is_xhr() {
	if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) {
		return false;
	}

	return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

function wpcf8_submit_nonajax() {
	if ( ! isset( $_POST['_wpcf8'] ) ) {
		return;
	}

	if ( $contact_form = wpcf8_contact_form( (int) $_POST['_wpcf8'] ) ) {
		$contact_form->submit();
	}
}

add_filter( 'widget_text', 'wpcf8_widget_text_filter', 9 );

function wpcf8_widget_text_filter( $content ) {
	$pattern = '/\[[\r\n\t ]*contact-form(-7)?[\r\n\t ].*?\]/';

	if ( ! preg_match( $pattern, $content ) ) {
		return $content;
	}

	$content = do_shortcode( $content );

	return $content;
}

add_action( 'wp_enqueue_scripts', 'wpcf8_do_enqueue_scripts' );

function wpcf8_do_enqueue_scripts() {
	if ( wpcf8_load_js() ) {
		wpcf8_enqueue_scripts();
	}

	if ( wpcf8_load_css() ) {
		wpcf8_enqueue_styles();
	}
}

function wpcf8_enqueue_scripts() {
	// jquery.form.js originally bundled with WordPress is out of date and deprecated
	// so we need to deregister it and re-register the latest one
	wp_deregister_script( 'jquery-form' );
	wp_register_script( 'jquery-form',
		wpcf8_plugin_url( 'includes/js/jquery.form.min.js' ),
		array( 'jquery' ), '3.51.0-2014.06.20', true );

	$in_footer = true;

	if ( 'header' === wpcf8_load_js() ) {
		$in_footer = false;
	}

	wp_enqueue_script( 'contact-form-8',
		wpcf8_plugin_url( 'includes/js/scripts.js' ),
		array( 'jquery', 'jquery-form' ), wpcf8_VERSION, $in_footer );

	$_wpcf8 = array(
		'recaptcha' => array(
			'messages' => array(
				'empty' =>
					__( 'Please verify that you are not a robot.', 'contact-form-8' ),
			),
		),
	);

	if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
		$_wpcf8['cached'] = 1;
	}

	if ( wpcf8_support_html5_fallback() ) {
		$_wpcf8['jqueryUi'] = 1;
	}

	wp_localize_script( 'contact-form-8', '_wpcf8', $_wpcf8 );

	do_action( 'wpcf8_enqueue_scripts' );
}

function wpcf8_script_is() {
	return wp_script_is( 'contact-form-8' );
}

function wpcf8_enqueue_styles() {
	wp_enqueue_style( 'contact-form-8',
		wpcf8_plugin_url( 'includes/css/styles.css' ),
		array(), wpcf8_VERSION, 'all' );

	if ( wpcf8_is_rtl() ) {
		wp_enqueue_style( 'contact-form-8-rtl',
			wpcf8_plugin_url( 'includes/css/styles-rtl.css' ),
			array(), wpcf8_VERSION, 'all' );
	}

	do_action( 'wpcf8_enqueue_styles' );
}

function wpcf8_style_is() {
	return wp_style_is( 'contact-form-8' );
}

/* HTML5 Fallback */

add_action( 'wp_enqueue_scripts', 'wpcf8_html5_fallback', 20 );

function wpcf8_html5_fallback() {
	if ( ! wpcf8_support_html5_fallback() ) {
		return;
	}

	if ( wpcf8_script_is() ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-spinner' );
	}

	if ( wpcf8_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness',
			wpcf8_plugin_url( 'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css' ), array(), '1.10.3', 'screen' );
	}
}
