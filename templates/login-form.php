<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
} ?>

<div id="login" class="webpoint-login">

	<?php if ( isset( $args['errors'] ) ) : ?>
        <?php $this->errors_template( $args['errors'] ); ?>
    <?php endif; ?>

	<?php if ( isset( $args['logged_out'] ) && $args['logged_out'] ) : ?>
		<p class="notice">
            <?php _e( 'You are logged out. Do you want to log in again?', 'webpoint-login' ); ?>
        </p>
	<?php endif; ?>

	<?php if ( isset( $args['registered'] ) && $args['registered'] ) : ?>
		<p class="success">
            <?php printf( __( 'You have successfully registered on the site %s. Now you can log in using your username and password.', 'webpoint-login' ), '<strong>' . $_SERVER["SERVER_NAME"] . '</strong>' ); ?>
        </p>
	<?php endif; ?>

	<?php if ( isset( $args['lost_password_sent'] ) && $args['lost_password_sent'] ) : ?>
		<p class="notice">
            <?php _e( 'You have been sent an email with a link to reset your password.', 'webpoint-login' ); ?>
        </p>
	<?php endif; ?>

	<?php if ( isset( $args['password_updated'] ) && $args['password_updated'] ) : ?>
		<p class="success">
            <?php _e( 'Your password has been changed. You can log in using the new password.', 'webpoint-login' ); ?>
        </p>
	<?php endif; ?>

	<?php wp_login_form(
		array(
			'label_username' => __( 'Username', 'webpoint-login' ) . ':',
			'label_password' => __( 'Password', 'webpoint-login' ) . ':',
			'label_remember' => __( 'Remember me', 'webpoint-login' ),
			'label_log_in'   => __( 'Log in', 'webpoint-login' ),
			'redirect'       => isset( $args['redirect'] ) ? $args['redirect'] : '',
		)
	); ?>

	<?php printf(
		'<p><a class="forgot-password" href="%s">%s</a></p>',
		esc_attr( wp_lostpassword_url() ),
		__( 'Forgot your password?', 'webpoint-login' )
	); ?>

</div><!-- #login -->