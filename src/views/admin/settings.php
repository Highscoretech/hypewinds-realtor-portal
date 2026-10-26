<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Settings', current_user(), 'admin/settings' ); ?>

<section class="hrp-card hrp-narrow">
	<form method="post" action="<?php echo e( url( 'admin/settings' ) ); ?>">
		<?php echo csrf_field(); ?>
		<label>Commission rate (%)
			<input type="number" step="0.01" min="0" max="100" name="commission_percent" value="<?php echo e( $rate ); ?>" required>
		</label>
		<p class="hrp-muted">Applies to sales recorded from now on. Past commissions keep the rate they were paid at.</p>

		<label>WhatsApp group link
			<input type="url" name="whatsapp_group" value="<?php echo e( $group ); ?>" placeholder="https://chat.whatsapp.com/...">
		</label>
		<p class="hrp-muted">Included in the welcome email when set.</p>

		<p><button class="hrp-button" type="submit">Save settings</button></p>
	</form>
</section>

<section class="hrp-card hrp-narrow">
	<h2>Links to share</h2>
	<p>Portal: <strong><?php echo e( absolute_url() ); ?></strong></p>
	<p>Registration: <strong><?php echo e( absolute_url( 'register' ) ); ?></strong></p>
</section>

<?php shell_end(); ?>
