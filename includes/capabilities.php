<?php

add_filter( 'map_meta_cap', 'wpcf8_map_meta_cap', 10, 4 );

function wpcf8_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$meta_caps = array(
		'wpcf8_edit_contact_form' => WPCF8_ADMIN_READ_WRITE_CAPABILITY,
		'wpcf8_edit_contact_forms' => WPCF8_ADMIN_READ_WRITE_CAPABILITY,
		'wpcf8_read_contact_forms' => WPCF8_ADMIN_READ_CAPABILITY,
		'wpcf8_delete_contact_form' => WPCF8_ADMIN_READ_WRITE_CAPABILITY,
		'wpcf8_manage_integration' => 'manage_options',
	);

	$meta_caps = apply_filters( 'wpcf8_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) ) {
		$caps[] = $meta_caps[$cap];
	}

	return $caps;
}
