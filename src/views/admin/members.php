<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Members', current_user(), 'admin/members' ); ?>

<section class="hrp-card">
	<?php if ( ! $realtors ) : ?>
		<p class="hrp-muted">No realtors registered yet. Share <strong><?php echo e( absolute_url( 'register' ) ); ?></strong> to sign people up.</p>
	<?php else : ?>
		<p class="hrp-muted"><?php echo e( count( $realtors ) ); ?> registered</p>
		<div class="hrp-scroll">
			<table class="hrp-table hrp-table-wide">
				<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>CID</th><th>Premium-star</th><th>Bank</th><th>Joined</th></tr></thead>
				<tbody>
				<?php foreach ( $realtors as $member ) : ?>
					<tr>
						<td><?php echo e( $member['full_name'] ); ?></td>
						<td><?php echo e( $member['email'] ); ?></td>
						<td><?php echo e( $member['phone'] ); ?></td>
						<td><?php echo e( $member['ref_code'] ); ?></td>
						<td><?php echo e( $member['premium_star_id'] ? ( $names[ $member['premium_star_id'] ] ?? '-' ) : '-' ); ?></td>
						<td><?php echo e( trim( $member['bank_name'] . ' ' . $member['account_number'] . ( $member['account_name'] ? ' (' . $member['account_name'] . ')' : '' ) ) ); ?></td>
						<td><?php echo e( format_date( $member['created_at'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
