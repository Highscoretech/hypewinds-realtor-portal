<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Realtor registration', 'Join the Hypewinds premium realtors network', true ); ?>

<?php if ( $ref && $star ) : ?>
	<div class="alert alert-ok"><?php echo icon( 'star' ); ?><p>Your premium-star: <strong><?php echo e( $star['full_name'] ); ?></strong></p></div>
<?php elseif ( $ref ) : ?>
	<div class="alert alert-error"><?php echo icon( 'alert' ); ?><p>That referral link is not valid. You can still register without one.</p></div>
<?php endif; ?>

<form method="post" action="<?php echo e( url( 'register' ) ); ?>" class="form">
	<?php echo csrf_field(); ?>
	<input type="hidden" name="ref" value="<?php echo e( $star ? $ref : '' ); ?>">

	<p class="form-section">Your details</p>
	<div class="form-grid">
		<label class="field"><span>Full name</span><input class="input" type="text" name="full_name" value="<?php echo e( $old['full_name'] ?? '' ); ?>" autocomplete="name" required></label>
		<label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?php echo e( $old['email'] ?? '' ); ?>" autocomplete="email" required></label>
		<?php
		// A sub-heading starts before these fields.
		$sections = array( 'bank_name' => 'Bank details for commission payments' );
		foreach ( member_fields() as $name => $label ) :
			if ( isset( $sections[ $name ] ) ) :
				?>
	</div>
	<p class="form-section"><?php echo e( $sections[ $name ] ); ?></p>
	<div class="form-grid">
				<?php
			endif;
			$type = 'date_of_birth' === $name ? 'date' : ( 'phone' === $name ? 'tel' : 'text' );
			?>
			<label class="field"><span><?php echo e( $label ); ?></span>
				<input class="input" type="<?php echo e( $type ); ?>" name="<?php echo e( $name ); ?>" value="<?php echo e( $old[ $name ] ?? '' ); ?>"<?php echo 'account_number' === $name ? ' inputmode="numeric"' : ''; ?>></label>
		<?php endforeach; ?>
	</div>

	<label class="check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the terms</span></label>
	<button class="btn btn-primary btn-block" type="submit">Create my account</button>
	<p class="muted center">Your password will be emailed to you once you register.</p>
</form>

<p class="auth-alt">Already have an account? <a href="<?php echo e( url( 'login' ) ); ?>">Sign in</a></p>

<?php auth_end(); ?>
