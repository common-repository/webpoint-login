<?php
/**
 * Plugin Name: WebPoint Login
 * Plugin URI: https://dmitriydenisov.com/webpoint-login/
 * Description: Authorization, registration and password reset with protection from robots.
 * Version: 1.0.2
 * Author: Dmitriy Denisov
 * Author URI: https://dmitriydenisov.com
 * Copyright: Dmitriy Denisov
 * Requires at least: 4.1
 * Tested up to: 5.0
 * Text Domain: webpoint-login
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
}


if ( ! class_exists( 'WebPoint_Login' ) ) :

/**
 * Main WebPoint_Login Class
 *
 * @class WebPoint_Login
 * @version 1.0.2
 */
class WebPoint_Login {


	/**
	 * WebPoint_Login version.
	 *
	 * @var string
	 */
	public $version = '1.0.2';


	/**
	 * The single instance of the class.
	 *
	 * @var WebPoint_Login
	 */
	protected static $_instance = null;


	/**
	 * Main WebPoint_Login Instance.
	 *
	 * Ensures only one instance of WebPoint_Login is loaded or can be loaded.
	 *
	 * @static
	 * @see WebPoint_Login()
	 *
	 * @return WebPoint_Login - Main instance.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;

	} // $this->instance();


	/**
	 * WebPoint_Login Constructor.
	 */
	public function __construct() {

		/* Init hooks */
		$this->init_hooks();

	} // __construct();


	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {

		/* Execute the function after the plugin has been activated */
		register_activation_hook( __FILE__, array( $this, 'plugin_activated' ) );

		/* Init WebPoint Login */
		add_action( 'init', array( $this, 'init' ), 0 );

		/* Redirect at authenticate */
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101 );

		/* Redirect after login */
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );

		/* Redirect after logout */
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );

		/* Redirect to the custom login page */
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );

		/* Redirect to the custom register page */
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );

		/* Redirect to the custom lost password page */
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );

		/* Redirect to the custom reset password page */
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );

		/* Register user */
		add_action( 'login_form_register', array( $this, 'do_register_user' ) );

		/* Send the lost password email */
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );

		/* Reset the user password */
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );

		/* Reset password message */
		add_filter( 'retrieve_password_message', array( $this, 'reset_password_message' ), 10, 4 );

		/* Add reCAPTCHA to the login form */
		add_filter( 'login_form_middle', array( $this, 'login_form_recaptcha' ), 10, 4 );

		/* Check reCAPTCHA before login */
		add_filter( 'authenticate', array( $this, 'validate_login_captcha' ), 30 );

		/* Custom login form shortcode */
		add_shortcode( 'webpoint-login-form', array( $this, 'render_login_form' ) );

		/* Custom register form shortcode */
		add_shortcode( 'webpoint-register-form', array( $this, 'render_register_form' ) );

		/* Custom lost password form shortcode */
		add_shortcode( 'webpoint-password-lost-form', array( $this, 'render_password_lost_form' ) );

		/* Custom reset password form shortcode */
		add_shortcode( 'webpoint-password-reset-form', array( $this, 'render_password_reset_form' ) );

		/* Including CSS and JavaScript files */
		add_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts' ) );

		/* Plugin Settings */
		add_action( 'admin_menu', array( $this, 'webpoint_login_menu' ) );
		add_action( 'admin_init', array( $this, 'webpoint_login_settings' ) );

	} // $this->init_hooks();


	/**
	 * Sanitize a variable.
	 *
	 * @param string|int|float $value
	 * @param string $type
	 * @param mixed $default
	 * @return mixed
	 */
	public function sanitize_var( $value = null, $type = null, $default = false ) {

		/* Check value */
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return $default;
		}

		/* Check type */
		if ( ! $type || ! is_string( $type ) ) {
			return $default;
		}

		/* Sanitize value by data type */
		if ( $type == 'integer' || $type == 'int' ) {
			$int_value = (int) $value;
			$string_value = trim( (string) $value );
			if ( strpos( $string_value, '.' ) !== false ) {
				$string_value = rtrim( rtrim( $string_value, '0' ), '.' );
			}
			$value = (string) $int_value === (string) $string_value ? $int_value : $default;
		} elseif ( $type == 'float' || $type == 'decimal' ) {
			$float_value = (float) $value;
			$string_value = trim( (string) $value );
			if ( strpos( $string_value, '.' ) !== false ) {
				$string_value = rtrim( rtrim( $string_value, '0' ), '.' );
			}
			$value = (string) $float_value === (string) $string_value ? $float_value : $default;
		} elseif ( $type == 'abs' ) {
			$abs_value = abs( $value );
			if ( is_int( $abs_value ) ) {
				$int_value = $this->sanitize_var( $value, 'integer', false );
				$value = (
					$int_value !== false
					&& $abs_value === abs( $int_value )
				) ? $abs_value : $default;
			} elseif ( is_float( $abs_value ) ) {
				$float_value = $this->sanitize_var( $value, 'float', false );
				$value = (
					$float_value !== false
					&& $abs_value === abs( $float_value )
				) ? $abs_value : $default;
			} else {
				$value = $default;
			}
		} elseif ( $type == 'absint' || $type == 'absinteger' ) {
			$value = $this->sanitize_var( $value, 'integer', false );
			$value = $value !== false ? abs( $value ) : $default;
		} elseif ( $type == 'absfloat' || $type == 'absdecimal' ) {
			$value = $this->sanitize_var( $value, 'float', false );
			$value = $value !== false ? abs( $value ) : $default;
		} elseif ( $type == 'term' ) {
			$value = (
				preg_match( '#^[a-z0-9_]+$#s', $value )
				&& $this->sanitize_var( substr( $value, 0, 1 ), 'integer', false ) === false
				&& substr( $value, -1, 1 ) !== '_'
			) ? (string) $value : $default;
		} elseif ( $type == 'slug' ) {
			$value = (
				preg_match( '#^[a-z0-9_-]+$#si', $value )
				&& substr( $value, 0, 1 ) !== '-'
				&& substr( $value, -1, 1 ) !== '-'
				&& strpos( $value, '--' ) === false
			) ? (string) $value : $default;
		} elseif ( $type == 'url' ) {
			$value = filter_var( $value, FILTER_VALIDATE_URL )
				? esc_url_raw( $value )
				: $default;
		} elseif ( $type == 'email' ) {
			$value = is_email( $value ) ? sanitize_email( $value ) : $default;
		} elseif ( $type == 'post' ) {
			$value = wp_unslash( $value );
			$value = balanceTags( $value, true );
			$value = wp_kses_post( $value );
		} elseif ( $type == 'comment' ) {
			$value = wp_unslash( $value );
			$value = balanceTags( $value, true );
			$value = wp_kses_data( $value );
		} elseif ( $type == 'html' ) {
			$value = wp_unslash( $value );
			$value = wp_kses_post( $value );
		} elseif ( $type == 'text' ) {
			$value = sanitize_text_field( $value );
		} elseif ( $type == 'string' ) {
			$value = (string) $value;
		} elseif ( $type == 'login' ) {
			$value = (
				preg_match( '#^[a-z0-9_-]+$#s', $value )
				&& strpos( $value, '__' ) === false
				&& strpos( $value, '--' ) === false
				&& strpos( $value, '_-' ) === false
				&& strpos( $value, '-_' ) === false
				&& substr( $value, 0, 1 ) !== '_'
				&& substr( $value, -1, 1 ) !== '_'
				&& substr( $value, 0, 1 ) !== '-'
				&& substr( $value, -1, 1 ) !== '-'
				&& $this->sanitize_var( substr( $value, 0, 1 ), 'integer', false ) === false
				&& mb_strlen( $value, 'UTF-8' ) >= 3
			) ? $value : false;
		} else {
			return $default;
		}

		/* Trim string values */
		if ( is_string( $value ) ) {
			$value = trim( $value );
		}

		/* Return sanitized data */
		return is_string( $value ) || is_numeric( $value ) ? $value : $default;

	} // $this->sanitize_var();


	/**
	 * Get plugin settings.
	 *
	 * @param string $option
	 * @param string $key
	 * @param string $type
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get_plugin_settings( $option = null, $key = null, $type = null, $default = false ) {

		/* Sanitize option name */
		$option = $this->sanitize_var( $option, 'term', $default );
		if ( $option === $default ) {
			return $default;
		}

		/* Get option value */
		$option = get_option( $option, $default );
		if ( $option === $default ) {
			return $default;
		}

		/* Sanitize key */
		$key = $this->sanitize_var( $key, 'term', null );

		/* Get settings by key */
		if ( ! is_null( $key ) ) {
			$option = isset( $option[ $key ] ) ? $option[ $key ] : $default;
			if ( $option === $default ) {
				return $default;
			}
		}

		/* Sanitize option value */
		if ( ! is_null( $type ) ) {
			$option = $this->sanitize_var( $option, $type, $default );
		}

		/* Return option */
		return $option;

	} // get_plugin_settings();


	/**
	 * Execute once after the plugin has been activated.
	 */
	public static function plugin_activated() {

		/* Set args */
		$args = array(
			'login' => array(
				'title' => __( 'Log in', 'webpoint-login' ),
				'content' => '[webpoint-login-form]'
			),
			'register' => array(
				'title' => __( 'Sign up', 'webpoint-login' ),
				'content' => '[webpoint-register-form]'
			),
			'password-lost' => array(
				'title' => __( 'Forgot your password?', 'webpoint-login' ),
				'content' => '[webpoint-password-lost-form]'
			),
			'password-reset' => array(
				'title' => __( 'Password reset', 'webpoint-login' ),
				'content' => '[webpoint-password-reset-form]'
			)
		);

		/* Create pages */
		foreach ( $args as $slug => $page ) {
			$query = new WP_Query( 'pagename=' . $slug );
			if ( ! $query->have_posts() ) {
				wp_insert_post(
					array(
						'post_content'   => $page['content'],
						'post_name'      => $slug,
						'post_title'     => $page['title'],
						'post_status'    => 'publish',
						'post_type'      => 'page',
						'ping_status'    => 'closed',
						'comment_status' => 'closed',
					)
				);
			}
		}

	} // $this->plugin_activated();


	/**
	 * Init the plugin when WordPress Initialises.
	 */
	public function init() {

		/* Set up plugin localisation */
		$this->load_plugin_textdomain();

	} // $this->init();


	/**
	 * Load plugin localisation files.
	 */
	public function load_plugin_textdomain() {

		/* Load plugin's translated strings. */
		load_plugin_textdomain(
			'webpoint-login',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages'
		);

	} // $this->load_plugin_textdomain();


	/**
	 * Add Settings Page to the Admin Menu.
	 */
	public function webpoint_login_menu() {

		/* Add submenu page */
		add_options_page(
			__( 'WebPoint Login', 'webpoint-login' ),
			__( 'WebPoint Login', 'webpoint-login' ),
			'manage_options',
			'webpoint_login',
			array( $this, 'webpoint_login_settings_page' )
		);

	} // $this->webpoint_login_menu();


	/**
	 * Render the Settings Page.
	 */
	public function webpoint_login_settings_page() {

		/* Check current user permissions */
		if ( ! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'webpoint-login' ) );
		} ?>

        <div class="wrap">

            <h1><?php _e( 'WebPoint Login Settings', 'webpoint-login' ); ?></h1>

            <form action='options.php' method='post'>

				<?php settings_fields( 'webpoint_login_settings_group' ); ?>

				<?php do_settings_sections( 'webpoint_login_settings' ); ?>

				<?php submit_button(); ?>

            </form>

        </div>

	<?php } // $this->webpoint_login_settings_page();


	/**
	 * WebPoint Login Settings.
	 */
	public function webpoint_login_settings() {

		/* Register Settings Option */
		register_setting(
			'webpoint_login_settings_group',
			'webpoint_login_settings',
			array(
				'type'              => 'string',
				'group'             => 'webpoint_login_settings_group',
				'description'       => __( 'WebPoint Login Settings', 'webpoint-login' ),
				'sanitize_callback' => null,
				'show_in_rest'      => false,
			)
		);

		/* Add Settings Section */
		add_settings_section(
			'webpoint_login_settings_section',
			'',
			'__return_true',
			'webpoint_login_settings'
		);

		/* Add reCAPTCHA Site Key Field */
		add_settings_field(
			'recapthca_site_key',
			__( 'reCAPTCHA Site Key', 'webpoint-login' ),
			array( $this, 'webpoint_login_settings_text_field_cb' ),
			'webpoint_login_settings',
			'webpoint_login_settings_section',
			array(
				'option' => 'recaptcha_site_key',
				'label_for' => 'recaptcha_site_key',
				'class' => 'regular-text',
				'placeholder' => '',
				'description' => __( 'Enter the Google reCAPTCHA Site Key.', 'webpoint-login' )
			)
		);

		/* Add reCAPTCHA Secret Key Field */
		add_settings_field(
			'recapthca_secret_key',
			__( 'reCAPTCHA Secret Key', 'webpoint-login' ),
			array( $this, 'webpoint_login_settings_text_field_cb' ),
			'webpoint_login_settings',
			'webpoint_login_settings_section',
			array(
				'option' => 'recaptcha_secret_key',
				'label_for' => 'recaptcha_secret_key',
				'class' => 'regular-text',
				'placeholder' => '',
				'description' => __( 'Enter the Google reCAPTCHA Secret Key.', 'webpoint-login' )
			)
		);

	} // $this->webpoint_login_settings();


	/**
	 * Render the Text Settings Field.
	 *
	 * @param array $args
	 */
	public function webpoint_login_settings_text_field_cb( $args ) {

		/* Get option value */
		$option = get_option( 'webpoint_login_settings', false );

		/* Get field value */
		$value = $option && isset( $option[ $args['option'] ] )
			? $option[ $args['option'] ]
			: '';

		/* Output the field */
		printf(
			'<input type="text" name="webpoint_login_settings[%s]" id="%s" class="%s" placeholder="%s" value="%s" />',
			isset( $args['option'] ) ? esc_attr( $args['option'] ) : '',
			isset( $args['label_for'] ) ? esc_attr( $args['label_for'] ) : '',
			isset( $args['class'] ) ? esc_attr( $args['class'] ) : '',
			isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '',
			esc_attr( $value )
		);

		/* Display field description */
		if ( $args['description'] ) {
			printf(
				'<p class="description">%s</p>',
				esc_attr( $args['description'] )
			);
		}

	} // $this->webpoint_login_settings_text_field_cb();


	/**
	 * Redirect user to custom URL.
	 *
	 * @param string $redirect_url
	 * @param string $default_url
	 * @return void
	 */
	public function redirect_user( $redirect_url = '', $default_url = '' ) {

		/* Check the redirect url */
		$redirect_url = $this->sanitize_var( $redirect_url, 'url', false );
		if ( ! $redirect_url ) {
			$redirect_url = home_url();
		}

		/* Check the default redirect url */
		$default_url = $this->sanitize_var( $default_url, 'url', false );
		if ( ! $default_url ) {
			$default_url = home_url();
		}

		/* Sanitize the redirect url */
		$redirect_url = wp_sanitize_redirect( $redirect_url );

		/* Validate the redirect url */
		$redirect_url = wp_validate_redirect( $redirect_url, $default_url );

		/* Redirect user */
		wp_redirect( $redirect_url );

		/* Exit */
		exit;

	} // $this->redirect_user();


	/**
	 * Redirect user to the custom login page.
	 */
	public function redirect_to_custom_login() {

		/* Check the request method */
		if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Redirect logged in users */
		if ( is_user_logged_in() ) {
			$this->redirect_logged_in_user();
		}

		/* Get the login page url */
		$login_url = home_url( 'login' );

		/* Save the default login page url */
		$default_url = $login_url;

		/* Add redirect url to the login url */
		if ( isset( $_REQUEST['redirect_to'] ) && ! empty( $_REQUEST['redirect_to'] ) ) {
			$login_url = add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $login_url );
		}

		/* Add check email args to login url if var exists */
		if ( isset( $_REQUEST['checkemail'] ) && ! empty( $_REQUEST['checkemail'] ) ) {
			$login_url = add_query_arg( 'checkemail', $_REQUEST['checkemail'], $login_url );
		}

		/* Redirect user */
		$this->redirect_user( $login_url, $default_url );

	} // $this->redirect_to_custom_login();
	

	/**
	 * Validate reCAPTCHA before user login.
	 *
	 * @param WP_USER|null $user
	 * @return WP_User|WP_Error|null
	 */
	public function validate_login_captcha( $user ) {

		/* Check reCAPTCHA response */
		if ( ! $this->check_recaptcha_response() ) {
			return new WP_Error( 'captcha_error', __( 'reCAPTCHA Error. Check the box "I\'m not a robot" and try again.', 'webpoint-login' ) );
		}

		/* Return the user object */
		return $user;

	} // $this->validate_login_captcha();


	/**
	 * Redirect user if an authentication error exists.
	 *
	 * @param WP_USER|WP_Error $user
	 * @return WP_User
	 */
	public function maybe_redirect_at_authenticate( $user ) {

		/* Check the request method */
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return $user;
		}

		/* Redirect user if wp error exists */
		if ( is_wp_error( $user ) ) {

			/* Get error codes */
			$error_codes = implode( ',', $user->get_error_codes() );

			/* Get the login page url */
			$login_url = home_url( 'login' );

			/* Save the default login page url */
			$default_url = $login_url;

			/* Add error args to the login URL */
			$login_url = add_query_arg( 'errors', $error_codes, $login_url );

			/* Add redirect url to the login URL */
			if ( isset( $_REQUEST['redirect_to'] ) && ! empty( $_REQUEST['redirect_to'] ) ) {
				$login_url = add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $login_url );
			}

			/* Redirect user */
			$this->redirect_user( $login_url, $default_url );

		}

		/* Return the user object */
		return $user;

	} // $this->maybe_redirect_at_authenticate();


	/**
	 * Redirect user after successful log in.
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $request URL the user is coming from.
	 * @param object $user Logged user's data.
	 * @return string
	 */
	public function redirect_after_login( $redirect_to, $request, $user ) {

		/* Sanitize redirect url */
		$redirect_url = $this->sanitize_var( $redirect_to, 'url', admin_url() );

		/* Sanitize redirect url */
		$redirect_url = wp_sanitize_redirect( $redirect_url );

		/* Validate redirect url */
		$redirect_url = wp_validate_redirect( $redirect_url, home_url() );

		/* Return redirect url */
		return $redirect_url;

	} // $this->redirect_after_login();


	/**
	 * Redirect user after logout.
	 */
	public function redirect_after_logout() {

		/* Get redirect url */
		$redirect_url = home_url( 'login?logged_out=true' );

		/* Redirect user */
		$this->redirect_user( $redirect_url, home_url() );

	} // $this->redirect_after_logout();


	/**
	 * Redirect user to the custom registration page instead of wp-login.php?action=register
	 */
	public function redirect_to_custom_register() {

		/* Check the request method */
		if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Redirect logged in users */
		if ( is_user_logged_in() ) {
			$this->redirect_logged_in_user();
		}

		/* Redirect user */
		wp_redirect( home_url( 'register' ) );

		/* Exit */
		exit;

	} // $this->redirect_to_custom_register();


	/**
	 * Redirect user to the custom lost password page instead of wp-login.php?action=lostpassword
	 */
	public function redirect_to_custom_lostpassword() {

		/* Check the request method */
		if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Redirect logged in users */
		if ( is_user_logged_in() ) {
			$this->redirect_logged_in_user();
		}

		/* Redirect user */
		wp_redirect( home_url( 'password-lost' ) );

		/* Exit */
		exit;

	} // $this->redirect_to_custom_lostpassword();


	/**
	 * Redirect user to the custom password reset page.
	 */
	public function redirect_to_custom_password_reset() {

		/* Check the request method */
		if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Verify key/login combo */
		/* $user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
		if ( ! $user || is_wp_error( $user ) ) {
			if ( $user && $user->get_error_code() === 'expired_key' ) {
				wp_redirect( home_url( 'login?login=expiredkey' ) );
			} else {
				wp_redirect( home_url( 'login?login=invalidkey' ) );
			}
			exit;
		} */

		/* Set redirect url */
		$redirect_url = home_url( 'password-reset' );
		$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
		$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

		/* Redirect user */
		$this->redirect_user( $redirect_url, home_url( 'password-reset' ) );

	} // $this->redirect_to_custom_password_reset();


	/**
	 * Register the login form shortcode.
	 *
	 * @return string
	 */
	public function render_login_form() {

		/* Exit if user is logged in */
		if ( is_user_logged_in() ) {
			return $this->logged_in_user_template();
		}

		/* Set args */
		$args = array(
			'redirect'           => '',
			'errors'             => array(),
			'logged_out'         => false,
			'registered'         => false,
			'lost_password_sent' => false,
			'password_updated'   => false,
			'recaptcha_site_key' => ''
		);

		/* Check if redirect url exists */
		if ( isset( $_REQUEST['redirect_to'] ) && ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = $this->sanitize_var( $_REQUEST['redirect_to'], 'url', false );
			if ( $redirect_to ) {
				$args['redirect'] = wp_sanitize_redirect( $_REQUEST['redirect_to'] );
				$args['redirect'] = wp_validate_redirect( $args['redirect'], '' );
			}
		}

		/* Get error messages */
		if ( isset( $_REQUEST['errors'] ) && ! empty( $_REQUEST['errors'] ) ) {
			$args['errors'] = $this->parse_error_messages( $_REQUEST['errors'] );
		}

		/* Check if user is logged out */
		if ( isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == 'true' ) {
			$args['logged_out'] = true;
		}

		/* Check if user has registered */
		if ( isset( $_REQUEST['registered'] ) ) {
			$args['registered'] = true;
		}

		/* Check if lost password message sent */
		if ( isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm' ) {
			$args['lost_password_sent'] = true;
		}

		/* Check if user is updated the password */
		if ( isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed' ) {
			$args['password_updated'] = true;
		}
		
		/* Get reCAPTCHA site key */
		$args['recaptcha_site_key'] = $this->get_recaptcha_keys( 'site_key' );

		/* Get the login form template */
		return $this->get_template_html( 'login-form', $args );

	} // $this->render_login_form();
	

	/**
	 * Add reCAPTCHA to the login form.
	 *
	 * @param string $form
	 * @return string
	 */
	public function login_form_recaptcha( $form ) {

		/* Get reCAPTCHA HTML */
		$recaptcha_form = $this->get_recaptcha_html();

		/* Check reCAPTCHA HTML */
		if ( ! $recaptcha_form ) {
			return $form;
		}

		/* Add reCAPTCHA HTML to the login form */
		return $form . sprintf(
			'<div class="recaptcha">%s</div>',
			$recaptcha_form
		);

	} // $this->login_form_recaptcha();


	/**
	 * Register form shortcode.
	 *
	 * @return string
	 */
	public function render_register_form() {

		/* Check if user is logged in */
		if ( is_user_logged_in() ) {
			return $this->logged_in_user_template();
		}

		/* Check if registration is disabled */
		if ( ! get_option( 'users_can_register' ) ) {
			return sprintf(
				'<div class="webpoint-login"><p class="notice">%s</p></div>',
				__( 'User registration is disabled.', 'webpoint-login' )
			);
		}

		/* Set args */
		$args = array(
			'errors' => array(),
			'recaptcha_site_key' => ''
		);

		/* Get error messages */
		if ( isset( $_REQUEST['errors'] ) && ! empty( $_REQUEST['errors'] ) ) {
			$args['errors'] = $this->parse_error_messages( $_REQUEST['errors'] );
		}

		/* Get reCAPTCHA site key */
		$args['recaptcha_site_key'] = $this->get_recaptcha_keys( 'site_key' );

		/* Get the register form template */
		return $this->get_template_html( 'register-form', $args );

	} // $this->render_register_form();


	/**
	 * Register password lost shortcode.
	 *
	 * @return string
	 */
	public function render_password_lost_form() {

		/* Check if user is logged in */
		if ( is_user_logged_in() ) {
			return $this->logged_in_user_template();
		}

		/* Set args */
		$args = array(
			'errors' => array(),
			'recaptcha_site_key' => ''
		);

		/* Get error messages */
		if ( isset( $_REQUEST['errors'] ) && ! empty( $_REQUEST['errors'] ) ) {
			$args['errors'] = $this->parse_error_messages( $_REQUEST['errors'] );
		}

		/* Get reCAPTCHA site key */
		$args['recaptcha_site_key'] = $this->get_recaptcha_keys( 'site_key' );

		/* Get the password lost form template */
		return $this->get_template_html( 'password-lost-form', $args );

	} // $this->render_password_lost_form();


	/**
	 * Register password reset form shortcode.
	 *
	 * @return string
	 */
	public function render_password_reset_form() {

		/* Check if user is logged in */
		if ( is_user_logged_in() ) {
			return $this->logged_in_user_template();
		}

		/* Exit if user login and key does not exists */
		if ( ! isset( $_REQUEST['login'] ) || ! isset( $_REQUEST['key'] ) ) {
			return '<div class="webpoint-login"><p class="notice">' . sprintf( __( 'To restore access to your account, click on the %slink%s.', 'webpoint-login' ), '<a href="' . esc_url( home_url( 'password-lost' ) ) . '">', '</a>' ) . '</p></div>';
		}

		/* Set args */
		$args = array(
			'errors' => array(),
			'recaptcha_site_key' => ''
		);

		/* Get user login and key */
		$args['login'] = $_REQUEST['login'];
		$args['key'] = $_REQUEST['key'];

		/* Get error messages */
		if ( isset( $_REQUEST['errors'] ) && ! empty( $_REQUEST['errors'] ) ) {
			$args['errors'] = $this->parse_error_messages( $_REQUEST['errors'] );
		}

		/* Get reCAPTCHA site key */
		$args['recaptcha_site_key'] = $this->get_recaptcha_keys( 'site_key' );

		/* Get the password reset form template */
		return $this->get_template_html( 'password-reset-form', $args );

	} // $this->render_password_reset_form();


	/**
	 * Parse error messages.
	 *
	 * @param string $errors
	 * @return array
	 */
	public function parse_error_messages( $errors = '' ) {

		/* Init errors messages */
		$error_messages = array();

		/* Sanitize errors */
		$errors = (string) $errors;

		/* Explode string */
		$error_codes = explode( ',', $errors );
		if ( empty( $error_codes ) || ! is_array( $error_codes ) ) {
			return $error_messages;
		}

		/* Get error messages */
		foreach ( $error_codes as $error_code ) {
			$error_messages[] = $this->get_error_message( $error_code );
		}

		/* Return error messages */
		return $error_messages;

	} // $this->parse_error_messages();

	
	/**
	 * Including CSS and JavaScript files.
	 */
	public function plugin_scripts() {

		/* Check page type */
		if ( ! is_page() ) {
			return;
		}

		/* Set page slugs */
		$slugs = array(
			'login',
			'register',
			'password-lost',
			'password-reset'
		);

		/* Check current page slug */
		if ( ! is_page( $slugs ) ) {
			return;
		}

		/**
		 * Register the CSS stylesheet.
		 *
		 * Name: webpoint-login-css
		 * Path: plugins_url( 'assets/css/webpoint-login.min.css', __FILE__ )
		 * Depends: null
		 * Version: null
		 * Media: all
		 */
		wp_register_style(
			'webpoint-login-css',
			plugins_url( 'assets/css/webpoint-login.min.css', __FILE__ ),
			array(),
			null,
			'all'
		);

		/* Enqueue the CSS stylesheet */
		wp_enqueue_style( 'webpoint-login-css' );

		/**
		 * Register the Script.
		 *
		 * Name: webpoint-login-js
		 * Path: plugins_url( 'assets/js/webpoint-login.min.js', __FILE__ )
		 * Depends: jquery
		 * Version: null
		 * In footer: true
		 */
		wp_register_script(
			'webpoint-login-js',
			plugins_url( 'assets/js/webpoint-login.min.js', __FILE__ ),
			array( 'jquery' ),
			null,
			true
		);

		/* Enqueue the Script */
		wp_enqueue_script( 'webpoint-login-js' );

		/* Check reCAPTCHA Activation */
		if ( $this->is_recaptcha_activated() ) {

			/* Check whether the reCAPTCHA script has been registered. */
			if ( ! wp_script_is( 'recaptcha', 'registered' ) ) {

				/**
				 * Register the Script.
				 *
				 * Name: recaptcha
				 * Path: https://www.google.com/recaptcha/api.js
				 * Depends: webpoint-login-js
				 * Version: null
				 * In footer: true
				 */
				wp_register_script(
					'recaptcha',
					'https://www.google.com/recaptcha/api.js',
					array( 'webpoint-login-js' ),
					null,
					true
				);

				/* Enqueue the Script */
				wp_enqueue_script( 'recaptcha' );

			} else {

				/* Check whether the reCAPTCHA script has been added to the queue. */
				if ( ! wp_script_is( 'recaptcha', 'enqueued' ) ) {

					/* Enqueue the Script */
					wp_enqueue_script( 'recaptcha' );

				}

			}

		}

		/* Localize js i18n */
		wp_localize_script(
			'webpoint-login-js',
			'wl_i18n',
			$this->get_js_i18n()
		);

	} // $this->plugin_scripts();


	/**
	 * Get javascript translations.
	 *
	 * @return array
	 */
	public function get_js_i18n() {

		/* Set translation */
		$js_i18n = array(
			'Google reCAPTCHA Loading Error.' => __( 'Google reCAPTCHA Loading Error.', 'webpoint-login' ),
			'Check the box "I\'m not a robot" and try again.' => __( 'Check the box "I\'m not a robot" and try again.', 'webpoint-login' ),
			'Enter password' => __( 'Enter password', 'webpoint-login' ),
			'Passwords do not match!' => __( 'Passwords do not match!', 'webpoint-login' ),
			'Passwords match' => __( 'Passwords match', 'webpoint-login' ),
			'Password is too short' => __( 'Password is too short', 'webpoint-login' ),
			'Weak!' => _x( 'Weak!', 'password', 'webpoint-login' ),
			'Average!' => _x( 'Average!', 'password', 'webpoint-login' ),
			'Strong!' => _x( 'Strong!', 'password', 'webpoint-login' ),
			'Secure!' => _x( 'Secure!', 'password', 'webpoint-login' ),
			'Username may not be longer than 60 characters.' => __( 'Username may not be longer than 60 characters.', 'webpoint-login' ),
			'Username should not start with a number.' => __( 'Username should not start with a number.', 'webpoint-login' ),
			'Username should not start with a hyphen.' => __( 'Username should not start with a hyphen.', 'webpoint-login' ),
			'Username should not start with an underscore.' => __( 'Username should not start with an underscore.', 'webpoint-login' ),
			'Username should not contain uppercase letters.' => __( 'Username should not contain uppercase letters.', 'webpoint-login' ),
			'Username should not end with a hyphen.' => __( 'Username should not end with a hyphen.', 'webpoint-login' ),
			'Username should not end with underscore.' => __( 'Username should not end with underscore.', 'webpoint-login' ),
			'Username should not contain double hyphens.' => __( 'Username should not contain double hyphens.', 'webpoint-login' ),
			'Username should not contain double underscores.' => __( 'Username should not contain double underscores.', 'webpoint-login' ),
			'Username should not contain underscore after the hyphen.' => __( 'Username should not contain underscore after the hyphen.', 'webpoint-login' ),
			'Username should not contain hyphen after the underscore.' => __( 'Username should not contain hyphen after the underscore.', 'webpoint-login' ),
			'Invalid username' => _x( 'Invalid username', 'js login error', 'webpoint-login' ),
			'Username can contain lowercase letters of the Latin alphabet (a-z), numbers (0-9), single hyphens (-) and underscores (_). It should start with a letter, end with a letter or number and may not be longer than 60 characters.' => __( 'Username can contain lowercase letters of the Latin alphabet (a-z), numbers (0-9), single hyphens (-) and underscores (_). It should start with a letter, end with a letter or number and may not be longer than 60 characters.', 'webpoint-login' ),
			'Email address cannot be empty.' => __( 'Email address cannot be empty.', 'webpoint-login' ),
			'Incorrect email address.' => __( 'Incorrect email address.', 'webpoint-login' ),
			'Username cannot be empty.' => __( 'Username cannot be empty.', 'webpoint-login' )
		);

		/* Return translations */
		return $js_i18n;

	} // $this->get_js_i18n();


	/**
	 * Get template html.
	 *
	 * @param string $template
	 * @param array $args
	 * @return string
	 */
	private function get_template_html( $template = null, $args = array() ) {

		/* Sanitize the template name */
		$template = $this->sanitize_var( $template, 'slug', false );
		if ( ! $template ) {
			return sprintf(
				'<div class="error">%s</div>',
				__( 'Template file does not set!', 'webpoint-login' )
			);
		}

		/* Check the file exists */
		if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'templates/' . $template . '.php' ) ) {
			return sprintf(
				'<div class="error">%s</div>',
				__( 'Template file does not exist!', 'webpoint-login' )
			);
		}

		ob_start();

		do_action( 'webpoint_login_before_' . $template );

		require( 'templates/' . $template . '.php' );

		do_action( 'webpoint_login_after_' . $template );

		$html = ob_get_contents();

		ob_end_clean();

		return $html;

	} // $this->get_template_html();


	/**
	 * Register user.
	 */
	public function do_register_user() {

		/* Check the request method */
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Set the default redirect url */
		$redirect_url = home_url( 'register' );

		/* Save the register page url */
		$register_url = $redirect_url;

		/* Get nonce field value */
		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;

		/* Check conditions */
		if ( ! get_option( 'users_can_register' ) ) {

			/* Error: registration is closed */
			$redirect_url = add_query_arg( 'errors', 'register_closed', $redirect_url );

		} elseif ( ! $this->check_recaptcha_response() ) {

			/* Captcha error */
			$redirect_url = add_query_arg( 'errors', 'captcha_error', $redirect_url );

		} elseif ( ! $nonce || ! wp_verify_nonce( $nonce, 'register_user' ) ) {

			/* Nonce field error */
			$redirect_url = add_query_arg( 'errors', 'nonce_error', $redirect_url );

		} else {

			/* Prepare user data */
			$user_email = $this->sanitize_var( $_POST['email'], 'email', '' );
			$user_login = $this->sanitize_var( $_POST['user_login'], 'login', '' );
			$user_pass1 = $this->sanitize_var( $_POST['pass1'], 'string', '' );
			$user_pass2 = $this->sanitize_var( $_POST['pass2'], 'string', '' );

			/* Register new user */
			$result = $this->register_user( $user_email, $user_login, $user_pass1, $user_pass2 );

			/* Check register result */
			if ( is_wp_error( $result ) ) {

				/* Check error codes */
				if ( is_array( $result->get_error_codes() ) ) {

					/* Add error codes to redirect url query args */
					$errors = implode( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'errors', $errors, $redirect_url );

				}

			} else {

				/* Redirect user to the login form if a user has been registered */
				$redirect_url = home_url( 'login' );
				$redirect_url = add_query_arg( 'registered', $user_email, $redirect_url );

			}

		}

		/* Redirect user */
		$this->redirect_user( $redirect_url, $register_url );

	} // $this->do_register_user();


	/**
	 * Sending a message with a link to reset password.
	 */
	public function do_password_lost() {

		/* Check the request method */
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Get nonce field value */
		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;

		/* Check conditions */
		if ( ! $this->check_recaptcha_response() ) {

			/* Captcha error */
			$redirect_url = home_url( 'password-lost' );
			$redirect_url = add_query_arg( 'errors', 'captcha_error', $redirect_url );

		} elseif ( ! $nonce || ! wp_verify_nonce( $nonce, 'lost_password' ) ) {

			/* Nonce field error */
			$redirect_url = home_url( 'password-lost' );
			$redirect_url = add_query_arg( 'errors', 'nonce_error', $redirect_url );

		} else {

			/* Handles sending password retrieval email to user */
			$errors = retrieve_password();

			/* Check errors */
			if ( is_wp_error( $errors ) ) {

				/* Set the redirect url */
				$redirect_url = home_url( 'password-lost' );

				/* Check error codes */
				if ( is_array( $errors->get_error_codes() ) ) {

					/* Add error codes to redirect url query args */
					$redirect_url = add_query_arg(
						'errors',
						implode( ',', $errors->get_error_codes() ),
						$redirect_url
					);

				}

			} else {

				/* Redirect user to login form if retrieve password is success */
				$redirect_url = home_url( 'login' );
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
				if ( isset( $_REQUEST['redirect_to'] ) && ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_url = $_REQUEST['redirect_to'];
				}

			}

		}

		/* Redirect user */
		$this->redirect_user( $redirect_url, home_url( 'password-lost' ) );

	} // $this->do_password_lost();


	/**
	 * Reset password.
	 */
	public function do_password_reset() {

		/* Check the request method */
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		/* Get the password reset url */
		$password_reset_url = home_url( 'password-reset' );

		/* Get the nonce field value */
		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;

		/* Get reset login and key */
		$rp_key = $this->sanitize_var( $_REQUEST['rp_key'], 'string', '' );
		$rp_login = $this->sanitize_var( $_REQUEST['rp_login'], 'string', '' );

		/* Check login */
		if (
			! $this->sanitize_var( $rp_login, 'login', false )
			&& ! $this->sanitize_var( $rp_login, 'email', false )
		) {
			$rp_login = '';
		}

		/* Check reCAPTCHA response */
		if ( ! $this->check_recaptcha_response() ) {

			/* Get the redirect url */
			$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );

			/* Add error code to redirect url query args */
			$redirect_url = add_query_arg( 'errors', 'captcha_error', $redirect_url );

			/* Redirect user */
			$this->redirect_user( $redirect_url, $password_reset_url );

		}

		/* Verify nonce field */
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'reset_password' ) ) {

			/* Get the redirect url */
			$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );

			/* Add error code to redirect url query args */
			$redirect_url = add_query_arg( 'errors', 'nonce_error', $redirect_url );

			/* Redirect user */
			$this->redirect_user( $redirect_url, $password_reset_url );

		}

		/* Check password reset key */
		$user = check_password_reset_key( $rp_key, $rp_login );

		/* Check errors */
		if ( ! $user || is_wp_error( $user ) ) {

			/* Get the redirect url */
			$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );

			/* Add error code to redirect url query args */
			if ( $user && $user->get_error_code() === 'expired_key' ) {
				$redirect_url = add_query_arg( 'errors', 'expired_key', $redirect_url );
			} else {
				$redirect_url = add_query_arg( 'errors', 'invalid_key', $redirect_url );
			}

			/* Redirect user */
			$this->redirect_user( $redirect_url, $password_reset_url );

		}

		/* Check both passwords exists */
		if ( ! isset( $_POST['pass1'] ) && ! isset( $_POST['pass2'] ) ) {

			/* Get the redirect url */
			$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );

			/* Add error code to redirect url query args */
			$redirect_url = add_query_arg( 'errors', 'request_error', $redirect_url );

			/* Redirect user */
			$this->redirect_user( $redirect_url, $password_reset_url );

		}

		/* Update the user password */
		if ( ! empty( $_POST['pass1'] ) && ! empty( $_POST['pass2'] ) ) {

			/* Compare passwords */
			if ( $_POST['pass1'] == $_POST['pass2'] ) {

			    /* Sanitize password */
			    $password = $this->sanitize_var( $_POST['pass1'], 'string', '' );

				/* Get the password length */
				$str_len = mb_strlen( $password, 'UTF-8' );

				/* Check the password length */
				if ( $str_len >= 8 && $str_len <= 64 ) {

					/* Reset user password */
					reset_password( $user, esc_attr( $password ) );

					/* Redirect user to success page */
					wp_redirect( home_url( 'login?password=changed' ) );

					/* Exit */
					exit;

				} else {

					/* Error: password length less than 8 or greater than 64 chars */
					$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );
					$redirect_url = add_query_arg( 'errors', 'password_length', $redirect_url );
					$this->redirect_user( $redirect_url, $password_reset_url );

				}

			} else {

				/* Passwords does not match */
				$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );
				$redirect_url = add_query_arg( 'errors', 'passwords_not_match', $redirect_url );
				$this->redirect_user( $redirect_url, $password_reset_url );

			}

		} elseif ( empty( $_POST['pass1'] ) ) {

			/* Error: password is empty */
			$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );
			$redirect_url = add_query_arg( 'errors', 'empty_password', $redirect_url );
			$this->redirect_user( $redirect_url, $password_reset_url );

		} else {

			/* Error: passwords does not matching, verification password is empty */
			$redirect_url = $this->get_password_reset_url( $rp_key, $rp_login );
			$redirect_url = add_query_arg( 'errors', 'passwords_not_match', $redirect_url );
			$this->redirect_user( $redirect_url, $password_reset_url );

		}

	} // $this->do_password_reset();


	/**
	 * Get the password reset url.
	 *
	 * @param string $rp_key
	 * @param string $rp_login
	 * @return string
	 */
	public function get_password_reset_url( $rp_key, $rp_login ) {

		/* Sanitize reset key */
		$rp_key = $this->sanitize_var( $rp_key, 'string', '' );

		/* Sanitize login */
		$rp_login = $this->sanitize_var( $rp_login, 'string', '' );

		/* Set the redirect url */
		$redirect_url = home_url( 'password-reset' );
		$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
		$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );

		/* Return the redirect url */
		return $redirect_url;

	} // $this->get_password_reset_url();


	/**
	 * Retrieve the reset password message.
	 *
	 * @param string $message
	 * @param string $key
	 * @param string $user_login
	 * @param WP_User $user_data
	 * @return string
	 */
	public function reset_password_message( $message, $key, $user_login, $user_data ) {

		/* Set the reset password url */
		$url = site_url( 'wp-login.php' );
		$url = add_query_arg( 'action', 'rp', $url );
		$url = add_query_arg( 'key', $key, $url );
		$url = add_query_arg( 'login', $user_login, $url );
		$url = esc_url_raw( $url );

		/* Set the message text */
		$message  = __( 'Hello!', 'webpoint-login' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Someone has requested a password reset for the following account: %s', 'webpoint-login' ), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'webpoint-login' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:', 'webpoint-login' ) . "\r\n\r\n";
		$message .= $url . "\r\n\r\n";
		$message .= sprintf( __( 'Sincerely, Administration %s', 'webpoint-login' ), $_SERVER["SERVER_NAME"] ) . "\r\n";

		/* Return the message text */
		return $message;

	} // $this->reset_password_message();


	/**
	 * Register new user.
	 *
	 * @param string $user_email
	 * @param string $user_login
	 * @param string $user_pass1
	 * @param string $user_pass2
	 * @return int|array|WP_Error
	 */
	private function register_user( $user_email, $user_login, $user_pass1, $user_pass2 ) {

		/* Init errors */
		$errors = new WP_Error();
		
		/* Check the user login exists */
		if ( empty( $user_login ) ) {
			$errors->add( 'empty_username', $this->get_error_message( 'empty_username' ) );
			return $errors;
		}

		/* Check the user login length */
		$user_login_length = mb_strlen( $user_login, 'UTF-8' );
		if ( $user_login_length < 1 || $user_login_length > 60 ) {
			$errors->add( 'username_length', $this->get_error_message( 'username_length' ) );
			return $errors;
		}

		/* Check the username string */
		$user_login = $this->sanitize_var( $user_login, 'login', false );
		if ( ! $user_login || ! validate_username( $user_login ) ) {
			$errors->add( 'validate_username', $this->get_error_message( 'validate_username' ) );
			return $errors;
		}

		/* Check the username exists */
		if ( username_exists( $user_login ) ) {
			$errors->add( 'username_exists', $this->get_error_message( 'username_exists' ) );
			return $errors;
		}

		/* Sanitize the username */
		$login = sanitize_user( $user_login, true );

		/* Check user email exists */
		if ( empty( $user_email ) ) {
			$errors->add( 'empty_email', $this->get_error_message( 'empty_email' ) );
			return $errors;
		}

		/* Check user email length */
		$user_email_length = mb_strlen( $user_email, 'UTF-8' );
		if ( $user_email_length > 100 ) {
			$errors->add( 'email_length', $this->get_error_message( 'email_length' ) );
			return $errors;
		}

		/* Sanitize user email */
		$user_email = $this->sanitize_var( $user_email, 'email', false );
		if ( ! $user_email ) {
			$errors->add( 'invalid_email', $this->get_error_message( 'invalid_email' ) );
			return $errors;
		}

		/* Check user email exists */
		if ( email_exists( $user_email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
			return $errors;
		}

		/* Sanitize user email */
		$email = sanitize_email( $user_email );
		
		/* Check user passwords exists */
		if ( empty( $user_pass1 ) ) {
			$errors->add( 'empty_password', $this->get_error_message( 'empty_password' ) );
			return $errors;
		}

		/* Check passwords match */
		if ( $user_pass1 != $user_pass2 ) {
			$errors->add( 'passwords_not_match', $this->get_error_message( 'passwords_not_match' ) );
			return $errors;
		}

		/* Check password length */
		$password_length = mb_strlen( $user_pass1, 'UTF-8' );
		if ( $password_length < 8 || $password_length > 64 ) {
			$errors->add( 'password_length', $this->get_error_message( 'password_length' ) );
			return $errors;
		}

		/* Sanitize user password */
		$password = $this->sanitize_var( $user_pass1, 'string', '' );

		/* Check user data just in case */
		if ( empty( $login ) || empty( $email ) || empty( $password ) ) {
			$errors->add( 'register_error', $this->get_error_message( 'register_error' ) );
			return $errors;
		}

		/* Set user data */
		$user_data = array(
			'user_login'    => $login,
			'user_email'    => $email,
			'user_pass'     => $password,
			'user_nicename' => $login,
			'display_name'  => $login
		);

		/* Register new user */
		$user_id = wp_insert_user( $user_data );

		/* Check registered user */
		if ( ! $this->sanitize_var( $user_id, 'absint', false ) ) {
			$errors->add( 'register_error', $this->get_error_message( 'register_error' ) );
			return $errors;
		}

		/* Send mail after user has been registered */
		$this->register_user_mail( $email );

		/* Return the user id */
		return $user_id;

	} // register_user();


	/**
	 * Send mail after user has been registered.
	 *
	 * @param string $email
	 * @return bool
	 */
	public function register_user_mail( $email = null ) {

		/* Sanitize email */
		$email = $this->sanitize_var( $email, 'email', false );
		if ( ! $email ) {
			return false;
		}

		/* Get site name and url */
		$site_name = $this->sanitize_var( $_SERVER["SERVER_NAME"], 'string', '' );
		$site_url = $this->sanitize_var( $_SERVER["SERVER_NAME"], 'url', '' );

		/* Prepare message */
		$headers[] = 'From: ' . $site_name . ' <noreply@' . esc_attr( $site_url ) . '>';
		$headers[] = 'content-type: text/html';

		$subject = sprintf(
			__( 'Registration on the site %s', 'webpoint-login' ),
			$site_name
		);

		$message = '<p>' . sprintf( __( 'You have successfully registered on the site %s', 'webpoint-login' ), $site_name ) . '</p>';
		$message .= '<p>' . sprintf( __( 'Now you can %slog in%s using your username and password.', 'webpoint-login' ), '<a href="' . esc_url( wp_login_url() ) . '">', '</a>' ) . '</p>';
		$message .= '<p>' . sprintf( __( 'Sincerely, Administration %s', 'webpoint-login' ), '<a href="' . esc_url( $site_url ) . '">' . $site_name . '</a>' ) . '</p>';

		/* Send notification about user registration to email */
		return wp_mail( $email, $subject, $message, $headers );

	} // $this->register_user_mail();


	/**
	 * Get Google reCAPTCHA keys.
	 *
	 * @param string $key
	 * @return string|array|false
	 */
	private function get_recaptcha_keys( $key = null ) {

		/* Validate key */
		if ( ! is_null( $key ) && $key != 'site_key' && $key != 'secret_key' ) {
			return false;
		}

		/* Get plugin settings */
		$plugin_settings = $this->get_plugin_settings( 'webpoint_login_settings' );
		if ( ! $plugin_settings ) {
			return false;
		}

		/* Init reCAPTCHA key */
		$recaptcha_keys = array();

		/* Sanitize reCAPTCHA site key */
		$recaptcha_keys['site_key'] = $this->sanitize_var(
			$plugin_settings['recaptcha_site_key'],
			'string',
			false
		);

		/* Check reCAPTCHA site key */
		if ( ! $recaptcha_keys['site_key'] ) {
			return false;
		}

		/* Sanitize reCAPTCHA secret key */
		$recaptcha_keys['secret_key'] = $this->sanitize_var(
			$plugin_settings['recaptcha_secret_key'],
			'string',
			false
		);

		/* Check reCAPTCHA secret key */
		if ( ! $recaptcha_keys['secret_key'] ) {
			return false;
		}

		/* Return reCAPTCHA keys */
		return ! is_null( $key ) ? $recaptcha_keys[ $key ] : $recaptcha_keys;

	} // $this->get_recaptcha_keys();


	/**
	 * Check reCAPTCHA is activated.
	 *
	 * @return string|int 0
	 */
	public function is_recaptcha_activated() {

		/* Get reCAPTCHA keys */
		$recaptcha_keys = $this->get_recaptcha_keys();

		/* Check reCAPTCHA keys */
		if ( $recaptcha_keys === false ) {
			return 0;
		}

		/* Return reCAPTCHA site key */
		return $recaptcha_keys['site_key'];

	} // $this->is_recaptcha_activated();


	/**
	 * Get Google reCAPTCHA HTML.
	 *
	 * @return string|false
	 */
	public function get_recaptcha_html() {

		/* Get reCAPTCHA keys */
		$keys = $this->get_recaptcha_keys();

		/* Check reCAPTCHA keys */
		if ( $keys === false ) {
			return false;
		}

		/* Return reCAPTCHA HTML */
		return sprintf( '<div class="recaptcha-i" data-site_key="%s"><i class="fa fa-spinner fa-pulse fa-fw"></i> %s</div>', $keys['site_key'], __( 'Loading Google reCAPTCHA', 'webpoint-login' ) );

	} // $this->get_recaptcha_html();


	/**
	 * Display Google reCAPTCHA Form.
	 *
	 * @param string $before
	 * @param string $after
	 */
	public function recaptcha_html( $before = '', $after = '' ) {

		/* Get reCAPTCHA HTML */
		$html = $this->get_recaptcha_html();

		/* Display reCAPTCHA form with wrap */
		echo $html ? $before . $html . $after : '';

	} // $this->recaptcha_html();


	/**
	 * Check Google reCAPTCHA response.
	 *
	 * @return bool
	 */
	private function check_recaptcha_response() {

		/* Get reCAPTCHA keys */
		$recaptcha_keys = $this->get_recaptcha_keys();

		/* Check reCAPTCHA keys */
		if ( $recaptcha_keys === false ) {
			return true;
		}

		/* This field is set by the recaptcha widget if checking is successful */
		if ( isset( $_POST['g-recaptcha-response'] ) ) {
			$g_recaptcha_response = $_POST['g-recaptcha-response'];
		} else {
			return false;
		}

		/* Get reCAPTCHA response from Google */
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret'   => $recaptcha_keys['secret_key'],
					'response' => $g_recaptcha_response,
					'remoteip' => $_SERVER['REMOTE_ADDR']
				)
			)
		);

		/* Get the response status */
		if ( $response && is_array( $response ) ) {
			$decoded_response = json_decode( $response['body'] );
			$success = $decoded_response->success;
		} else {
			$success = false;
		}

		/* Return response status */
		return $success;

	} // $this->check_recaptcha_response();


	/**
	 * Redirect logged in users.
	 *
	 * @param string $redirect_to
	 */
	private function redirect_logged_in_user( $redirect_to = null ) {

		/* Sanitize redirect url */
		$redirect_to = $this->sanitize_var( $redirect_to, 'url', false );

		/* Redirect user */
		if ( $redirect_to ) {
			wp_safe_redirect( $redirect_to );
		} else {
			wp_redirect( admin_url() );
		}

		/* Exit */
		exit;

	} // $this->redirect_logged_in_user();


	/**
	 * Logged in user template.
	 *
	 * @return string
	 */
	public function logged_in_user_template() {

		return '<div class="webpoint-login"><p class="success">' . sprintf( __( 'You are already authorized on the site. Go to the %scontrol panel%s.', 'webpoint-login' ), '<a href="' . esc_url( admin_url() ) . '">', '</a>' ) . '</p></div><!-- #login -->';

	} // $this->logged_in_user_template();


	/**
	 * Display errors.
	 *
	 * @param array $errors
	 */
	public function errors_template( $errors = array() ) {

		/* Check errors */
		if ( empty( $errors ) || ! is_array( $errors ) ) {
			return;
		}

		/* Display errors */
		foreach ( $errors as $error ) {
			printf( '<p class="error">%s</p>', $error );
		}

	} // $this->errors_template():


	/**
	 * Get error message by error code.
	 *
	 * @param string $error_code
	 * @return string
	 */
	private function get_error_message( $error_code ) {

		/* Init the default error message */
		$default = __( 'Unknown error. Notify the administrator and try again later.', 'webpoint-login' );

		/* Sanitize error code */
		$error_code = $this->sanitize_var( $error_code, 'term', false );
		if ( ! $error_code ) {
			return $default;
		}

		/* Set errors */
		$errors = array(
			'request_error' => __( 'Request error. Please refresh the page and try again.', 'webpoint-login' ),
			'nonce_error' => __( 'Request error. Please refresh the page and try again.', 'webpoint-login' ),
			'captcha_error' => __( 'reCAPTCHA Error. Check the box "I\'m not a robot" and try again.', 'webpoint-login' ),
			'invalid_username' => __( 'Invalid username.', 'webpoint-login' ),
			'incorrect_password' => sprintf( __( 'Incorrect password. %sForgot your password?%s', 'webpoint-login' ), '<a href="' . esc_url( home_url( 'password-lost' ) ) . '">', '</a>' ),
			'empty_username' => __( 'Username cannot be empty.', 'webpoint-login' ),
			'empty_email' => __( 'Email address cannot be empty.', 'webpoint-login' ),
			'empty_password' => __( 'Password cannot be empty.', 'webpoint-login' ),
			'username_exists' => __( 'Username already exists.', 'webpoint-login' ),
			'email_exists' => __( 'Email already exists.', 'webpoint-login' ),
			'validate_username' => __( 'Username can contain lowercase letters of the Latin alphabet (a-z), numbers (0-9), single hyphens (-) and underscores (_). It should start with a letter, end with a letter or number and may not be longer than 60 characters.', 'webpoint-login' ),
			'invalid_email' => __( 'Incorrect email address.', 'webpoint-login' ),
			'username_length' => __( 'Username must be between 1 and 60 characters.', 'webpoint-login' ),
			'email_length' => __( 'Email address should not contain more than 100 characters.', 'webpoint-login' ),
			'password_length' => __( 'Password must be between 8 and 64 characters.', 'webpoint-login' ),
			'passwords_not_match' => __( 'Passwords do not match.', 'webpoint-login' ),
			'register_closed' => __( 'User registration is disabled.', 'webpoint-login' ),
			'register_error' => __( 'Registration error. Please refresh the page and try again.', 'webpoint-login' ),
			'expired_key' => sprintf( __( 'Invalid password reset link. %sRequest a new link?%s', 'webpoint-login' ), '<a href="' . esc_url( home_url( 'password-lost' ) ) . '">', '</a>' ),
			'invalid_key' => sprintf( __( 'Invalid password reset link. %sRequest a new link?%s', 'webpoint-login' ), '<a href="' . esc_url( home_url( 'password-lost' ) ) . '">', '</a>' ),
			'invalidcombo' => __( 'Incorrect username or email.', 'webpoint-login' )
		);

		/* Return error message */
		return isset( $errors[ $error_code ] ) ? $errors[ $error_code ] : $default;

	} // $this->get_error_message();


} // WebPoint_Login class

endif;

/**
 * Main instance of WebPoint_Login.
 *
 * Returns the main instance of WebPoint_Login to prevent the need to use globals.
 *
 * @return WebPoint_Login
 */
function WebPoint_Login() {
	return WebPoint_Login::instance();
} // WebPoint_Login();

/* Init plugin */
WebPoint_Login();
