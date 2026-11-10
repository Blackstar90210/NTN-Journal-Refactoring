<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Intestazione degli archivi sulla fascia a matrice di punti (fase 6, v3.8.0).
 *
 * Argomenti:
 * - 'label'       etichetta sopra il titolo (es. "Categoria", "Motore grafico", "Ricerca")
 * - 'title'       titolo dell'archivio, senza il prefisso automatico di WordPress
 * - 'description' testo di presentazione (facoltativo)
 * - 'count'       numero di articoli (facoltativo)
 * - 'unit'        array( singolare, plurale ) per il contatore (facoltativo, v3.9.0)
 *
 * @package NTN_Journal
 */

$ntn_ah_label = isset( $args['label'] ) ? (string) $args['label'] : 'Archivio';
$ntn_ah_title = isset( $args['title'] ) ? (string) $args['title'] : '';
$ntn_ah_desc  = isset( $args['description'] ) ? wp_strip_all_tags( (string) $args['description'] ) : '';
$ntn_ah_count = isset( $args['count'] ) ? (int) $args['count'] : 0;
$ntn_ah_unit  = ( isset( $args['unit'] ) && is_array( $args['unit'] ) && 2 === count( $args['unit'] ) ) ? array_values( array_map( 'strval', $args['unit'] ) ) : array( 'Articolo', 'Articoli' );
?>
<header class="ntn-band ntn-arch-band">
	<div class="ntn-wrap">
		<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
			<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
		<?php endif; ?>

		<div class="ntn-arch-head">
			<div class="flex flex-wrap gap-2">
				<span class="ntn-chip"><?php echo esc_html( $ntn_ah_label ); ?></span>
			</div>
			<h1 class="ntn-arch-title"><?php echo esc_html( $ntn_ah_title ); ?></h1>
			<?php if ( '' !== trim( $ntn_ah_desc ) ) : ?>
				<p class="ntn-arch-desc"><?php echo esc_html( $ntn_ah_desc ); ?></p>
			<?php endif; ?>
			<?php if ( $ntn_ah_count ) : ?>
				<div class="ntn-gcounts">
					<span><b><?php echo esc_html( number_format_i18n( $ntn_ah_count ) ); ?></b><?php echo esc_html( 1 === $ntn_ah_count ? $ntn_ah_unit[0] : $ntn_ah_unit[1] ); ?></span>
					<?php if ( get_query_var( 'paged' ) > 1 ) : ?>
						<span><b><?php echo esc_html( (int) get_query_var( 'paged' ) ); ?></b>Pagina</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</header>
