<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Reset your password', 'We will email you a link to choose a new one' ); ?>

<form method="post" action="<?php echo e( url( 'forgot' ) ); ?>">
	<?php echo csrf_field(); ?>
	<p class="login-username">
		<label for="email">Email</label>
		<input type="email" name="email" id="email" autocomplete="username" required autofocus>
	</p>
	<p class="login-submit">
		<input type="submit" value="Send reset link">
	</p>
</form>

<p class="hrp-auth-alt">Remembered it? <a href="<?php echo e( url( 'login' ) ); ?>">Back to login</a></p>

<?php auth_end(); ?>
