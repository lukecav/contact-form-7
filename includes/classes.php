<?php

class WPCF7_ContactForm {

	var $title;
	var $form;
	var $mail;
	var $mail_2;
	var $messages;
	var $options;

	/* Form Elements */

	function form_elements( $replace = true ) {
		$form = $this->form;

		$types = 'text[*]?|email[*]?|textarea[*]?|select[*]?|checkbox[*]?|radio|acceptance|captchac|captchar|file[*]?|quiz';
		$regex = '%\[\s*(' . $types . ')(\s+[a-zA-Z][0-9a-zA-Z:._-]*)([-0-9a-zA-Z:#_/|\s]*)?((?:\s*(?:"[^"]*"|\'[^\']*\'))*)?\s*\]%';
		$submit_regex = '%\[\s*submit(\s[-0-9a-zA-Z:#_/\s]*)?(\s+(?:"[^"]*"|\'[^\']*\'))?\s*\]%';
		if ( $replace ) {
			$form = preg_replace_callback( $regex, array( &$this, 'form_element_replace_callback' ), $form );
			// Submit button
			$form = preg_replace_callback( $submit_regex, array( &$this, 'submit_replace_callback' ), $form );
			return $form;
		} else {
			$results = array();
			preg_match_all( $regex, $form, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$results[] = (array) $this->form_element_parse( $match );
			}
			return $results;
		}
	}

	function form_element_replace_callback( $matches ) {
		global $wpcf7;

		extract( (array) $this->form_element_parse( $matches ) ); // $type, $name, $options, $values, $raw_values

		if ( $wpcf7->processing_unit_tag == $_POST['_wpcf7_unit_tag'] ) {
			$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
			$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . $validation_error . '</span>' : '';
		} else {
			$validation_error = '';
		}

		$atts = '';
		$options = (array) $options;

		$id_array = preg_grep( '%^id:[-0-9a-zA-Z_]+$%', $options );
		if ( $id = array_shift( $id_array ) ) {
			preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches );
			if ( $id = $id_matches[1] )
				$atts .= ' id="' . $id . '"';
		}

		$class_att = "";
		$class_array = preg_grep( '%^class:[-0-9a-zA-Z_]+$%', $options );
		foreach ( $class_array as $class ) {
			preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches );
			if ( $class = $class_matches[1] )
				$class_att .= ' ' . $class;
		}

		if ( preg_match( '/^email[*]?$/', $type ) )
			$class_att .= ' wpcf7-validates-as-email';
		if ( preg_match( '/[*]$/', $type ) )
			$class_att .= ' wpcf7-validates-as-required';

		if ( preg_match( '/^checkbox[*]?$/', $type ) )
			$class_att .= ' wpcf7-checkbox';

		if ( 'radio' == $type )
			$class_att .= ' wpcf7-radio';

		if ( preg_match( '/^captchac$/', $type ) )
			$class_att .= ' wpcf7-captcha-' . $name;

		if ( 'acceptance' == $type ) {
			$class_att .= ' wpcf7-acceptance';
			if ( preg_grep( '%^invert$%', $options ) )
				$class_att .= ' wpcf7-invert';
		}

		if ( $class_att )
			$atts .= ' class="' . trim( $class_att ) . '"';

		// Value.
		if ( $wpcf7->processing_unit_tag == $_POST['_wpcf7_unit_tag'] ) {
			if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
				$value = '';
			elseif ( 'captchar' == $type )
				$value = '';
			else
				$value = $_POST[$name];
		} else {
			$value = $values[0];
		}

		// Default selected/checked for select/checkbox/radio
		if ( preg_match( '/^(?:select|checkbox|radio)[*]?$/', $type ) ) {
			$scr_defaults = array_values( preg_grep( '/^default:/', $options ) );
			preg_match( '/^default:([0-9_]+)$/', $scr_defaults[0], $scr_default_matches );
			$scr_default = explode( '_', $scr_default_matches[1] );
		}

		switch ( $type ) {
			case 'text':
			case 'text*':
			case 'email':
			case 'email*':
			case 'captchar':
				if ( is_array( $options ) ) {
					$size_maxlength_array = preg_grep( '%^[0-9]*[/x][0-9]*$%', $options );
					if ( $size_maxlength = array_shift( $size_maxlength_array ) ) {
						preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches );
						if ( $size = (int) $sm_matches[1] )
							$atts .= ' size="' . $size . '"';
						else
							$atts .= ' size="40"';
						if ( $maxlength = (int) $sm_matches[2] )
							$atts .= ' maxlength="' . $maxlength . '"';
					} else {
						$atts .= ' size="40"';
					}
				}
				$html = '<input type="text" name="' . $name . '" value="' . attribute_escape( $value ) . '"' . $atts . ' />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'textarea':
			case 'textarea*':
				if ( is_array( $options ) ) {
					$cols_rows_array = preg_grep( '%^[0-9]*[x/][0-9]*$%', $options );
					if ( $cols_rows = array_shift( $cols_rows_array ) ) {
						preg_match( '%^([0-9]*)[x/]([0-9]*)$%', $cols_rows, $cr_matches );
						if ( $cols = (int) $cr_matches[1] )
							$atts .= ' cols="' . $cols . '"';
						else
							$atts .= ' cols="40"';
						if ( $rows = (int) $cr_matches[2] )
							$atts .= ' rows="' . $rows . '"';
						else
							$atts .= ' rows="10"';
					} else {
							$atts .= ' cols="40" rows="10"';
					}
				}
				$html = '<textarea name="' . $name . '"' . $atts . '>' . $value . '</textarea>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'select':
			case 'select*':
				$multiple = ( preg_grep( '%^multiple$%', $options ) ) ? true : false;
				$include_blank = preg_grep( '%^include_blank$%', $options );

				if ( $empty_select = empty( $values ) || $include_blank )
					array_unshift( $values, '---' );

				$html = '';
				foreach ( $values as $key => $value ) {
					$selected = '';
					if ( ! $empty_select && in_array( $key + 1, (array) $scr_default ) )
						$selected = ' selected="selected"';
					if ( $wpcf7->processing_unit_tag == $_POST['_wpcf7_unit_tag'] && (
						$multiple && in_array( $value, (array) $_POST[$name] ) ||
							! $multiple && $_POST[$name] == $value ) )
						$selected = ' selected="selected"';
					$html .= '<option value="' . attribute_escape( $value ) . '"' . $selected . '>' . $value . '</option>';
				}

				if ( $multiple )
					$atts .= ' multiple="multiple"';

				$html = '<select name="' . $name . ( $multiple ? '[]' : '' ) . '"' . $atts . '>' . $html . '</select>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'checkbox':
			case 'checkbox*':
			case 'radio':
				$multiple = ( preg_match( '/^checkbox[*]?$/', $type ) && ! preg_grep( '%^exclusive$%', $options ) ) ? true : false;
				$html = '';

				if ( preg_match( '/^checkbox[*]?$/', $type ) && ! $multiple )
					$onclick = ' onclick="wpcf7ExclusiveCheckbox(this);"';

				$input_type = rtrim( $type, '*' );

				foreach ( $values as $key => $value ) {
					$checked = '';
					if ( in_array( $key + 1, (array) $scr_default ) )
						$checked = ' checked="checked"';
					if ( $wpcf7->processing_unit_tag == $_POST['_wpcf7_unit_tag'] && (
						$multiple && in_array( $value, (array) $_POST[$name] ) ||
							! $multiple && $_POST[$name] == $value ) )
						$checked = ' checked="checked"';
					if ( preg_grep( '%^label[_-]?first$%', $options ) ) { // put label first, input last
						$item = '<span class="wpcf7-list-item-label">' . $value . '</span>&nbsp;';
						$item .= '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . attribute_escape( $value ) . '"' . $checked . $onclick . ' />';
					} else {
						$item = '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . attribute_escape( $value ) . '"' . $checked . $onclick . ' />';
						$item .= '&nbsp;<span class="wpcf7-list-item-label">' . $value . '</span>';
					}
					$item = '<span class="wpcf7-list-item">' . $item . '</span>';
					$html .= $item;
				}

				$html = '<span' . $atts . '>' . $html . '</span>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'quiz':
				if ( count( $raw_values ) == 0 && count( $values ) == 0 ) { // default quiz
					$raw_values[] = '1+1=?|2';
					$values[] = '1+1=?';
				}

				$pipes = wpcf7_get_pipes( $raw_values );

				if ( count( $values ) == 0 ) {
					break;
				} elseif ( count( $values ) == 1 ) {
					$value = $values[0];
				} else {
					$value = $values[array_rand( $values )];
				}

				$answer = wpcf7_pipe( $pipes, $value );
				$answer = wpcf7_canonicalize( $answer );

				if ( is_array( $options ) ) {
					$size_maxlength_array = preg_grep( '%^[0-9]*[/x][0-9]*$%', $options );
					if ( $size_maxlength = array_shift( $size_maxlength_array ) ) {
						preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches );
						if ( $size = (int) $sm_matches[1] )
							$atts .= ' size="' . $size . '"';
						else
							$atts .= ' size="40"';
						if ( $maxlength = (int) $sm_matches[2] )
							$atts .= ' maxlength="' . $maxlength . '"';
					} else {
						$atts .= ' size="40"';
					}
				}
                
				$html = '<span class="wpcf7-quiz-label">' . $value . '</span>&nbsp;';
				$html .= '<input type="text" name="' . $name . '"' . $atts . ' />';
				$html .= '<input type="hidden" name="_wpcf7_quiz_answer_' . $name . '" value="' . wp_hash( $answer, 'wpcf7_quiz' ) . '" />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'acceptance':
				$invert = (bool) preg_grep( '%^invert$%', $options );
				$default = (bool) preg_grep( '%^default:on$%', $options );

				$onclick = ' onclick="wpcf7ToggleSubmit(this.form);"';
				$checked = $default ? ' checked="checked"' : '';
				$html = '<input type="checkbox" name="' . $name . '" value="1"' . $atts . $onclick . $checked . ' />';
				return $html;
				break;
			case 'captchac':
				if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
					return '<em>' . __( 'To use CAPTCHA, you need <a href="http://wordpress.org/extend/plugins/really-simple-captcha/">Really Simple CAPTCHA</a> plugin installed.', 'wpcf7' ) . '</em>';
					break;
				}

				$op = array();
				// Default
				$op['img_size'] = array( 72, 24 );
				$op['base'] = array( 6, 18 );
				$op['font_size'] = 14;
				$op['font_char_width'] = 15;

				$op = array_merge( $op, wpcf7_captchac_options( $options ) );

				if ( ! $filename = wpcf7_generate_captcha( $op ) ) {
					return '';
					break;
				}
				if ( is_array( $op['img_size'] ) )
					$atts .= ' width="' . $op['img_size'][0] . '" height="' . $op['img_size'][1] . '"';
				$captcha_url = trailingslashit( wpcf7_captcha_tmp_url() ) . $filename;
				$html = '<img alt="captcha" src="' . $captcha_url . '"' . $atts . ' />';
				$ref = substr( $filename, 0, strrpos( $filename, '.' ) );
				$html = '<input type="hidden" name="_wpcf7_captcha_challenge_' . $name . '" value="' . $ref . '" />' . $html;
				return $html;
				break;
			case 'file':
			case 'file*':
				$html = '<input type="file" name="' . $name . '"' . $atts . ' value="1" />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
		}
	}

	function submit_replace_callback( $matches ) {
		$atts = '';
		$options = preg_split( '/[\s]+/', trim( $matches[1] ) );

		$id_array = preg_grep( '%^id:[-0-9a-zA-Z_]+$%', $options );
		if ( $id = array_shift( $id_array ) ) {
			preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches );
			if ( $id = $id_matches[1] )
				$atts .= ' id="' . $id . '"';
		}

		$class_att = '';
		$class_array = preg_grep( '%^class:[-0-9a-zA-Z_]+$%', $options );
		foreach ( $class_array as $class ) {
			preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches );
			if ( $class = $class_matches[1] )
				$class_att .= ' ' . $class;
		} 

		if ( $class_att )
			$atts .= ' class="' . trim( $class_att ) . '"';

		if ( $matches[2] )
			$value = wpcf7_strip_quote( $matches[2] );
		if ( empty( $value ) )
			$value = __( 'Send', 'wpcf7' );
		$ajax_loader_image_url = WPCF7_PLUGIN_URL . '/images/ajax-loader.gif';

		$html = '<input type="submit" value="' . $value . '"' . $atts . ' />';
		$html .= ' <img class="ajax-loader" style="visibility: hidden;" alt="ajax loader" src="' . $ajax_loader_image_url . '" />';
		return $html;
	}

	function form_element_parse( $element ) {
		$type = trim( $element[1] );
		$name = trim( $element[2] );
		$options = preg_split( '/[\s]+/', trim( $element[3] ) );

		preg_match_all( '/"[^"]*"|\'[^\']*\'/', $element[4], $matches );
		$raw_values = wpcf7_strip_quote_deep( $matches[0] );

		if ( WPCF7_USE_PIPE && preg_match( '/^(select[*]?|checkbox[*]?|radio)$/', $type ) || 'quiz' == $type ) {
			$pipes = wpcf7_get_pipes( $raw_values );
			$values = wpcf7_get_pipe_ins( $pipes );
		} else {
			$values =& $raw_values;
		}

		return compact( 'type', 'name', 'options', 'values', 'raw_values' );
	}

	/* Validate */

	function validate() {
		$fes = $this->form_elements( false );
		$valid = true;
		$reason = array();

		foreach ( $fes as $fe ) {
			$type = $fe['type'];
			$name = $fe['name'];
			$values = $fe['values'];
			$raw_values = $fe['raw_values'];

			// Before validation corrections
			if ( preg_match( '/^(?:text|email|captchar|textarea)[*]?$/', $type ) )
				$_POST[$name] = (string) $_POST[$name];

			if ( preg_match( '/^(?:text|email)[*]?$/', $type ) )
				$_POST[$name] = trim( strtr( $_POST[$name], "\n", " " ) );

			if ( preg_match( '/^(?:select|checkbox|radio)[*]?$/', $type ) ) {
				if ( is_array( $_POST[$name] ) ) {
					foreach ( $_POST[$name] as $key => $value ) {
						$value = stripslashes( $value );
						if ( ! in_array( $value, (array) $values ) ) // Not in given choices.
							unset( $_POST[$name][$key] );
					}
				} else {
					$value = stripslashes( $_POST[$name] );
					if ( ! in_array( $value, (array) $values ) ) //  Not in given choices.
						$_POST[$name] = '';
				}
			}

			if ( 'acceptance' == $type )
				$_POST[$name] = $_POST[$name] ? 1 : 0;

			// Required item (*)
			if ( preg_match( '/^(?:text|textarea)[*]$/', $type ) ) {
				if ( ! isset( $_POST[$name] ) || '' == $_POST[$name] ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				}
			}

			if ( 'checkbox*' == $type ) {
				if ( empty( $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				}
			}

			if ( 'select*' == $type ) {
				if ( empty( $_POST[$name] ) ||
						! is_array( $_POST[$name] ) && '---' == $_POST[$name] ||
						is_array( $_POST[$name] ) && 1 == count( $_POST[$name] ) && '---' == $_POST[$name][0] ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				}
			}

			if ( preg_match( '/^email[*]?$/', $type ) ) {
				if ( '*' == substr( $type, -1 ) && ( ! isset( $_POST[$name] ) || '' == $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				} elseif ( isset( $_POST[$name] ) && '' != $_POST[$name] && ! is_email( $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_email' );
				}
			}

			if ( preg_match( '/^captchar$/', $type ) ) {
				$captchac = '_wpcf7_captcha_challenge_' . $name;
				if ( ! wpcf7_check_captcha( $_POST[$captchac], $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'captcha_not_match' );
				}
				wpcf7_remove_captcha( $_POST[$captchac] );
			}

			if ( 'quiz' == $type ) {
				$answer = wpcf7_canonicalize( $_POST[$name] );
				$answer_hash = wp_hash( $answer, 'wpcf7_quiz' );
				$expected_hash = $_POST['_wpcf7_quiz_answer_' . $name];
				if ( $answer_hash != $expected_hash ) {
					$valid = false;
					$reason[$name] = $this->message( 'quiz_answer_not_correct' );
				}
			}
		}
		return compact( 'valid', 'reason' );
	}

	/* Message */

	function message( $status ) {
		$messages = $this->messages;

		if ( ! is_array( $messages ) || ! isset( $messages[$status] ) )
			return wpcf7_default_message( $status );

		return $messages[$status];
	}

	/* Upgrade */

	function upgrade() {
		$this->upgrade_160();
		$this->upgrade_181();
		$this->upgrade_190();
		$this->upgrade_192();
	}

	function upgrade_160() {
		if ( ! isset( $this->mail['recipient'] ) )
			$this->mail['recipient'] = $this->options['recipient'];
	}

	function upgrade_181() {
		if ( ! is_array( $this->messages ) )
			$this->messages = array(
				'mail_sent_ok' => wpcf7_default_message( 'mail_sent_ok' ),
				'mail_sent_ng' => wpcf7_default_message( 'mail_sent_ng' ),
				'akismet_says_spam' => wpcf7_default_message( 'akismet_says_spam' ),
				'validation_error' => wpcf7_default_message( 'validation_error' ),
				'accept_terms' => wpcf7_default_message( 'accept_terms' ),
				'invalid_email' => wpcf7_default_message( 'invalid_email' ),
				'invalid_required' => wpcf7_default_message( 'invalid_required' ),
				'captcha_not_match' => wpcf7_default_message( 'captcha_not_match' )
			);
	}

	function upgrade_190() {
		if ( ! is_array( $this->messages ) )
			$this->messages = array();

		if ( ! isset( $this->messages['upload_failed'] ) )
			$this->messages['upload_failed'] = wpcf7_default_message( 'upload_failed' );

		if ( ! isset( $this->messages['upload_file_type_invalid'] ) )
			$this->messages['upload_file_type_invalid'] = wpcf7_default_message( 'upload_file_type_invalid' );

		if ( ! isset( $this->messages['upload_file_too_large'] ) )
			$this->messages['upload_file_too_large'] = wpcf7_default_message( 'upload_file_too_large' );
	}

	function upgrade_192() {
		if ( ! is_array( $this->messages ) )
			$this->messages = array();

		if ( ! isset( $this->messages['quiz_answer_not_correct'] ) )
			$this->messages['quiz_answer_not_correct'] = wpcf7_default_message( 'quiz_answer_not_correct' );
	}

}

?>