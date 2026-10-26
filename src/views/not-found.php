<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Page not found', 'There is nothing at this address.' ); ?>

<p class="hrp-auth-alt"><a href="<?php echo e( url() ); ?>">Go to the realtor portal</a></p>

<?php auth_end(); ?>
