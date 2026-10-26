<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Realtor login', 'Sign in to your realtor portal' ); ?>

<form method="post" action="<?php echo e( url( 'login' ) ); ?>">
	<?php echo csrf_field(); ?>
	<p class="login-username">
		<label for="email">Email</label>
		<input type="email" name="email" id="email" value="<?php echo e( $email ); ?>" autocomplete="username" required autofocus>
	</p>
	<p class="login-password">
		<label for="password">Password</label>
		<input type="password" name="password" id="password" autocomplete="current-password" required>
	</p>
	<p class="login-submit">
		<input type="submit" value="Login">
	</p>
</form>

<p class="hrp-auth-forgot"><a href="<?php echo e( url( 'forgot' ) ); ?>">Forgot your password?</a></p>
<p class="hrp-auth-alt">No account yet? <a href="<?php echo e( url( 'register' ) ); ?>">Register</a></p>

<?php auth_end(); ?>
