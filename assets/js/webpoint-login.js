jQuery( function ( $ ) {

	/* Init WebPoint Login */
	webpoint_login_init();

	function webpoint_login_init() {

		/* Check password */
		check_user_password();

		/* Check register form */
		validate_register_form();

	} // webpoint_login_init();

} ); /* Document Ready */


if ( typeof get_wlt !== 'function' ) {

    function get_wlt( text ) {

        /* Check global var exists */
        if ( typeof wl_i18n === 'undefined' || jQuery.type( wl_i18n ) !== 'object' ) {
            return false;
        }

        /* Check key data type */
        if ( typeof text === 'undefined' || jQuery.type( text ) !== 'string' ) {
            return false;
        }

        /* Return object property */
        return jQuery.type( wl_i18n[ text ] ) !== 'undefined' ? wl_i18n[ text ] : text;

    } // get_wlt();

}


if ( typeof recaptcha_render !== 'function' ) {

    function recaptcha_render( id ) {

        /* Check element id */
        if ( typeof id === 'undefined' || jQuery.type( id ) !== 'string' ) {
            return false;
        }

        /* Get reCAPTCHA site key */
        var site_key = jQuery( '#' + id ).attr( 'data-site_key' );

        /* Check reCAPTCHA site key */
        if ( jQuery.type( site_key ) !== 'string' || ! site_key.length ) {
            return false;
        }

        /* Render reCAPTCHA */
        return grecaptcha.render( id, {
            sitekey: site_key,
            theme: 'light'
        } );

    } // recaptcha_render();

}


if ( typeof recaptcha_init !== 'function' ) {

    function recaptcha_init() {

        /* Get reCAPTCHA elements */
        var recaptcha = jQuery( '.recaptcha-i' );

        /* Check reCAPTCHA elements */
        if ( ! recaptcha.length ) {
            return;
        }

        /* Init reCAPTCHA */
        recaptcha.each( function( index ) {

            /* Get reCAPTCHA ID */
            var recaptcha_id = jQuery( this ).attr( 'data-widget_id' );

            /* Check reCAPTCHA ID */
            if ( jQuery.isNumeric( recaptcha_id ) ) {
                grecaptcha.reset( recaptcha_id );
                return true;
            }

            /* Set element id and clear html */
            jQuery( this ).attr( 'id', 'recaptcha-i-' + index ).html( '' );

            /* Get reCAPTCHA ID */
            recaptcha_id = recaptcha_render( jQuery( this ).attr( 'id' ) );

            /* Check reCAPTCHA ID */
            if ( jQuery.isNumeric( recaptcha_id ) ) {

                /* Add reCAPTCHA ID to data attribute */
                jQuery( this ).attr( 'data-widget_id', recaptcha_id );

            } else {

                /* Show error message */
                jQuery( this ).html( '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + get_wlt( 'Google reCAPTCHA Loading Error.' ) );

            }

        } );

    } // recaptcha_init();

    /* Execute functions after page load */
    jQuery( window ).on( 'load', function() {
		recaptcha_init();
    } );

}


if ( typeof check_recaptcha !== 'function' ) {

    function check_recaptcha() {

        jQuery( document ).on( 'submit', 'form', function() {

            /* Get current form */
            var form = jQuery( this );

            /* Check if reCAPTCHA exists */
            if ( ! form.find( 'textarea[id*="g-recaptcha-response"]' ).length ) {
                return true;
            }

            /* Get reCAPTCHA ID */
            var recaptcha_id = form.find( '.recaptcha-i' ).first().attr( 'data-widget_id' );

            /* Check reCAPTCHA ID */
            if ( jQuery.type( recaptcha_id ) !== 'string' || ! recaptcha_id.length ) {
                return true;
            }

            /* Check reCAPTCHA response */
            if ( grecaptcha.getResponse( recaptcha_id ) === '' ) {
                alert( get_wlt( 'Check the box "I\'m not a robot" and try again.' ) );
                return false;
            }

        } );

    } // check_recaptcha();

	/* Execute function */
    check_recaptcha();

}


if ( typeof is_email !== 'function' ) {

	function is_email( email ) {
		//var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		var re = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
		return re.test( email );
	} // is_email();

}


if ( typeof reset_form !== 'function' ) {

	function reset_form( form ) {

		/* Get form */
		if ( jQuery.type( form ) === 'string' ) {
			form = jQuery( form );
		} else if ( jQuery.type( form ) !== 'object' ) {
			form = false;
		}

		/* Check form */
		if ( ! form || ! form.length ) {
			return false;
		}

		/* Reset form */
		form.each( function() {
			jQuery( this )[0].reset();
		} );

	} // reset_form();

}


if ( typeof check_user_password !== 'function' ) {

	function check_user_password() {

		/* Get passwords */
		var pass1 = jQuery( '#pass1' );
		var pass2 = jQuery( '#pass2' );

		/* Get password wraps */
		var pass1_parent = pass1.parent( 'p' );
		var pass2_parent = pass2.parent( 'p' );

		/* Show pass button init */
		var show_pass = '<span class="show-pass"></span>';

		/* Insert show pass after pass1 field */
		pass1.after( show_pass ).css( {
			'paddingRight': '36px'
		} ).parent( 'p' ).css( {
			'position': 'relative' } );

		/* Insert show pass after pass2 field */
		pass2.after( show_pass ).css( {
			'paddingRight': '36px' } ).parent( 'p' ).css( {
			'position': 'relative' } );

		/* Show pass button click */
		jQuery( document ).on( 'click', '.show-pass', function() {
			var input = jQuery( this ).parent( 'p' ).children( 'input' );
			if ( jQuery( this ).html() === '<span>X</span>' ) {
				jQuery( this ).text( '' );
				input.attr( 'type', 'password' );
			} else {
				jQuery( this ).html( '<span>X</span>' );
				input.attr( 'type', 'text' );
			}
		} );

		/* Add password indicator */
		pass1_parent.after( '<div class="password-indicator"><div id="password-indicator-i">&nbsp;</div></div><p id="complexity" class="default">' + get_wlt( 'Enter password' ) + '</p>' );

		/* Add password match field */
		pass2_parent.after( '<p id="pass-match" class="cp-default"></p>' );

		// jQuery( '#complexity' ).after( '<div id="details"></div>' );

		/* Get password match element */
		var pass_match = jQuery( '#pass-match' );

		/* Hide #pass2 */
		pass2_parent.hide();

		/* Pass1 change event */
		pass1.on( 'keyup', function() {

			if ( jQuery( this ).val() !== '' ) {
				pass2_parent.show();
			} else {
				pass2_parent.hide();
				pass_match.removeClass().addClass( 'cp-default' );
			}

		} );

		/* Check passwords match*/
		function check_password_match() {

			var pass1_val = pass1.val();
			var pass2_val = pass2.val();

			if ( pass1_val.length > 0 && pass2_val.length > 0 ) {
				if ( pass1_val !== pass2_val ) {
					pass_match.removeClass().addClass( 'cp-not-match' ).html( get_wlt( 'Passwords do not match!' ) );
				} else {
					pass_match.removeClass().addClass( 'cp-match' ).html( get_wlt( 'Passwords match' ) );
				}
			} else if ( pass1_val.length > 0 || pass2_val.length > 0 ) {
				pass_match.removeClass().addClass( 'cp-default' ).html( get_wlt( 'Passwords do not match!' ) );
			} else {
				pass_match.removeClass().addClass( 'cp-default' ).html( '' );
			}

		} // check_password_match();

		pass1.on( 'keyup', check_password_match );
		pass2.on( 'keyup', check_password_match );

		/* Check password strength */
		var strPassword;
		var charPassword;
		var complexity = jQuery( '#complexity' );
		var passwordIndicator = jQuery( '#password-indicator-i' );
		var minPasswordLength = 8;
		var maxPasswordLength = pass1.attr( 'maxlength' );
		if ( ! maxPasswordLength || maxPasswordLength.length === 0 ) {
			maxPasswordLength = 60;
		}
		var baseScore = 0;
		var score = 0;

		var num = {};
		num.Excess = 0;
		num.Upper = 0;
		num.Numbers = 0;
		num.Symbols = 0;

		var bonus = {};
		bonus.Excess = 3;
		bonus.Upper = 4;
		bonus.Numbers = 5;
		bonus.Symbols = 5;
		bonus.Combo = 0;
		bonus.FlatLower = 0;
		bonus.FlatNumber = 0;
		bonus.CharRepeat = 0;

		pass1.bind( 'keyup', checkVal );

		function password_strength_init() {

			strPassword = pass1.val();
			charPassword = strPassword.split( '' );
			num.Excess = 0;
			num.Upper = 0;
			num.Numbers = 0;
			num.Symbols = 0;
			bonus.Combo = 0;
			bonus.FlatLower = 0;
			bonus.FlatNumber = 0;
			bonus.CharRepeat = 0;
			baseScore = 0;
			score = 0;

		} // password_strength_init();

		function checkVal() {

			password_strength_init();

			if ( charPassword.length >= minPasswordLength ) {
				baseScore = 50;
				analyzeString();
				calcComplexity();
			} else {
				baseScore = 0;
			}

			outputResult();

		} // checkVal();

		function analyzeString() {

			for ( var i = 0; i < charPassword.length; i++ ) {

				if ( charPassword[i].match( /[A-ZА-Я]/g ) ) {
					num.Upper++;
				}

				if ( charPassword[i].match( /[0-9]/g ) ) {
					num.Numbers++;
				}

				if ( charPassword[i].match( /(.*[!,@,#,$,%,^,&,*,?,_,~])/ ) ) {
					num.Symbols++;
				}

			}

			num.Excess = charPassword.length - minPasswordLength;

			if ( num.Upper && num.Numbers && num.Symbols ) {
				bonus.Combo = 25;
			} else if ( ( num.Upper && num.Numbers ) || ( num.Upper && num.Symbols ) || ( num.Numbers && num.Symbols ) ) {
				bonus.Combo = 15;
			}

			if ( strPassword.match( /^[\sa-zа-я]+$/ ) ) {
				bonus.FlatLower = -15;
			}

			if ( strPassword.match( /^[\s0-9]+$/ ) ) {
				//bonus.FlatNumber = -35;
				bonus.FlatNumber = -100;
			}

			if ( strPassword.match( /^(.)\1+$/ ) ) {
				bonus.CharRepeat = -500;
			}

		} // analyzeString();

		function calcComplexity() {

			score = baseScore + ( num.Excess * bonus.Excess ) + ( num.Upper * bonus.Upper ) + ( num.Numbers * bonus.Numbers ) + ( num.Symbols * bonus.Symbols ) + bonus.Combo + bonus.FlatLower + bonus.FlatNumber + bonus.CharRepeat;

		} // calcComplexity();

		function outputResult() {

			var indWidth = Math.round( ( Math.round( charPassword.length ) / Math.round( maxPasswordLength ) ) * 100 );

			if ( indWidth > 100 ) {
				indWidth = 100;
			}

			var indColor = '#ff0000';

			if ( pass1.val() === '' ) {
				complexity
					.html( get_wlt( 'Enter password' ) )
					.removeClass( 'weak strong stronger strongest' )
					.addClass( 'default' );
			} else if ( charPassword.length < minPasswordLength ) {
				complexity
					.html( get_wlt( 'Password is too short' ) )
					.removeClass( 'strong stronger strongest' )
					.addClass( 'weak' );
			} else if ( score < 50 ) {
				complexity
					.html( get_wlt( 'Weak!' ) )
					.removeClass( 'strong stronger strongest' )
					.addClass( 'weak' );
			} else if ( score >= 50 && score < 75 ) {
				complexity
					.html( get_wlt( 'Average!' ) )
					.removeClass( 'stronger strongest' )
					.addClass( 'strong' );
				indColor = '#ffa500';
			} else if ( score >= 75 && score < 100 ) {
				complexity
					.html( get_wlt( 'Strong!' ) )
					.removeClass( 'strongest' )
					.addClass( 'stronger' );
				indColor = '#008000';
			} else if ( score >= 100 ) {
				complexity
					.html( get_wlt( 'Secure!' ) )
					.addClass( 'strongest' );
				indColor = '#008000';
			}

			passwordIndicator.css( {
				'backgroundColor': indColor,
				'width': indWidth + '%'
			} );

			/* jQuery( '#details' ).html( 'Base Score :<span class="value">' + baseScore  + '</span>'
			 + '<br />Length Bonus :<span class="value">' + ( num.Excess * bonus.Excess ) + ' [' + num.Excess + 'x' + bonus.Excess + ']</span> '
			 + '<br />Upper case bonus :<span class="value">' + ( num.Upper * bonus.Upper ) + ' [' + num.Upper + 'x' + bonus.Upper + ']</span> '
			 + '<br />Number Bonus :<span class="value"> ' + ( num.Numbers * bonus.Numbers ) + ' [' + num.Numbers + 'x' + bonus.Numbers + ']</span>'
			 + '<br />Symbol Bonus :<span class="value"> ' + ( num.Symbols * bonus.Symbols ) + ' [' + num.Symbols + 'x' + bonus.Symbols + ']</span>'
			 + '<br />Combination Bonus :<span class="value"> ' + bonus.Combo + '</span>'
			 + '<br />Lower case only penalty :<span class="value"> ' + bonus.FlatLower + '</span>'
			 + '<br />Numbers only penalty :<span class="value"> ' + bonus.FlatNumber + '</span>'
			 + '<br />Char repeat :<span class="value"> ' + bonus.CharRepeat + '</span>'
			 + '<br />Total Score:<span class="value"> ' + score  + '</span>' ); */

		} // outputResult();

		/* Disable submit form with week pass */
		jQuery( 'form' ).on( 'submit', function() {

			/* Get passwords */
			var pass1 = jQuery( this ).find( '#pass1' );
			var pass2 = jQuery( this ).find( '#pass2' );

			/* Check elements */
			if ( ! pass1.length && ! pass2.length ) {
				return true;
			}

			/* Check if pass1 is empty */
			if ( pass1.val() === '' ) {
				pass1.focus().css( { 'boxShadow': '0 0 5px #ff0000' } );
				complexity.addClass( 'weak' );
				return false;
			}

			/* Check password strength */
			if ( score < 50 ) {
				pass1.focus().css( { 'boxShadow': '0 0 5px #ff0000' } );
				return false;
			}

			/* Check password match */
			if ( pass1.val() !== pass2.val() ) {
				pass2.focus().css( { 'boxShadow': '0 0 5px #ff0000' } );
				return false;
			}

		} );

		/* Reset pass1 CSS */
		pass1.keyup( function() {
			jQuery( this ).css( { 'boxShadow': '' } );
		} );

		/* Reset pass2 CSS */
		pass2.keyup( function() {
			jQuery( this ).css( { 'boxShadow': '' } );
		} );

	} // check_user_password();

}


if ( typeof validate_register_form !== 'function' ) {

	function validate_register_form() {

		/* Get login form */
		var signupform = jQuery( '#signupform' );

		/* Check login form exists */
		if ( ! signupform.length ) {
			return false;
		}

		/* Reset register form */
		reset_form( signupform );

		/* Get user login */
		var user_login = signupform.find( '#user_login' );

		/* Insert login error element */
		user_login
			.parent( 'p' )
			.after( '<p id="login-error" class="error" style="display:none;"></p>' );

		/* Get login error element */
		var login_error = jQuery( '#login-error' );

		/* Init login notice */
		var login_notice = '';

		/* Change login input field */
		user_login.on( 'keyup', function() {

			/* Login to lowercase */
			jQuery( this ).val( jQuery( this ).val().toLowerCase() );

			/* Reset user login field CSS */
			jQuery( this ).css( { 'boxShadow': '' } );

			/* Get login value */
			var login = jQuery( this ).val();

			/* Set login notice */
			if ( login.length && ( login.length < 1 || login.length > 60 ) ) {
				login_notice = get_wlt( 'Username must be between 1 and 60 characters.' );
			} else if ( login.length && ( ! login.match( /^[a-z]{1}[a-z0-9_-]{2,30}[a-z0-9]{1}$/ ) || login.match( /[-|_]{2,}/ ) || login.match( /[-|_]+[-|_]+/ ) ) ) {
				if ( login.match(/^[0-9]/) ) {
					login_notice = get_wlt( 'Username should not start with a number.' );
				} else if ( login.match(/^-/) ) {
					login_notice = get_wlt( 'Username should not start with a hyphen.' );
				} else if ( login.match(/^_/) ) {
					login_notice = get_wlt( 'Username should not start with an underscore.' );
				} else if ( login.match(/[A-Z]+/) ) {
					login_notice = get_wlt( 'Username should not contain uppercase letters.' );
				} else if ( login.match(/-$/) ) {
					login_notice = get_wlt( 'Username should not end with a hyphen.' );
				} else if ( login.match(/_$/) ) {
					login_notice = get_wlt( 'Username should not end with underscore.' );
				} else if ( login.match(/[-]{2,}/) ) {
					login_notice = get_wlt( 'Username should not contain double hyphens.' );
				} else if ( login.match(/[_]{2,}/) ) {
					login_notice = get_wlt( 'Username should not contain double underscores.' );
				} else if ( login.match(/-_/) ) {
					login_notice = get_wlt( 'Username should not contain underscore after the hyphen.' );
				} else if (  login.match(/_-/)  ) {
					login_notice = get_wlt( 'Username should not contain hyphen after the underscore.' );
				} else {
					login_notice = '<span style="color: #ff0000;">' + get_wlt( 'Invalid username' ) + '</span>. ' + get_wlt( 'Username can contain lowercase letters of the Latin alphabet (a-z), numbers (0-9), single hyphens (-) and underscores (_). It should start with a letter, end with a letter or number and may not be longer than 60 characters.' );
				}
			} else {
				login_notice = '';
			}

			/* Show/hide login error */
			if ( login_notice !== '' ) {
				login_error.html( login_notice ).show();
			} else {
				login_error.html( '' ).hide();
			}

		} );

		/* Get user email */
		var user_email = signupform.find( '#email' );

		/* Insert email error element */
		user_email
			.parent( 'p' )
			.after( '<p id="email-error" class="error" style="display:none;"></p>' );

		/* Get email error element */
		var email_error = jQuery( '#email-error' );

		/* Change email input field */
		user_email.on( 'keyup', function() {

			/* Reset user email field CSS */
			jQuery( this ).css( { 'boxShadow': '' } );

			/* Hide email notice */
			email_error.html( '' ).hide();

		} );

		/* Disable submit form with errors */
		signupform.on( 'submit', function() {

			/* Init status */
			var status = true;

			/* Check user email */
			if ( user_email.val() === '' || ! is_email( user_email.val() ) ) {
				user_email.focus().css( { 'boxShadow': '0 0 5px #ff0000' } );
				if ( user_email.val() === '' ) {
					email_error.html( get_wlt( 'Email address cannot be empty.' ) ).show();
				} else {
					email_error.html( get_wlt( 'Incorrect email address.' ) ).show();
				}
				status = false;
			}

			/* Check user login */
			if ( user_login.val() === '' || login_notice !== '' ) {
				user_login.focus().css( { 'boxShadow': '0 0 5px #ff0000' } );
				if ( user_login.val() === '' ) {
					login_error.html( get_wlt( 'Username cannot be empty.' ) ).show();
				}
				status = false;
			}

			/* Return true/false */
			return status;

		} );

	} // validate_register_form();

}
