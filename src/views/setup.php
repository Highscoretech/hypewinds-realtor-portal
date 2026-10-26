<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Portal setup', 'Create the admin account. This page closes once it exists.' ); ?>

<form method="post" action="<?php echo e( url( 'setup', array( 'key' => $key ) ) ); ?>" class="form">
	<?php echo csrf_field(); ?>
	<label class="field"><span>Your name</span><input class="input" type="text" name="full_name" id="full_name" required autofocus></label>
	<label class="field"><span>Email</span><input class="input" type="email" name="email" id="email" autocomplete="username" required></label>
	<label class="field"><span>Password</span><input class="input" type="password" name="password" id="password" autocomplete="new-password" minlength="8" required></label>
	<label class="field"><span>Type it again</span><input class="input" type="password" name="password_confirm" id="password_confirm" autocomplete="new-password" minlength="8" required></label>
	<button class="btn btn-primary btn-block" type="submit">Create admin</button>
</form>

<?php auth_end(); ?>
