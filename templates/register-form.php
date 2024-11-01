<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
} ?>

<div id="register-form" class="webpoint-login">

	<?php if ( isset( $args['errors'] ) ) : ?>
		<?php $this->errors_template( $args['errors'] ); ?>
	<?php endif; ?>

	<form id="signupform" action="<?php esc_attr_e( wp_registration_url() ); ?>" method="post" autocomplete="off" novalidate>
		
		<p class="form-row">
			<label for="user_login"><?php _e( 'Username', 'webpoint-login' ); ?> <span class="required">*</span></label>
			<input type="text" name="user_login" id="user_login" maxlength="32" />
		</p><!-- .form-row -->
		
		<p class="form-row">
			<label for="email"><?php _e( 'Email', 'webpoint-login' ); ?> <span class="required">*</span></label>
			<input type="email" name="email" id="email" maxlength="100" />
		</p><!-- .form-row -->
		
		<p class="form-row">
			<label for="pass1"><?php _e( 'Password', 'webpoint-login' ); ?> <span class="required">*</span></label>
			<input type="password" name="pass1" id="pass1" class="text-input" maxlength="64" />
		</p><!-- .form-row -->
		
		<p class="form-row">
			<label for="pass2"><?php _e( 'Confirm password', 'webpoint-login' ); ?> <span class="required">*</span></label>
			<input type="password" name="pass2" id="pass2" class="text-input" maxlength="64" />
		</p><!-- .form-row -->

		<p class="form-row form-info">
			<span class="req">*</span> - <?php _e( 'Required fields', 'webpoint-login' ); ?>
		</p><!-- .form-info -->

		<?php $this->recaptcha_html( '<div class="recaptcha">', '</div><!-- .recaptcha -->' ); ?>

		<?php wp_nonce_field( 'register_user' ); ?>

		<p class="form-row">
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Sign up', 'webpoint-login' ); ?>" class="button" />
		</p><!-- .form-row -->

	</form><!-- #signupform -->

</div><!-- #register-form -->