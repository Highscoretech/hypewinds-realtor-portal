<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Realtor login', 'Sign in to your realtor portal' ); ?>

<form method="post" action="<?php echo e( url( 'login' ) ); ?>" class="form">
	<?php echo csrf_field(); ?>
	<label class="field"><span>Email</span>
		<input class="input" type="email" name="email" id="email" value="<?php echo e( $email ); ?>" autocomplete="username" required autofocus>
	</label>
	<label class="field"><span>Password</span>
		<input class="input" type="password" name="password" id="password" autocomplete="current-password" required>
	</label>
	<button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>

<p class="auth-forgot"><a href="<?php echo e( url( 'forgot' ) ); ?>">Forgot your password?</a></p>
<p class="auth-alt">No account yet? <a href="<?php echo e( url( 'register' ) ); ?>">Register as a realtor</a></p>

<?php auth_end(); ?>
