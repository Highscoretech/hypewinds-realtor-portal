<?php defined( 'PORTAL' ) || exit; ?>
<?php shell_start( 'Members', current_user(), 'admin/members', 'Every realtor registered on the portal.' ); ?>

<section class="card">
	<header class="card-head">
		<h2><?php echo icon( 'users' ); ?> Realtors</h2>
		<span class="badge"><?php echo e( count( $realtors ) ); ?></span>
	</header>
	<?php if ( ! $realtors ) : ?>
		<?php echo empty_state( 'users', 'No realtors registered yet', 'Share ' . absolute_url( 'register' ) . ' to sign people up.' ); ?>
	<?php else : ?>
		<div class="table-wrap">
			<table class="table table-wide">
				<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>CID</th><th>Premium-star</th><th>Bank</th><th>Joined</th></tr></thead>
				<tbody>
				<?php foreach ( $realtors as $member ) : ?>
					<tr>
						<td><span class="cell-person"><span class="avatar avatar-sm"><?php echo e( initials( $member['full_name'] ) ); ?></span><?php echo e( $member['full_name'] ); ?></span></td>
						<td><?php echo e( $member['email'] ); ?></td>
						<td><?php echo e( '' !== $member['phone'] ? $member['phone'] : '-' ); ?></td>
						<td class="code"><?php echo e( $member['ref_code'] ); ?></td>
						<td><?php echo e( $member['premium_star_id'] ? ( $names[ $member['premium_star_id'] ] ?? '-' ) : '-' ); ?></td>
						<td>
							<?php if ( '' !== $member['bank_name'] || '' !== $member['account_number'] ) : ?>
								<span class="stack"><span><?php echo e( trim( $member['bank_name'] . ' ' . $member['account_number'] ) ); ?></span>
								<span class="muted"><?php echo e( $member['account_name'] ); ?></span></span>
							<?php else : ?>-<?php endif; ?>
						</td>
						<td><?php echo e( format_date( $member['created_at'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php shell_end(); ?>
