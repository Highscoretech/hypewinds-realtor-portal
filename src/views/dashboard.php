<?php defined( 'PORTAL' ) || exit; ?>
<?php
// Nothing here invents content: every figure is read back from what has
// actually been recorded, and an empty section says it is empty.
$link = referral_link( $user['ref_code'] );
shell_start( 'Dashboard', $user, '' );
?>

<div class="hrp-tiles">
	<div class="hrp-tile"><span>Welcome back</span><strong><?php echo e( $user['full_name'] ); ?></strong></div>
	<div class="hrp-tile"><span>Your CID</span><strong><?php echo e( $user['ref_code'] ); ?></strong></div>
	<div class="hrp-tile"><span>Commission earned</span><strong><?php echo e( money( $total ) ); ?></strong></div>
	<div class="hrp-tile"><span>Your premium-lines</span><strong><?php echo e( count( $lines ) ); ?></strong></div>
</div>

<section class="hrp-card">
	<h2>Your referral link</h2>
	<div class="hrp-copy-field">
		<input type="text" class="hrp-input hrp-copy-value" readonly value="<?php echo e( $link ); ?>">
		<button type="button" class="hrp-copy" data-copy="<?php echo e( $link ); ?>" title="Copy referral link" aria-label="Copy referral link"><?php echo copy_icon(); ?></button>
	</div>
	<p class="hrp-muted">
		Anyone who registers with this link becomes your premium-line.
		Your code: <strong class="hrp-code"><?php echo e( $user['ref_code'] ); ?></strong>
		<button type="button" class="hrp-copy hrp-copy-plain" data-copy="<?php echo e( $user['ref_code'] ); ?>" title="Copy your code" aria-label="Copy your code"><?php echo copy_icon(); ?></button>
	</p>
</section>

<section class="hrp-card">
	<h2>Your premium-star</h2>
	<?php if ( $star ) : ?>
		<p><strong><?php echo e( $star['full_name'] ); ?></strong><br><?php echo e( $star['email'] ); ?></p>
	<?php else : ?>
		<p class="hrp-muted">You joined without a referral link, so you have no premium-star.</p>
	<?php endif; ?>
</section>

<section class="hrp-card">
	<h2>Our properties and prices</h2>
	<?php if ( ! $properties ) : ?>
		<p class="hrp-muted">No properties are listed yet.</p>
	<?php else : ?>
		<div class="hrp-scroll">
			<table class="hrp-table">
				<thead><tr><th>Property</th><th>Location</th><th>Status</th><th>Price</th></tr></thead>
				<tbody>
				<?php foreach ( $properties as $property ) : ?>
					<tr>
						<td>
							<?php if ( $property['url'] ) : ?>
								<a href="<?php echo e( $property['url'] ); ?>" target="_blank" rel="noopener"><?php echo e( $property['name'] ); ?></a>
							<?php else : ?>
								<?php echo e( $property['name'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo e( $property['location'] ); ?></td>
						<td><span class="hrp-pill<?php echo 'Sold out' === $property['status'] ? ' hrp-pill-off' : ''; ?>"><?php echo e( $property['status'] ); ?></span></td>
						<td><?php echo null === $property['price'] ? '&mdash;' : e( money( $property['price'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<section class="hrp-card">
	<h2>Your commissions</h2>
	<?php if ( ! $earnings ) : ?>
		<p class="hrp-muted">No commission yet. You earn when a premium-line sells a property.</p>
	<?php else : ?>
		<ul class="hrp-list">
		<?php foreach ( $earnings as $row ) : ?>
			<li>
				<span><?php echo e( $row['seller_name'] ); ?> &middot; <?php echo e( (float) $row['rate_percent'] ); ?>%
					<em class="hrp-muted"><?php echo e( format_date( $row['created_at'] ) ); ?></em></span>
				<strong><?php echo e( money( $row['amount'] ) ); ?></strong>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>

<section class="hrp-card">
	<h2>Notifications</h2>
	<?php if ( ! $notices ) : ?>
		<p class="hrp-muted">Nothing yet.</p>
	<?php else : ?>
		<ul class="hrp-list">
		<?php foreach ( $notices as $note ) : ?>
			<li><span><?php echo e( $note['body'] ); ?></span>
				<em class="hrp-muted"><?php echo e( format_date( $note['created_at'] ) ); ?></em></li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
