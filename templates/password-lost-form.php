<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
} ?>

<div id="password-lost-form" class="webpoint-login">

	<?php if ( isset( $args['errors'] ) ) : ?>
		<?php $this->errors_template( $args['errors'] ); ?>
	<?php endif; ?>

	<?php /* ?><p class="notice"><?php _e( 'Please enter your username or email address.', 'webpoint-login' ); ?></p><?php */ ?>
	
	<form id="lostpasswordform" action="<?php esc_attr_e( wp_lostpassword_url() ); ?>" method="post" autocomplete="off">

		<p class="form-row">
			<label for="user_login"><?php _e( 'Enter your username or email:', 'webpoint-login' ); ?></label>
			<input type="text" name="user_login" id="user_login" placeholder="<?php esc_attr_e( 'Username or email', 'webpoint-login' ); ?>" maxlength="32" />
		</p><!-- .form-row -->

		<?php $this->recaptcha_html( '<div class="recaptcha">', '</div><!-- .recaptcha -->' ); ?>

		<?php wp_nonce_field( 'lost_password' ); ?>

		<p class="form-row">
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Submit', 'webpoint-login' ); ?>" class="button" />
		</p><!-- .form-row -->

	</form><!-- #lostpasswordform -->

</div><!-- #password-lost-form -->