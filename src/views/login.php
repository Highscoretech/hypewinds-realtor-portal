<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Realtor login', 'Sign in to your realtor portal' ); ?>

<form method="post" action="<?php echo e( url( 'login' ) ); ?>" class="form">
	<?php echo csrf_field(); ?>
	<?php // type="text", not "email": a username or CID is valid here too. ?>
	<label class="field"><span>Email, username or CID</span>
		<input class="input" type="text" name="email" id="email" value="<?php echo e( $email ); ?>" autocomplete="username" placeholder="Email, username or CID" required autofocus>
		<small class="hint">All three are in your welcome email. The CID is the six-digit number.</small>
	</label>
	<label class="field"><span>Password</span>
		<input class="input" type="password" name="password" id="password" autocomplete="current-password" required>
	</label>
	<button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>

<p class="auth-forgot"><a href="<?php echo e( url( 'forgot' ) ); ?>">Forgot your password?</a></p>
<p class="auth-alt">No account yet? <a href="<?php echo e( url( 'register' ) ); ?>">Register as a realtor</a></p>

<?php auth_end(); ?>
