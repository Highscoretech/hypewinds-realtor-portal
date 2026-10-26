<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Choose a new password', 'At least 8 characters' ); ?>

<form method="post" action="<?php echo e( url( 'reset', array( 'token' => $token ) ) ); ?>">
	<?php echo csrf_field(); ?>
	<p class="login-password">
		<label for="password">New password</label>
		<input type="password" name="password" id="password" autocomplete="new-password" minlength="8" required autofocus>
	</p>
	<p class="login-password">
		<label for="password_confirm">Type it again</label>
		<input type="password" name="password_confirm" id="password_confirm" autocomplete="new-password" minlength="8" required>
	</p>
	<p class="login-submit">
		<input type="submit" value="Save password">
	</p>
</form>

<?php auth_end(); ?>
