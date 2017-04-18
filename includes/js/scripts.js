( function( $ ) {

	'use strict';

	if ( typeof _wpcf8 === 'undefined' || _wpcf8 === null ) {
		return;
	}

	_wpcf8 = $.extend( {
		cached: 0,
		inputs: []
	}, _wpcf8 );

	$.fn.wpcf8InitForm = function() {
		this.ajaxForm( {
			beforeSubmit: function( arr, $form, options ) {
				$form.wpcf8ClearResponseOutput();
				$form.find( '[aria-invalid]' ).attr( 'aria-invalid', 'false' );
				$form.find( '.ajax-loader' ).addClass( 'is-active' );
				return true;
			},
			beforeSerialize: function( $form, options ) {
				$form.find( '[placeholder].placeheld' ).each( function( i, n ) {
					$( n ).val( '' );
				} );
				return true;
			},
			data: { '_wpcf8_is_ajax_call': 1 },
			dataType: 'json',
			success: $.wpcf8AjaxSuccess,
			error: function( xhr, status, error, $form ) {
				var e = $( '<div class="ajax-error"></div>' ).text( error.message );
				$form.after( e );
			}
		} );

		if ( _wpcf8.cached ) {
			this.wpcf8OnloadRefill();
		}

		this.wpcf8ToggleSubmit();

		this.find( '.wpcf8-submit' ).wpcf8AjaxLoader();

		this.find( '.wpcf8-acceptance' ).click( function() {
			$( this ).closest( 'form' ).wpcf8ToggleSubmit();
		} );

		this.find( '.wpcf8-exclusive-checkbox' ).wpcf8ExclusiveCheckbox();

		this.find( '.wpcf8-list-item.has-free-text' ).wpcf8ToggleCheckboxFreetext();

		this.find( '[placeholder]' ).wpcf8Placeholder();

		if ( _wpcf8.jqueryUi && ! _wpcf8.supportHtml5.date ) {
			this.find( 'input.wpcf8-date[type="date"]' ).each( function() {
				$( this ).datepicker( {
					dateFormat: 'yy-mm-dd',
					minDate: new Date( $( this ).attr( 'min' ) ),
					maxDate: new Date( $( this ).attr( 'max' ) )
				} );
			} );
		}

		if ( _wpcf8.jqueryUi && ! _wpcf8.supportHtml5.number ) {
			this.find( 'input.wpcf8-number[type="number"]' ).each( function() {
				$( this ).spinner( {
					min: $( this ).attr( 'min' ),
					max: $( this ).attr( 'max' ),
					step: $( this ).attr( 'step' )
				} );
			} );
		}

		this.find( '.wpcf8-character-count' ).wpcf8CharacterCount();

		this.find( '.wpcf8-validates-as-url' ).change( function() {
			$( this ).wpcf8NormalizeUrl();
		} );

		this.find( '.wpcf8-recaptcha' ).wpcf8Recaptcha();
	};

	$.wpcf8AjaxSuccess = function( data, status, xhr, $form ) {
		if ( ! $.isPlainObject( data ) || $.isEmptyObject( data ) ) {
			return;
		}

		_wpcf8.inputs = $form.serializeArray();

		var $responseOutput = $form.find( 'div.wpcf8-response-output' );

		$form.wpcf8ClearResponseOutput();

		$form.find( '.wpcf8-form-control' ).removeClass( 'wpcf8-not-valid' );
		$form.removeClass( 'invalid spam sent failed' );

		if ( data.captcha ) {
			$form.wpcf8RefillCaptcha( data.captcha );
		}

		if ( data.quiz ) {
			$form.wpcf8RefillQuiz( data.quiz );
		}

		if ( data.invalids ) {
			$.each( data.invalids, function( i, n ) {
				$form.find( n.into ).wpcf8NotValidTip( n.message );
				$form.find( n.into ).find( '.wpcf8-form-control' ).addClass( 'wpcf8-not-valid' );
				$form.find( n.into ).find( '[aria-invalid]' ).attr( 'aria-invalid', 'true' );
			} );

			$responseOutput.addClass( 'wpcf8-validation-errors' );
			$form.addClass( 'invalid' );

			$( data.into ).wpcf8TriggerEvent( 'invalid' );

		} else if ( 1 == data.spam ) {
			$form.find( '[name="g-recaptcha-response"]' ).each( function() {
				if ( '' == $( this ).val() ) {
					var $recaptcha = $( this ).closest( '.wpcf8-form-control-wrap' );
					$recaptcha.wpcf8NotValidTip( _wpcf8.recaptcha.messages.empty );
				}
			} );

			$responseOutput.addClass( 'wpcf8-spam-blocked' );
			$form.addClass( 'spam' );

			$( data.into ).wpcf8TriggerEvent( 'spam' );

		} else if ( 1 == data.mailSent ) {
			$responseOutput.addClass( 'wpcf8-mail-sent-ok' );
			$form.addClass( 'sent' );

			if ( data.onSentOk ) {
				$.each( data.onSentOk, function( i, n ) { eval( n ) } );
			}

			$( data.into ).wpcf8TriggerEvent( 'mailsent' );

		} else {
			$responseOutput.addClass( 'wpcf8-mail-sent-ng' );
			$form.addClass( 'failed' );

			$( data.into ).wpcf8TriggerEvent( 'mailfailed' );
		}

		if ( data.onSubmit ) {
			$.each( data.onSubmit, function( i, n ) { eval( n ) } );
		}

		$( data.into ).wpcf8TriggerEvent( 'submit' );

		if ( 1 == data.mailSent ) {
			$form.resetForm();
		}

		$form.find( '[placeholder].placeheld' ).each( function( i, n ) {
			$( n ).val( $( n ).attr( 'placeholder' ) );
		} );

		$responseOutput.append( data.message ).slideDown( 'fast' );
		$responseOutput.attr( 'role', 'alert' );

		$.wpcf8UpdateScreenReaderResponse( $form, data );
	};

	$.fn.wpcf8TriggerEvent = function( name ) {
		return this.each( function() {
			var elmId = this.id;
			var inputs = _wpcf8.inputs;

			/* DOM event */
			var event = new CustomEvent( 'wpcf8' + name, {
				bubbles: true,
				detail: {
					id: elmId,
					inputs: inputs
				}
			} );

			this.dispatchEvent( event );

			/* jQuery event */
			$( this ).trigger( 'wpcf8:' + name );
			$( this ).trigger( name + '.wpcf8' ); // deprecated
		} );
	};

	$.fn.wpcf8ExclusiveCheckbox = function() {
		return this.find( 'input:checkbox' ).click( function() {
			var name = $( this ).attr( 'name' );
			$( this ).closest( 'form' ).find( 'input:checkbox[name="' + name + '"]' ).not( this ).prop( 'checked', false );
		} );
	};

	$.fn.wpcf8Placeholder = function() {
		if ( _wpcf8.supportHtml5.placeholder ) {
			return this;
		}

		return this.each( function() {
			$( this ).val( $( this ).attr( 'placeholder' ) );
			$( this ).addClass( 'placeheld' );

			$( this ).focus( function() {
				if ( $( this ).hasClass( 'placeheld' ) ) {
					$( this ).val( '' ).removeClass( 'placeheld' );
				}
			} );

			$( this ).blur( function() {
				if ( '' === $( this ).val() ) {
					$( this ).val( $( this ).attr( 'placeholder' ) );
					$( this ).addClass( 'placeheld' );
				}
			} );
		} );
	};

	$.fn.wpcf8AjaxLoader = function() {
		return this.each( function() {
			$( this ).after( '<span class="ajax-loader"></span>' );
		} );
	};

	$.fn.wpcf8ToggleSubmit = function() {
		return this.each( function() {
			var form = $( this );

			if ( this.tagName.toLowerCase() != 'form' ) {
				form = $( this ).find( 'form' ).first();
			}

			if ( form.hasClass( 'wpcf8-acceptance-as-validation' ) ) {
				return;
			}

			var submit = form.find( 'input:submit' );

			if ( ! submit.length ) {
				return;
			}

			var acceptances = form.find( 'input:checkbox.wpcf8-acceptance' );

			if ( ! acceptances.length ) {
				return;
			}

			submit.removeAttr( 'disabled' );
			acceptances.each( function( i, n ) {
				n = $( n );

				if ( n.hasClass( 'wpcf8-invert' ) && n.is( ':checked' )
						|| ! n.hasClass( 'wpcf8-invert' ) && ! n.is( ':checked' ) ) {
					submit.attr( 'disabled', 'disabled' );
				}
			} );
		} );
	};

	$.fn.wpcf8ToggleCheckboxFreetext = function() {
		return this.each( function() {
			var $wrap = $( this ).closest( '.wpcf8-form-control' );

			if ( $( this ).find( ':checkbox, :radio' ).is( ':checked' ) ) {
				$( this ).find( ':input.wpcf8-free-text' ).prop( 'disabled', false );
			} else {
				$( this ).find( ':input.wpcf8-free-text' ).prop( 'disabled', true );
			}

			$wrap.find( ':checkbox, :radio' ).change( function() {
				var $cb = $( '.has-free-text', $wrap ).find( ':checkbox, :radio' );
				var $freetext = $( ':input.wpcf8-free-text', $wrap );

				if ( $cb.is( ':checked' ) ) {
					$freetext.prop( 'disabled', false ).focus();
				} else {
					$freetext.prop( 'disabled', true );
				}
			} );
		} );
	};

	$.fn.wpcf8CharacterCount = function() {
		return this.each( function() {
			var $count = $( this );
			var name = $count.attr( 'data-target-name' );
			var down = $count.hasClass( 'down' );
			var starting = parseInt( $count.attr( 'data-starting-value' ), 10 );
			var maximum = parseInt( $count.attr( 'data-maximum-value' ), 10 );
			var minimum = parseInt( $count.attr( 'data-minimum-value' ), 10 );

			var updateCount = function( $target ) {
				var length = $target.val().length;
				var count = down ? starting - length : length;
				$count.attr( 'data-current-value', count );
				$count.text( count );

				if ( maximum && maximum < length ) {
					$count.addClass( 'too-long' );
				} else {
					$count.removeClass( 'too-long' );
				}

				if ( minimum && length < minimum ) {
					$count.addClass( 'too-short' );
				} else {
					$count.removeClass( 'too-short' );
				}
			};

			$count.closest( 'form' ).find( ':input[name="' + name + '"]' ).each( function() {
				updateCount( $( this ) );

				$( this ).keyup( function() {
					updateCount( $( this ) );
				} );
			} );
		} );
	};

	$.fn.wpcf8NormalizeUrl = function() {
		return this.each( function() {
			var val = $.trim( $( this ).val() );

			// check the scheme part
			if ( val && ! val.match( /^[a-z][a-z0-9.+-]*:/i ) ) {
				val = val.replace( /^\/+/, '' );
				val = 'http://' + val;
			}

			$( this ).val( val );
		} );
	};

	$.fn.wpcf8NotValidTip = function( message ) {
		return this.each( function() {
			var $into = $( this );

			$into.find( 'span.wpcf8-not-valid-tip' ).remove();
			$into.append( '<span role="alert" class="wpcf8-not-valid-tip">' + message + '</span>' );

			if ( $into.is( '.use-floating-validation-tip *' ) ) {
				$( '.wpcf8-not-valid-tip', $into ).mouseover( function() {
					$( this ).wpcf8FadeOut();
				} );

				$( ':input', $into ).focus( function() {
					$( '.wpcf8-not-valid-tip', $into ).not( ':hidden' ).wpcf8FadeOut();
				} );
			}
		} );
	};

	$.fn.wpcf8FadeOut = function() {
		return this.each( function() {
			$( this ).animate( {
				opacity: 0
			}, 'fast', function() {
				$( this ).css( { 'z-index': -100 } );
			} );
		} );
	};

	$.fn.wpcf8OnloadRefill = function() {
		return this.each( function() {
			var url = $( this ).attr( 'action' );

			if ( 0 < url.indexOf( '#' ) ) {
				url = url.substr( 0, url.indexOf( '#' ) );
			}

			var id = $( this ).find( 'input[name="_wpcf8"]' ).val();
			var unitTag = $( this ).find( 'input[name="_wpcf8_unit_tag"]' ).val();

			$.getJSON( url,
				{ _wpcf8_is_ajax_call: 1, _wpcf8: id, _wpcf8_request_ver: $.now() },
				function( data ) {
					if ( data && data.captcha ) {
						$( '#' + unitTag ).wpcf8RefillCaptcha( data.captcha );
					}

					if ( data && data.quiz ) {
						$( '#' + unitTag ).wpcf8RefillQuiz( data.quiz );
					}
				}
			);
		} );
	};

	$.fn.wpcf8RefillCaptcha = function( captcha ) {
		return this.each( function() {
			var form = $( this );

			$.each( captcha, function( i, n ) {
				form.find( ':input[name="' + i + '"]' ).clearFields();
				form.find( 'img.wpcf8-captcha-' + i ).attr( 'src', n );
				var match = /([0-9]+)\.(png|gif|jpeg)$/.exec( n );
				form.find( 'input:hidden[name="_wpcf8_captcha_challenge_' + i + '"]' ).attr( 'value', match[ 1 ] );
			} );
		} );
	};

	$.fn.wpcf8RefillQuiz = function( quiz ) {
		return this.each( function() {
			var form = $( this );

			$.each( quiz, function( i, n ) {
				form.find( ':input[name="' + i + '"]' ).clearFields();
				form.find( ':input[name="' + i + '"]' ).siblings( 'span.wpcf8-quiz-label' ).text( n[ 0 ] );
				form.find( 'input:hidden[name="_wpcf8_quiz_answer_' + i + '"]' ).attr( 'value', n[ 1 ] );
			} );
		} );
	};

	$.fn.wpcf8ClearResponseOutput = function() {
		return this.each( function() {
			$( this ).find( 'div.wpcf8-response-output' ).hide().empty().removeClass( 'wpcf8-mail-sent-ok wpcf8-mail-sent-ng wpcf8-validation-errors wpcf8-spam-blocked' ).removeAttr( 'role' );
			$( this ).find( 'span.wpcf8-not-valid-tip' ).remove();
			$( this ).find( '.ajax-loader' ).removeClass( 'is-active' );
		} );
	};

	$.fn.wpcf8Recaptcha = function() {
		return this.each( function() {
			var events = 'wpcf8:spam wpcf8:mailsent wpcf8:mailfailed';
			$( this ).closest( 'div.wpcf8' ).on( events, function( e ) {
				if ( recaptchaWidgets && grecaptcha ) {
					$.each( recaptchaWidgets, function( index, value ) {
						grecaptcha.reset( value );
					} );
				}
			} );
		} );
	};

	$.wpcf8UpdateScreenReaderResponse = function( $form, data ) {
		$( '.wpcf8 .screen-reader-response' ).html( '' ).attr( 'role', '' );

		if ( data.message ) {
			var $response = $form.siblings( '.screen-reader-response' ).first();
			$response.append( data.message );

			if ( data.invalids ) {
				var $invalids = $( '<ul></ul>' );

				$.each( data.invalids, function( i, n ) {
					if ( n.idref ) {
						var $li = $( '<li></li>' ).append( $( '<a></a>' ).attr( 'href', '#' + n.idref ).append( n.message ) );
					} else {
						var $li = $( '<li></li>' ).append( n.message );
					}

					$invalids.append( $li );
				} );

				$response.append( $invalids );
			}

			$response.attr( 'role', 'alert' ).focus();
		}
	};

	$.wpcf8SupportHtml5 = function() {
		var features = {};
		var input = document.createElement( 'input' );

		features.placeholder = 'placeholder' in input;

		var inputTypes = [ 'email', 'url', 'tel', 'number', 'range', 'date' ];

		$.each( inputTypes, function( index, value ) {
			input.setAttribute( 'type', value );
			features[ value ] = input.type !== 'text';
		} );

		return features;
	};

	$( function() {
		_wpcf8.supportHtml5 = $.wpcf8SupportHtml5();
		$( 'div.wpcf8 > form' ).wpcf8InitForm();
	} );

} )( jQuery );

/*
 * Polyfill for Internet Explorer
 * See https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
 */
( function () {
	if ( typeof window.CustomEvent === "function" ) return false;

	function CustomEvent ( event, params ) {
		params = params || { bubbles: false, cancelable: false, detail: undefined };
		var evt = document.createEvent( 'CustomEvent' );
		evt.initCustomEvent( event,
			params.bubbles, params.cancelable, params.detail );
		return evt;
	}

	CustomEvent.prototype = window.Event.prototype;

	window.CustomEvent = CustomEvent;
} )();
