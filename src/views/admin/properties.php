<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Properties', current_user(), 'admin/properties', 'The listings and prices realtors see on their dashboard.' ); ?>

<section class="card">
	<header class="card-head"><h2><?php echo icon( $editing ? 'building' : 'plus' ); ?> <?php echo $editing ? 'Edit property' : 'Add a property'; ?></h2></header>
	<div class="card-body">
		<form method="post" action="<?php echo e( url( 'admin/properties' ) ); ?>" class="form">
			<?php echo csrf_field(); ?>
			<input type="hidden" name="id" value="<?php echo e( $editing['id'] ?? 0 ); ?>">
			<div class="form-grid">
				<label class="field"><span>Name</span><input class="input" type="text" name="name" maxlength="190" value="<?php echo e( $editing['name'] ?? '' ); ?>" required></label>
				<label class="field"><span>Location</span><input class="input" type="text" name="location" maxlength="190" value="<?php echo e( $editing['location'] ?? '' ); ?>"></label>
				<label class="field"><span>Price (<?php echo e( config( 'currency' ) ); ?>)</span><input class="input" type="number" step="0.01" min="0" name="price" value="<?php echo e( $editing['price'] ?? '' ); ?>" placeholder="Leave blank to show a dash"></label>
				<label class="field"><span>Status</span>
					<select class="input" name="status">
						<?php foreach ( property_statuses() as $status ) : ?>
							<option <?php echo ( $editing['status'] ?? 'Available' ) === $status ? 'selected' : ''; ?>><?php echo e( $status ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="field field-wide"><span>Link to the listing on the website (optional)</span><input class="input" type="url" name="url" maxlength="500" value="<?php echo e( $editing['url'] ?? '' ); ?>" placeholder="https://hypewindsproperties.com/properties/..."></label>
			</div>
			<div class="form-foot">
				<?php if ( $editing ) : ?>
					<a class="btn btn-ghost" href="<?php echo e( url( 'admin/properties' ) ); ?>">Cancel</a>
				<?php else : ?><span></span><?php endif; ?>
				<button class="btn btn-primary" type="submit"><?php echo icon( 'check' ); ?><span><?php echo $editing ? 'Save changes' : 'Add property'; ?></span></button>
			</div>
		</form>
	</div>
</section>

<section class="card">
	<header class="card-head">
		<h2><?php echo icon( 'building' ); ?> Listed on the realtor dashboard</h2>
		<?php if ( $properties ) : ?><span class="badge"><?php echo e( count( $properties ) ); ?></span><?php endif; ?>
	</header>
	<?php if ( ! $properties ) : ?>
		<?php echo empty_state( 'building', 'No properties yet', 'Realtors see an empty list until you add one above.' ); ?>
	<?php else : ?>
		<div class="table-wrap">
			<table class="table">
				<thead><tr><th>Property</th><th>Location</th><th>Status</th><th class="num">Price</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $properties as $property ) : ?>
					<tr>
						<td class="strong"><?php echo e( $property['name'] ); ?></td>
						<td><?php echo e( '' !== $property['location'] ? $property['location'] : '-' ); ?></td>
						<td><span class="pill<?php echo 'Sold out' === $property['status'] ? ' pill-muted' : ( 'Selling fast' === $property['status'] ? ' pill-warn' : '' ); ?>"><?php echo e( $property['status'] ); ?></span></td>
						<td class="num"><?php echo null === $property['price'] ? '&mdash;' : e( money( $property['price'] ) ); ?></td>
						<td class="actions">
							<a class="btn btn-ghost btn-sm" href="<?php echo e( url( 'admin/properties', array( 'edit' => $property['id'] ) ) ); ?>">Edit</a>
							<form method="post" action="<?php echo e( url( 'admin/properties' ) ); ?>" data-confirm="Remove <?php echo e( $property['name'] ); ?> from the list?">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="id" value="<?php echo e( $property['id'] ); ?>">
								<input type="hidden" name="do" value="delete">
								<button type="submit" class="btn btn-danger btn-sm">Remove</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
