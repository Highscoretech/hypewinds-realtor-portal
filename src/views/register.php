<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Realtor registration', 'Join the premium realtors network', true ); ?>

<?php if ( $ref && $star ) : ?>
	<p class="hrp-alert hrp-alert-ok">Your premium-star: <strong><?php echo e( $star['full_name'] ); ?></strong></p>
<?php elseif ( $ref ) : ?>
	<p class="hrp-alert hrp-alert-error">That referral link is not valid. You can still register without one.</p>
<?php endif; ?>

<form method="post" action="<?php echo e( url( 'register' ) ); ?>">
	<?php echo csrf_field(); ?>
	<input type="hidden" name="ref" value="<?php echo e( $star ? $ref : '' ); ?>">

	<div class="hrp-grid">
		<label>Full Name<input class="hrp-input" type="text" name="full_name" value="<?php echo e( $old['full_name'] ?? '' ); ?>" required></label>
		<label>Email<input class="hrp-input" type="email" name="email" value="<?php echo e( $old['email'] ?? '' ); ?>" required></label>
		<?php foreach ( member_fields() as $name => $label ) :
			$type = 'date_of_birth' === $name ? 'date' : ( 'phone' === $name ? 'tel' : 'text' ); ?>
			<label><?php echo e( $label ); ?>
				<input class="hrp-input" type="<?php echo e( $type ); ?>" name="<?php echo e( $name ); ?>" value="<?php echo e( $old[ $name ] ?? '' ); ?>"></label>
		<?php endforeach; ?>
	</div>

	<p class="hrp-muted">Your password will be emailed to you once you register.</p>
	<label class="hrp-check"><input type="checkbox" name="terms" value="1" required> I agree to the terms</label>
	<p><button class="hrp-button" type="submit">Register</button></p>
</form>

<p class="hrp-auth-alt">Already have an account? <a href="<?php echo e( url( 'login' ) ); ?>">Login</a></p>

<?php auth_end(); ?>
