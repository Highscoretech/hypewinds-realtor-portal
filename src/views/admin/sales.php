<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Sales & commissions', current_user(), 'admin' ); ?>

<section class="hrp-card">
	<h2>Record a sale</h2>
	<?php if ( ! $realtors ) : ?>
		<p class="hrp-muted">No realtors have registered yet, so there is nobody to credit a sale to.</p>
	<?php else : ?>
		<form method="post" action="<?php echo e( url( 'admin' ) ); ?>">
			<?php echo csrf_field(); ?>
			<div class="hrp-grid">
				<label>Realtor who sold
					<select name="seller_id" required>
						<option value="">Select</option>
						<?php foreach ( $realtors as $realtor ) : ?>
							<option value="<?php echo e( $realtor['id'] ); ?>"><?php echo e( $realtor['full_name'] . ' (' . $realtor['ref_code'] . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Property
					<select name="property_type">
						<option value="land">Land</option>
						<option value="house">House</option>
					</select>
				</label>
				<label>Sale amount<input type="number" step="0.01" min="0.01" name="amount" required></label>
				<label>Date sold<input type="date" name="sold_on" value="<?php echo e( date( 'Y-m-d' ) ); ?>"></label>
			</div>
			<label>Description<input type="text" name="description" maxlength="255"></label>
			<p class="hrp-muted">The seller's premium-star earns <?php echo e( commission_rate() ); ?>% of the amount. Change the rate in Settings.</p>
			<p><button class="hrp-button" type="submit">Record sale</button></p>
		</form>
	<?php endif; ?>
</section>

<section class="hrp-card">
	<h2>Recent sales</h2>
	<?php if ( ! $sales ) : ?>
		<p class="hrp-muted">No sales recorded yet.</p>
	<?php else : ?>
		<div class="hrp-scroll">
			<table class="hrp-table">
				<thead><tr><th>Date</th><th>Realtor</th><th>Property</th><th>Amount</th><th>Premium-star</th><th>Commission</th></tr></thead>
				<tbody>
				<?php foreach ( $sales as $sale ) : ?>
					<tr>
						<td><?php echo e( format_date( $sale['sold_on'] ) ); ?></td>
						<td><?php echo e( $names[ $sale['seller_id'] ] ?? '-' ); ?></td>
						<td><?php echo e( ucfirst( $sale['property_type'] ) . ( '' !== $sale['description'] ? ': ' . $sale['description'] : '' ) ); ?></td>
						<td><?php echo e( money( $sale['amount'] ) ); ?></td>
						<td><?php echo e( $sale['beneficiary_id'] ? ( $names[ $sale['beneficiary_id'] ] ?? '-' ) : '-' ); ?></td>
						<td><?php echo e( null !== $sale['commission'] ? money( $sale['commission'] ) : '-' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
