<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function wpcf8_delete_plugin() {
	global $wpdb;

	delete_option( 'wpcf8' );

	$posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type' => 'wpcf8_contact_form',
			'post_status' => 'any',
		)
	);

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	$wpdb->query( sprintf( "DROP TABLE IF EXISTS %s",
		$wpdb->prefix . 'contact_form_8' ) );
}

wpcf8_delete_plugin();
