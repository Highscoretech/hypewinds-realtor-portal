<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Choose a new password', 'Use at least 8 characters' ); ?>

<form method="post" action="<?php echo e( url( 'reset', array( 'token' => $token ) ) ); ?>" class="form">
	<?php echo csrf_field(); ?>
	<label class="field"><span>New password</span>
		<input class="input" type="password" name="password" id="password" autocomplete="new-password" minlength="8" required autofocus>
	</label>
	<label class="field"><span>Type it again</span>
		<input class="input" type="password" name="password_confirm" id="password_confirm" autocomplete="new-password" minlength="8" required>
	</label>
	<button class="btn btn-primary btn-block" type="submit">Save password</button>
</form>

<?php auth_end(); ?>
