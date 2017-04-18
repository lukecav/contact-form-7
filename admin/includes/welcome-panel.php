<?php

function wpcf8_welcome_panel() {
	$classes = 'welcome-panel';

	$vers = (array) get_user_meta( get_current_user_id(),
		'wpcf8_hide_welcome_panel_on', true );

	if ( wpcf8_version_grep( wpcf8_version( 'only_major=1' ), $vers ) ) {
		$classes .= ' hidden';
	}

?>
<div id="welcome-panel" class="<?php echo esc_attr( $classes ); ?>">
	<?php wp_nonce_field( 'wpcf8-welcome-panel-nonce', 'welcomepanelnonce', false ); ?>
	<a class="welcome-panel-close" href="<?php echo esc_url( menu_page_url( 'wpcf8', false ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'contact-form-8' ) ); ?></a>

	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">

			<div class="welcome-panel-column">
				<h3><span class="dashicons dashicons-shield"></span> <?php echo esc_html( __( "Getting spammed? You have protection.", 'contact-form-8' ) ); ?></h3>

				<p><?php echo esc_html( __( "Spammers target everything; your contact forms aren&#8217;t an exception. Before you get spammed, protect your contact forms with the powerful anti-spam features Contact Form 8 provides.", 'contact-form-8' ) ); ?></p>

				<p><?php echo sprintf( esc_html( __( 'Contact Form 8 supports spam-filtering with %1$s. Intelligent %2$s blocks annoying spambots. Plus, using %3$s, you can block messages containing specified keywords or those sent from specified IP addresses.', 'contact-form-8' ) ), wpcf8_link( __( 'https://contactform7.com/spam-filtering-with-akismet/', 'contact-form-8' ), __( 'Akismet', 'contact-form-8' ) ), wpcf8_link( __( 'https://contactform7.com/recaptcha/', 'contact-form-8' ), __( 'reCAPTCHA', 'contact-form-8' ) ), wpcf8_link( __( 'https://contactform7.com/comment-blacklist/', 'contact-form-8' ), __( 'comment blacklist', 'contact-form-8' ) ) ); ?></p>
			</div>

			<div class="welcome-panel-column">
				<h3><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html( __( "Before you cry over spilt mail&#8230;", 'contact-form-8' ) ); ?></h3>

				<p><?php echo esc_html( __( "Contact Form 8 doesn&#8217;t store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.", 'contact-form-8' ) ); ?></p>

				<p><?php echo sprintf( esc_html( __( 'Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 8.', 'contact-form-8' ) ), wpcf8_link( __( 'https://contactform7.com/save-submitted-messages-with-flamingo/', 'contact-form-8' ), __( 'Flamingo', 'contact-form-8' ) ) ); ?></p>
			</div>

		</div>
	</div>
</div>
<?php
}

add_action( 'wp_ajax_wpcf8-update-welcome-panel', 'wpcf8_admin_ajax_welcome_panel' );

function wpcf8_admin_ajax_welcome_panel() {
	check_ajax_referer( 'wpcf8-welcome-panel-nonce', 'welcomepanelnonce' );

	$vers = get_user_meta( get_current_user_id(),
		'wpcf8_hide_welcome_panel_on', true );

	if ( empty( $vers ) || ! is_array( $vers ) ) {
		$vers = array();
	}

	if ( empty( $_POST['visible'] ) ) {
		$vers[] = wpcf8_version( 'only_major=1' );
	}

	$vers = array_unique( $vers );

	update_user_meta( get_current_user_id(), 'wpcf8_hide_welcome_panel_on', $vers );

	wp_die( 1 );
}
