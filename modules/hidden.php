<?php

add_action( 'wpcf8_init', 'wpcf8_add_form_tag_hidden' );

function wpcf8_add_form_tag_hidden() {
	wpcf8_add_form_tag( 'hidden',
		'wpcf8_hidden_form_tag_handler', array( 'name-attr' => true ) );
}

function wpcf8_hidden_form_tag_handler( $tag ) {
	$tag = new wpcf8_FormTag( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$atts = array();

	$class = wpcf8_form_controls_class( $tag->type );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$value = (string) reset( $tag->values );
	$value = $tag->get_default_option( $value );
	$atts['value'] = $value;

	$atts['type'] = 'hidden';
	$atts['name'] = $tag->name;
	$atts = wpcf8_format_atts( $atts );

	$html = sprintf( '<input %s />', $atts );
	return $html;
}
