<?php
/**
 * Scheda Immagini (v4.24.0): gli strumenti per le immagini del sito, prima nella scheda Extra.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="card ntn-images-tab" style="max-width: 100%; margin-top: 0;">
	<style>.ntn-images-tab > hr:first-of-type { display: none; }</style>
	<?php
	// v4.21.0: riparazione delle miniature dei PNG trasparenti
	if ( function_exists( 'ntn_core_png_repair_render_box' ) ) {
		ntn_core_png_repair_render_box();
	}
	// v4.23.0: rapporto di sola lettura sulle immagini
	if ( function_exists( 'ntn_core_image_report_render_box' ) ) {
		ntn_core_image_report_render_box();
	}
	?>
</div>
