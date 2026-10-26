<?php defined( 'PORTAL' ) || exit; ?>
<?php
$titles = array( 1 => 'First Generation', 2 => 'Second Generation', 3 => 'Third Generation' );
shell_start( 'My premium-lines', $user, 'premium-lines' );
?>

<?php foreach ( $titles as $level => $title ) : ?>
	<section class="hrp-card">
		<h3><?php echo e( $title ); ?> (<?php echo e( count( $levels[ $level ] ) ); ?>)</h3>
		<?php if ( ! $levels[ $level ] ) : ?>
			<p class="hrp-muted">Nobody at this generation yet.</p>
		<?php else : ?>
			<div class="hrp-scroll">
				<table class="hrp-table">
					<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>CID</th><th>Joined</th></tr></thead>
					<tbody>
					<?php foreach ( $levels[ $level ] as $member ) : ?>
						<tr>
							<td><?php echo e( $member['full_name'] ); ?></td>
							<td><?php echo e( $member['email'] ); ?></td>
							<td><?php echo e( $member['phone'] ); ?></td>
							<td><?php echo e( $member['ref_code'] ); ?></td>
							<td><?php echo e( format_date( $member['created_at'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
<?php endforeach; ?>

<?php shell_end(); ?>
