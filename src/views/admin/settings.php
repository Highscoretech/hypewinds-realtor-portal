<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Settings', current_user(), 'admin/settings', 'Commission rate and the links realtors receive.' ); ?>

<div class="columns">
	<div class="column-main">
		<section class="card">
			<header class="card-head"><h2><?php echo icon( 'settings' ); ?> Portal settings</h2></header>
			<div class="card-body">
				<form method="post" action="<?php echo e( url( 'admin/settings' ) ); ?>" class="form">
					<?php echo csrf_field(); ?>
					<label class="field"><span>Commission rate (%)</span>
						<input class="input" type="number" step="0.01" min="0" max="100" name="commission_percent" value="<?php echo e( $rate ); ?>" required>
						<small>Applies to sales recorded from now on. Past commissions keep the rate they were paid at.</small>
					</label>
					<label class="field"><span>WhatsApp group link</span>
						<input class="input" type="url" name="whatsapp_group" value="<?php echo e( $group ); ?>" placeholder="https://chat.whatsapp.com/...">
						<small>Included in the welcome email when set.</small>
					</label>
					<div class="form-foot">
						<span></span>
						<button class="btn btn-primary" type="submit"><?php echo icon( 'check' ); ?><span>Save settings</span></button>
					</div>
				</form>
			</div>
		</section>
	</div>

	<div class="column-side">
		<section class="card">
			<header class="card-head"><h2><?php echo icon( 'link' ); ?> Links to share</h2></header>
			<div class="card-body stack-lg">
				<?php foreach ( array( 'Realtor portal' => absolute_url(), 'Registration' => absolute_url( 'register' ) ) as $label => $link ) : ?>
					<div>
						<span class="field-label"><?php echo e( $label ); ?></span>
						<div class="copy-field">
							<input type="text" class="input" readonly value="<?php echo e( $link ); ?>" aria-label="<?php echo e( $label ); ?>">
							<button type="button" class="icon-button" data-copy="<?php echo e( $link ); ?>" aria-label="Copy <?php echo e( $label ); ?> link"><?php echo icon( 'copy' ); ?></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
</div>

<?php shell_end(); ?>
