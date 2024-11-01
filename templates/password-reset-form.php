<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
} ?>

<div id="password-reset-form" class="webpoint-login">

	<form name="resetpassform" id="resetpassform" action="<?php esc_attr_e( site_url( 'wp-login.php?action=resetpass' ) ); ?>" method="post" autocomplete="off">

		<?php printf(
			'<input type="hidden" name="rp_login" value="%s" />',
			isset( $args['login'] ) ? esc_attr( $args['login'] ) : ''
		); ?>

		<?php printf(
			'<input type="hidden" name="rp_key" value="%s" />',
			isset( $args['key'] ) ? esc_attr( $args['key'] ) : ''
		); ?>

		<?php if ( isset( $args['errors'] ) ) : ?>
			<?php $this->errors_template( $args['errors'] ); ?>
		<?php endif; ?>

		<p class="form-row">
			<label for="pass1"><?php _e( 'Enter a new password', 'webpoint-login' ); ?> <span class="required">*</span></label>
			<input type="password" name="pass1" value="" id="pass1" class="input" maxlength="64" />
		</p><!-- .form-row -->
		
		<p class="form-row">
			<label for="pass2"><?php _e( 'Confirm password', 'webpoint-login' ); ?> <span class="required">*</span></label>
			<input type="password" name="pass2" value="" id="pass2" class="input" maxlength="64" />
		</p><!-- .form-row -->

		<p class="notice"><?php _e( 'To create a strong password, use uppercase and lowercase letters, numbers and symbols like ! " ? $ % ^ & ).', 'webpoint-login' ); ?></p>

		<p class="form-row form-info">
			<span class="req">*</span> - <?php _e( 'Required fields', 'webpoint-login' ); ?>
		</p><!-- .form-info -->

		<?php $this->recaptcha_html( '<div class="recaptcha">', '</div><!-- .recaptcha -->' ); ?>

		<?php wp_nonce_field( 'reset_password' ); ?>

		<p class="form-row">
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Save', 'webpoint-login' ); ?>" id="rp-button" class="button" />
		</p><!-- .form-row -->

	</form><!-- #resetpassform -->

</div><!-- #password-reset-form -->