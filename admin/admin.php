<?php

require_once WPCF8_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once WPCF8_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once WPCF8_PLUGIN_DIR . '/admin/includes/tag-generator.php';
require_once WPCF8_PLUGIN_DIR . '/admin/includes/welcome-panel.php';

add_action( 'admin_init', 'wpcf8_admin_init' );

function wpcf8_admin_init() {
	do_action( 'wpcf8_admin_init' );
}

add_action( 'admin_menu', 'wpcf8_admin_menu', 9 );

function wpcf8_admin_menu() {
	global $_wp_last_object_menu;

	$_wp_last_object_menu++;

	add_menu_page( __( 'Contact Form 7', 'contact-form-8' ),
		__( 'Contact', 'contact-form-8' ),
		'wpcf8_read_contact_forms', 'wpcf8',
		'wpcf8_admin_management_page', 'dashicons-email',
		$_wp_last_object_menu );

	$edit = add_submenu_page( 'wpcf8',
		__( 'Edit Contact Form', 'contact-form-8' ),
		__( 'Contact Forms', 'contact-form-8' ),
		'wpcf8_read_contact_forms', 'wpcf8',
		'wpcf8_admin_management_page' );

	add_action( 'load-' . $edit, 'wpcf8_load_contact_form_admin' );

	$addnew = add_submenu_page( 'wpcf8',
		__( 'Add New Contact Form', 'contact-form-8' ),
		__( 'Add New', 'contact-form-8' ),
		'wpcf8_edit_contact_forms', 'wpcf8-new',
		'wpcf8_admin_add_new_page' );

	add_action( 'load-' . $addnew, 'wpcf8_load_contact_form_admin' );

	$integration = wpcf8_Integration::get_instance();

	if ( $integration->service_exists() ) {
		$integration = add_submenu_page( 'wpcf8',
			__( 'Integration with Other Services', 'contact-form-8' ),
			__( 'Integration', 'contact-form-8' ),
			'wpcf8_manage_integration', 'wpcf8-integration',
			'wpcf8_admin_integration_page' );

		add_action( 'load-' . $integration, 'wpcf8_load_integration_page' );
	}
}

add_filter( 'set-screen-option', 'wpcf8_set_screen_options', 10, 3 );

function wpcf8_set_screen_options( $result, $option, $value ) {
	$wpcf8_screens = array(
		'cfseven_contact_forms_per_page' );

	if ( in_array( $option, $wpcf8_screens ) ) {
		$result = $value;
	}

	return $result;
}

function wpcf8_load_contact_form_admin() {
	global $plugin_page;

	$action = wpcf8_current_action();

	if ( 'save' == $action ) {
		$id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : '-1';
		check_admin_referer( 'wpcf8-save-contact-form_' . $id );

		if ( ! current_user_can( 'wpcf8_edit_contact_form', $id ) ) {
			wp_die( __( 'You are not allowed to edit this item.', 'contact-form-8' ) );
		}

		$args = $_REQUEST;
		$args['id'] = $id;

		$args['title'] = isset( $_POST['post_title'] )
			? $_POST['post_title'] : null;

		$args['locale'] = isset( $_POST['wpcf8-locale'] )
			? $_POST['wpcf8-locale'] : null;

		$args['form'] = isset( $_POST['wpcf8-form'] )
			? $_POST['wpcf8-form'] : '';

		$args['mail'] = isset( $_POST['wpcf8-mail'] )
			? wpcf8_sanitize_mail( $_POST['wpcf8-mail'] )
			: array();

		$args['mail_2'] = isset( $_POST['wpcf8-mail-2'] )
			? wpcf8_sanitize_mail( $_POST['wpcf8-mail-2'] )
			: array();

		$args['messages'] = isset( $_POST['wpcf8-messages'] )
			? $_POST['wpcf8-messages'] : array();

		$args['additional_settings'] = isset( $_POST['wpcf8-additional-settings'] )
			? $_POST['wpcf8-additional-settings'] : '';

		$contact_form = wpcf8_save_contact_form( $args );

		if ( $contact_form && wpcf8_validate_configuration() ) {
			$config_validator = new wpcf8_ConfigValidator( $contact_form );
			$config_validator->validate();
			$config_validator->save();
		}

		$query = array(
			'post' => $contact_form ? $contact_form->id() : 0,
			'active-tab' => isset( $_POST['active-tab'] )
				? (int) $_POST['active-tab'] : 0,
		);

		if ( ! $contact_form ) {
			$query['message'] = 'failed';
		} elseif ( -1 == $id ) {
			$query['message'] = 'created';
		} else {
			$query['message'] = 'saved';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wpcf8', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'copy' == $action ) {
		$id = empty( $_POST['post_ID'] )
			? absint( $_REQUEST['post'] )
			: absint( $_POST['post_ID'] );

		check_admin_referer( 'wpcf8-copy-contact-form_' . $id );

		if ( ! current_user_can( 'wpcf8_edit_contact_form', $id ) ) {
			wp_die( __( 'You are not allowed to edit this item.', 'contact-form-8' ) );
		}

		$query = array();

		if ( $contact_form = wpcf8_contact_form( $id ) ) {
			$new_contact_form = $contact_form->copy();
			$new_contact_form->save();

			$query['post'] = $new_contact_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wpcf8', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action ) {
		if ( ! empty( $_POST['post_ID'] ) ) {
			check_admin_referer( 'wpcf8-delete-contact-form_' . $_POST['post_ID'] );
		} elseif ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer( 'wpcf8-delete-contact-form_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$posts = empty( $_POST['post_ID'] )
			? (array) $_REQUEST['post']
			: (array) $_POST['post_ID'];

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = wpcf8_ContactForm::get_instance( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'wpcf8_delete_contact_form', $post->id() ) ) {
				wp_die( __( 'You are not allowed to delete this item.', 'contact-form-8' ) );
			}

			if ( ! $post->delete() ) {
				wp_die( __( 'Error in deleting.', 'contact-form-8' ) );
			}

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) ) {
			$query['message'] = 'deleted';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wpcf8', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'validate' == $action && wpcf8_validate_configuration() ) {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wpcf8-bulk-validate' );

			if ( ! current_user_can( 'wpcf8_edit_contact_forms' ) ) {
				wp_die( __( "You are not allowed to validate configuration.", 'contact-form-8' ) );
			}

			$contact_forms = wpcf8_ContactForm::find();

			$result = array(
				'timestamp' => current_time( 'timestamp' ),
				'version' => wpcf8_VERSION,
				'count_valid' => 0,
				'count_invalid' => 0,
			);

			foreach ( $contact_forms as $contact_form ) {
				$config_validator = new wpcf8_ConfigValidator( $contact_form );
				$config_validator->validate();
				$config_validator->save();

				if ( $config_validator->is_valid() ) {
					$result['count_valid'] += 1;
				} else {
					$result['count_invalid'] += 1;
				}
			}

			wpcf8::update_option( 'bulk_validate', $result );

			$query = array(
				'message' => 'validated' );

			$redirect_to = add_query_arg( $query, menu_page_url( 'wpcf8', false ) );
			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	$_GET['post'] = isset( $_GET['post'] ) ? $_GET['post'] : '';

	$post = null;

	if ( 'wpcf8-new' == $plugin_page ) {
		$post = wpcf8_ContactForm::get_template( array(
			'locale' => isset( $_GET['locale'] ) ? $_GET['locale'] : null ) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$post = wpcf8_ContactForm::get_instance( $_GET['post'] );
	}

	$current_screen = get_current_screen();

	$help_tabs = new wpcf8_Help_Tabs( $current_screen );

	if ( $post && current_user_can( 'wpcf8_edit_contact_form', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		$help_tabs->set_help_tabs( 'list' );

		if ( ! class_exists( 'wpcf8_Contact_Form_List_Table' ) ) {
			require_once wpcf8_PLUGIN_DIR . '/admin/includes/class-contact-forms-list-table.php';
		}

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'wpcf8_Contact_Form_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'cfseven_contact_forms_per_page' ) );
	}
}

add_action( 'admin_enqueue_scripts', 'wpcf8_admin_enqueue_scripts' );

function wpcf8_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'wpcf8' ) ) {
		return;
	}

	wp_enqueue_style( 'contact-form-8-admin',
		wpcf8_plugin_url( 'admin/css/styles.css' ),
		array(), wpcf8_VERSION, 'all' );

	if ( wpcf8_is_rtl() ) {
		wp_enqueue_style( 'contact-form-8-admin-rtl',
			wpcf8_plugin_url( 'admin/css/styles-rtl.css' ),
			array(), wpcf8_VERSION, 'all' );
	}

	wp_enqueue_script( 'wpcf8-admin',
		wpcf8_plugin_url( 'admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		wpcf8_VERSION, true );

	$args = array(
		'apiSettings' => array(
			'root' => esc_url_raw( get_rest_url() ),
			'nonce' => ( wp_installing() && ! is_multisite() )
				? '' : wp_create_nonce( 'wp_rest' ) ),
		'pluginUrl' => wpcf8_plugin_url(),
		'saveAlert' => __(
			"The changes you made will be lost if you navigate away from this page.",
			'contact-form-8' ),
		'activeTab' => isset( $_GET['active-tab'] )
			? (int) $_GET['active-tab'] : 0,
		'configValidator' => array(
			'errors' => array(),
			'howToCorrect' => __( "How to correct this?", 'contact-form-8' ),
			'oneError' => __( '1 configuration error detected', 'contact-form-8' ),
			'manyErrors' => __( '%d configuration errors detected', 'contact-form-8' ),
			'oneErrorInTab' => __( '1 configuration error detected in this tab panel', 'contact-form-8' ),
			'manyErrorsInTab' => __( '%d configuration errors detected in this tab panel', 'contact-form-8' ),
			'docUrl' => wpcf8_ConfigValidator::get_doc_link(),
		),
	);

	if ( ( $post = wpcf8_get_current_contact_form() )
	&& current_user_can( 'wpcf8_edit_contact_form', $post->id() )
	&& wpcf8_validate_configuration() ) {
		$config_validator = new wpcf8_ConfigValidator( $post );
		$config_validator->restore();
		$args['configValidator']['errors'] =
			$config_validator->collect_error_messages();
	}

	wp_localize_script( 'wpcf8-admin', '_wpcf8', $args );

	add_thickbox();

	wp_enqueue_script( 'wpcf8-admin-taggenerator',
		wpcf8_plugin_url( 'admin/js/tag-generator.js' ),
		array( 'jquery', 'thickbox', 'wpcf8-admin' ), wpcf8_VERSION, true );
}

function wpcf8_admin_management_page() {
	if ( $post = wpcf8_get_current_contact_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once wpcf8_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once wpcf8_PLUGIN_DIR . '/admin/edit-contact-form.php';
		return;
	}

	if ( 'validate' == wpcf8_current_action()
	&& wpcf8_validate_configuration()
	&& current_user_can( 'wpcf8_edit_contact_forms' ) ) {
		wpcf8_admin_bulk_validate_page();
		return;
	}

	$list_table = new wpcf8_Contact_Form_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1><?php
	echo esc_html( __( 'Contact Forms', 'contact-form-8' ) );

	if ( current_user_can( 'wpcf8_edit_contact_forms' ) ) {
		echo ' <a href="' . esc_url( menu_page_url( 'wpcf8-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New', 'contact-form-8' ) ) . '</a>';
	}

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'contact-form-8' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h1>

<?php do_action( 'wpcf8_admin_warnings' ); ?>
<?php wpcf8_welcome_panel(); ?>
<?php do_action( 'wpcf8_admin_notices' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Contact Forms', 'contact-form-8' ), 'wpcf8-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function wpcf8_admin_bulk_validate_page() {
	$contact_forms = wpcf8_ContactForm::find();
	$count = wpcf8_ContactForm::count();

	$submit_text = sprintf(
		_n(
			"Validate %s Contact Form Now",
			"Validate %s Contact Forms Now",
			$count, 'contact-form-8' ),
		number_format_i18n( $count ) );

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Validate Configuration', 'contact-form-8' ) ); ?></h1>

<form method="post" action="">
	<input type="hidden" name="action" value="validate" />
	<?php wp_nonce_field( 'wpcf8-bulk-validate' ); ?>
	<p><input type="submit" class="button" value="<?php echo esc_attr( $submit_text ); ?>" /></p>
</form>

<?php echo wpcf8_link( __( 'https://contactform7.com/configuration-validator-faq/', 'contact-form-8' ), __( 'FAQ about Configuration Validator', 'contact-form-8' ) ); ?>

</div>
<?php
}

function wpcf8_admin_add_new_page() {
	$post = wpcf8_get_current_contact_form();

	if ( ! $post ) {
		$post = wpcf8_ContactForm::get_template();
	}

	$post_id = -1;

	require_once wpcf8_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once wpcf8_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

function wpcf8_load_integration_page() {
	$integration = wpcf8_Integration::get_instance();

	if ( isset( $_REQUEST['service'] )
	&& $integration->service_exists( $_REQUEST['service'] ) ) {
		$service = $integration->get_service( $_REQUEST['service'] );
		$service->load( wpcf8_current_action() );
	}

	$help_tabs = new wpcf8_Help_Tabs( get_current_screen() );
	$help_tabs->set_help_tabs( 'integration' );
}

function wpcf8_admin_integration_page() {
	$integration = wpcf8_Integration::get_instance();

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Integration with Other Services', 'contact-form-8' ) ); ?></h1>

<?php do_action( 'wpcf8_admin_warnings' ); ?>
<?php do_action( 'wpcf8_admin_notices' ); ?>

<?php
	if ( isset( $_REQUEST['service'] )
	&& $service = $integration->get_service( $_REQUEST['service'] ) ) {
		$message = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '';
		$service->admin_notice( $message );
		$integration->list_services( array( 'include' => $_REQUEST['service'] ) );
	} else {
		$integration->list_services();
	}
?>

</div>
<?php
}

/* Misc */

add_action( 'wpcf8_admin_notices', 'wpcf8_admin_updated_message' );

function wpcf8_admin_updated_message() {
	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'created' == $_REQUEST['message'] ) {
		$updated_message = __( "Contact form created.", 'contact-form-8' );
	} elseif ( 'saved' == $_REQUEST['message'] ) {
		$updated_message = __( "Contact form saved.", 'contact-form-8' );
	} elseif ( 'deleted' == $_REQUEST['message'] ) {
		$updated_message = __( "Contact form deleted.", 'contact-form-8' );
	}

	if ( ! empty( $updated_message ) ) {
		echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		return;
	}

	if ( 'failed' == $_REQUEST['message'] ) {
		$updated_message = __( "There was an error saving the contact form.",
			'contact-form-8' );

		echo sprintf( '<div id="message" class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		return;
	}

	if ( 'validated' == $_REQUEST['message'] ) {
		$bulk_validate = wpcf8::get_option( 'bulk_validate', array() );
		$count_invalid = isset( $bulk_validate['count_invalid'] )
			? absint( $bulk_validate['count_invalid'] ) : 0;

		if ( $count_invalid ) {
			$updated_message = sprintf(
				_n(
					"Configuration validation completed. An invalid contact form was found.",
					"Configuration validation completed. %s invalid contact forms were found.",
					$count_invalid, 'contact-form-8' ),
				number_format_i18n( $count_invalid ) );

			echo sprintf( '<div id="message" class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		} else {
			$updated_message = __( "Configuration validation completed. No invalid contact form was found.", 'contact-form-8' );

			echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		}

		return;
	}
}

add_filter( 'plugin_action_links', 'wpcf8_plugin_action_links', 10, 2 );

function wpcf8_plugin_action_links( $links, $file ) {
	if ( $file != wpcf8_PLUGIN_BASENAME ) {
		return $links;
	}

	$settings_link = '<a href="' . menu_page_url( 'wpcf8', false ) . '">'
		. esc_html( __( 'Settings', 'contact-form-8' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'wpcf8_admin_warnings', 'wpcf8_old_wp_version_error' );

function wpcf8_old_wp_version_error() {
	$wp_version = get_bloginfo( 'version' );

	if ( ! version_compare( $wp_version, wpcf8_REQUIRED_WP_VERSION, '<' ) ) {
		return;
	}

?>
<div class="notice notice-warning">
<p><?php echo sprintf( __( '<strong>Contact Form 7 %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'contact-form-8' ), wpcf8_VERSION, wpcf8_REQUIRED_WP_VERSION, admin_url( 'update-core.php' ) ); ?></p>
</div>
<?php
}

add_action( 'wpcf8_admin_warnings', 'wpcf8_not_allowed_to_edit' );

function wpcf8_not_allowed_to_edit() {
	if ( ! $contact_form = wpcf8_get_current_contact_form() ) {
		return;
	}

	$post_id = $contact_form->id();

	if ( current_user_can( 'wpcf8_edit_contact_form', $post_id ) ) {
		return;
	}

	$message = __( "You are not allowed to edit this contact form.",
		'contact-form-8' );

	echo sprintf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html( $message ) );
}

add_action( 'wpcf8_admin_warnings', 'wpcf8_notice_bulk_validate_config', 5 );

function wpcf8_notice_bulk_validate_config() {
	if ( ! wpcf8_validate_configuration()
	|| ! current_user_can( 'wpcf8_edit_contact_forms' ) ) {
		return;
	}

	if ( isset( $_GET['page'] ) && 'wpcf8' == $_GET['page']
	&& isset( $_GET['action'] ) && 'validate' == $_GET['action'] ) {
		return;
	}

	if ( wpcf8::get_option( 'bulk_validate' ) ) { // already done.
		return;
	}

	$link = add_query_arg(
		array( 'action' => 'validate' ),
		menu_page_url( 'wpcf8', false ) );

	$link = sprintf( '<a href="%s">%s</a>', $link, esc_html( __( 'Validate Contact Form 7 Configuration', 'contact-form-8' ) ) );

	$message = __( "Misconfiguration leads to mail delivery failure or other troubles. Validate your contact forms now.", 'contact-form-8' );

	echo sprintf( '<div class="notice notice-warning"><p>%s &raquo; %s</p></div>',
		esc_html( $message ), $link );
}
