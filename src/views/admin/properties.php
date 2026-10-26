<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Properties', current_user(), 'admin/properties' ); ?>

<section class="hrp-card">
	<h2><?php echo $editing ? 'Edit property' : 'Add a property'; ?></h2>
	<form method="post" action="<?php echo e( url( 'admin/properties' ) ); ?>">
		<?php echo csrf_field(); ?>
		<input type="hidden" name="id" value="<?php echo e( $editing['id'] ?? 0 ); ?>">
		<div class="hrp-grid">
			<label>Name<input type="text" name="name" maxlength="190" value="<?php echo e( $editing['name'] ?? '' ); ?>" required></label>
			<label>Location<input type="text" name="location" maxlength="190" value="<?php echo e( $editing['location'] ?? '' ); ?>"></label>
			<label>Price<input type="number" step="0.01" min="0" name="price" value="<?php echo e( $editing['price'] ?? '' ); ?>" placeholder="Leave blank to show a dash"></label>
			<label>Status
				<select name="status">
					<?php foreach ( property_statuses() as $status ) : ?>
						<option <?php echo ( $editing['status'] ?? 'Available' ) === $status ? 'selected' : ''; ?>><?php echo e( $status ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
		<label>Link to the listing on the website (optional)<input type="url" name="url" maxlength="500" value="<?php echo e( $editing['url'] ?? '' ); ?>" placeholder="https://hypewindsproperties.com/..."></label>
		<p>
			<button class="hrp-button" type="submit"><?php echo $editing ? 'Save changes' : 'Add property'; ?></button>
			<?php if ( $editing ) : ?>
				<a class="hrp-link" href="<?php echo e( url( 'admin/properties' ) ); ?>">Cancel</a>
			<?php endif; ?>
		</p>
	</form>
</section>

<section class="hrp-card">
	<h2>Listed on the realtor dashboard</h2>
	<?php if ( ! $properties ) : ?>
		<p class="hrp-muted">No properties yet. Realtors see an empty list until you add one.</p>
	<?php else : ?>
		<div class="hrp-scroll">
			<table class="hrp-table">
				<thead><tr><th>Property</th><th>Location</th><th>Status</th><th>Price</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $properties as $property ) : ?>
					<tr>
						<td><?php echo e( $property['name'] ); ?></td>
						<td><?php echo e( $property['location'] ); ?></td>
						<td><?php echo e( $property['status'] ); ?></td>
						<td><?php echo null === $property['price'] ? '&mdash;' : e( money( $property['price'] ) ); ?></td>
						<td class="hrp-actions">
							<a href="<?php echo e( url( 'admin/properties', array( 'edit' => $property['id'] ) ) ); ?>">Edit</a>
							<form method="post" action="<?php echo e( url( 'admin/properties' ) ); ?>" data-confirm="Remove <?php echo e( $property['name'] ); ?> from the list?">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="id" value="<?php echo e( $property['id'] ); ?>">
								<input type="hidden" name="do" value="delete">
								<button type="submit" class="hrp-text-button">Remove</button>
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
