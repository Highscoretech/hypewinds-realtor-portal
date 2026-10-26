<?php defined( 'PORTAL' ) || exit; ?>
<?php
$total_sales = array_sum( array_column( $sales, 'amount' ) );
$total_paid  = array_sum( array_map( 'floatval', array_column( $sales, 'commission' ) ) );
shell_start( 'Sales & commissions', current_user(), 'admin', 'Record a sale and the seller\'s premium-star is paid automatically.' );
?>

<section class="stats stats-3">
	<div class="stat">
		<span class="stat-icon is-blue"><?php echo icon( 'chart' ); ?></span>
		<span class="stat-label">Sales recorded</span>
		<strong class="stat-value"><?php echo e( count( $sales ) ); ?></strong>
		<span class="stat-foot">Most recent 100</span>
	</div>
	<div class="stat">
		<span class="stat-icon is-violet"><?php echo icon( 'building' ); ?></span>
		<span class="stat-label">Sales value</span>
		<strong class="stat-value"><?php echo e( money( $total_sales ) ); ?></strong>
	</div>
	<div class="stat">
		<span class="stat-icon is-green"><?php echo icon( 'wallet' ); ?></span>
		<span class="stat-label">Commission paid</span>
		<strong class="stat-value"><?php echo e( money( $total_paid ) ); ?></strong>
		<span class="stat-foot">Current rate <?php echo e( commission_rate() ); ?>%</span>
	</div>
</section>

<section class="card">
	<header class="card-head"><h2><?php echo icon( 'plus' ); ?> Record a sale</h2></header>
	<div class="card-body">
		<?php if ( ! $realtors ) : ?>
			<?php echo empty_state( 'users', 'No realtors yet', 'Once realtors register, you can credit sales to them here.' ); ?>
		<?php else : ?>
			<form method="post" action="<?php echo e( url( 'admin' ) ); ?>" class="form">
				<?php echo csrf_field(); ?>
				<div class="form-grid">
					<label class="field"><span>Realtor who sold</span>
						<select class="input" name="seller_id" required>
							<option value="">Select a realtor</option>
							<?php foreach ( $realtors as $realtor ) : ?>
								<option value="<?php echo e( $realtor['id'] ); ?>"><?php echo e( $realtor['full_name'] . ' (' . $realtor['ref_code'] . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="field"><span>Property type</span>
						<select class="input" name="property_type">
							<option value="land">Land</option>
							<option value="house">House</option>
						</select>
					</label>
					<label class="field"><span>Sale amount (<?php echo e( config( 'currency' ) ); ?>)</span><input class="input" type="number" step="0.01" min="0.01" name="amount" required></label>
					<label class="field"><span>Date sold</span><input class="input" type="date" name="sold_on" value="<?php echo e( date( 'Y-m-d' ) ); ?>"></label>
					<label class="field field-wide"><span>Description</span><input class="input" type="text" name="description" maxlength="255" placeholder="e.g. Plot 4, Lekki Gardens"></label>
				</div>
				<div class="form-foot">
					<p class="muted">The seller's premium-star earns <?php echo e( commission_rate() ); ?>% of the amount. <a href="<?php echo e( url( 'admin/settings' ) ); ?>">Change the rate</a></p>
					<button class="btn btn-primary" type="submit"><?php echo icon( 'check' ); ?><span>Record sale</span></button>
				</div>
			</form>
		<?php endif; ?>
	</div>
</section>

<section class="card">
	<header class="card-head">
		<h2><?php echo icon( 'chart' ); ?> Recent sales</h2>
		<?php if ( $sales ) : ?><span class="badge"><?php echo e( count( $sales ) ); ?></span><?php endif; ?>
	</header>
	<?php if ( ! $sales ) : ?>
		<?php echo empty_state( 'chart', 'No sales recorded yet', 'Sales you record above will be listed here.' ); ?>
	<?php else : ?>
		<div class="table-wrap">
			<table class="table">
				<thead><tr><th>Date</th><th>Realtor</th><th>Property</th><th class="num">Amount</th><th>Premium-star</th><th class="num">Commission</th></tr></thead>
				<tbody>
				<?php foreach ( $sales as $sale ) : ?>
					<tr>
						<td><?php echo e( format_date( $sale['sold_on'] ) ); ?></td>
						<td class="strong"><?php echo e( $names[ $sale['seller_id'] ] ?? '-' ); ?></td>
						<td><?php echo e( ucfirst( $sale['property_type'] ) . ( '' !== $sale['description'] ? ': ' . $sale['description'] : '' ) ); ?></td>
						<td class="num"><?php echo e( money( $sale['amount'] ) ); ?></td>
						<td><?php echo e( $sale['beneficiary_id'] ? ( $names[ $sale['beneficiary_id'] ] ?? '-' ) : '-' ); ?></td>
						<td class="num"><?php echo null !== $sale['commission'] ? '<span class="amount-up">' . e( money( $sale['commission'] ) ) . '</span>' : '-'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
