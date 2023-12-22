<?php
$iyzicoEnabled           = SettingsRepository::getSetting( 'enable-iyzico-payments' ) == 'true';
$showInFooterAndCheckout = SettingsRepository::getSetting( 'show-in-footer-and-checkout' ) == 'true';

if (!$iyzicoEnabled && !$showInFooterAndCheckout) {
    return;
}
?>

<div class="rise-footer">
	<?php
	if ( $showInFooterAndCheckout ) {
		?>
        <div class="rise-powered-by <?php echo ! $iyzicoEnabled ? 'text-center' : '' ?>">
            <a href="<?php echo esc_url( RISE_PLUGIN_WEBSITE ) ?>" target="_blank">
				<?php _e( 'Booking engine powered by Rise', 'rise-hotel-booking' ); ?>
            </a>
        </div>
		<?php
	}
	if ( $iyzicoEnabled ) {
		?>
        <div class="iyzico-footer-band <?php echo ! $showInFooterAndCheckout ? 'w-100 text-right' : '' ?>">
            <img src="<?php echo esc_url( RISE_LOCATION_URL ) . '/View/FrontEnd/img/iyzico_footer_band.svg' ?>"
                 alt="Iyzico Footer Band" draggable="false">
        </div>
		<?php
	}
	?>
</div>
