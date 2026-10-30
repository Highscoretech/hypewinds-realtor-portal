<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Properties', current_user(), 'admin/properties', 'The listings and prices realtors see on their dashboard.' ); ?>

<?php
// Read-only on purpose. Listings belong to the website, and are added there,
// so that a property is entered once and cannot drift between two lists.
?>
<section class="card">
	<header class="card-head">
		<h2><?php echo icon( 'building' ); ?> Listed on the realtor dashboard</h2>
		<?php if ( $properties ) : ?><span class="badge"><?php echo e( count( $properties ) ); ?></span><?php endif; ?>
	</header>
	<div class="card-body">
		<p class="muted">
			These come from the website's own listings. To add, edit or remove one, use
			<a href="<?php echo e( config( 'site_url' ) ); ?>/my-account" target="_blank" rel="noopener">Add Property on the website</a>
			&mdash; whatever is published there appears here.
		</p>
	</div>
	<?php if ( ! $properties ) : ?>
		<?php echo empty_state( 'building', 'No properties yet', 'Publish a listing on the website and it will appear here.' ); ?>
	<?php else : ?>
		<div class="table-wrap">
			<table class="table">
				<thead><tr><th>Property</th><th>Location</th><th>Status</th><th class="num">Price</th></tr></thead>
				<tbody>
				<?php foreach ( $properties as $property ) : ?>
					<tr>
						<td class="strong">
							<?php if ( ! empty( $property['url'] ) ) : ?>
								<a href="<?php echo e( $property['url'] ); ?>" target="_blank" rel="noopener"><?php echo e( $property['name'] ); ?></a>
							<?php else : ?>
								<?php echo e( $property['name'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo e( '' !== $property['location'] ? $property['location'] : '-' ); ?></td>
						<td><span class="pill<?php echo 'Sold out' === $property['status'] ? ' pill-muted' : ( 'Selling fast' === $property['status'] ? ' pill-warn' : '' ); ?>"><?php echo e( $property['status'] ); ?></span></td>
						<td class="num"><?php echo null === $property['price'] ? '&mdash;' : e( money( $property['price'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
