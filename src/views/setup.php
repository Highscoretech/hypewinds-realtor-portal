<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Portal setup', 'Create the admin account. This page closes once it exists.' ); ?>

<form method="post" action="<?php echo e( url( 'setup', array( 'key' => $key ) ) ); ?>">
	<?php echo csrf_field(); ?>
	<p class="login-username">
		<label for="full_name">Your name</label>
		<input type="text" name="full_name" id="full_name" required autofocus>
	</p>
	<p class="login-username">
		<label for="email">Email</label>
		<input type="email" name="email" id="email" autocomplete="username" required>
	</p>
	<p class="login-password">
		<label for="password">Password</label>
		<input type="password" name="password" id="password" autocomplete="new-password" minlength="8" required>
	</p>
	<p class="login-password">
		<label for="password_confirm">Type it again</label>
		<input type="password" name="password_confirm" id="password_confirm" autocomplete="new-password" minlength="8" required>
	</p>
	<p class="login-submit">
		<input type="submit" value="Create admin">
	</p>
</form>

<?php auth_end(); ?>
