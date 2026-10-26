<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Reset your password', 'We will email you a link to choose a new one' ); ?>

<form method="post" action="<?php echo e( url( 'forgot' ) ); ?>" class="form">
	<?php echo csrf_field(); ?>
	<label class="field"><span>Email</span>
		<input class="input" type="email" name="email" id="email" autocomplete="username" required autofocus>
	</label>
	<button class="btn btn-primary btn-block" type="submit">Send reset link</button>
</form>

<p class="auth-alt">Remembered it? <a href="<?php echo e( url( 'login' ) ); ?>">Back to sign in</a></p>

<?php auth_end(); ?>
