<?php
/**
** A base module for [response]
**/

/* form_tag handler */

add_action( 'wpcf8_init', 'wpcf8_add_form_tag_response' );

function wpcf8_add_form_tag_response() {
	wpcf8_add_form_tag( 'response', 'wpcf8_response_form_tag_handler' );
}

function wpcf8_response_form_tag_handler( $tag ) {
	if ( $contact_form = wpcf8_get_current_contact_form() ) {
		return $contact_form->form_response_output();
	}
}
