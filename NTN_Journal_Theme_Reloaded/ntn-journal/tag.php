<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying Tag archives.
 * Supporta sia i tag videogioco con Hero Header IGDB che i normali tag di WordPress.
 *
 * @package NTN_Journal
 */

$current_tag = get_queried_object();
$igdb_info   = ( $current_tag && function_exists( 'ntn_get_hub_igdb_info' ) ) ? ntn_get_hub_igdb_info( $current_tag ) : array();

$is_game_tag = ! empty( $igdb_info ) && (
	! empty( $igdb_info['cover'] ) ||
	! empty( $igdb_info['developer'] ) ||
	! empty( $igdb_info['release'] ) ||
	! empty( $igdb_info['genres'] ) ||
	! empty( $igdb_info['platforms'] ) ||
	! empty( $igdb_info['engine'] ) ||
	( $current_tag instanceof WP_Term && ( get_term_meta( $current_tag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $current_tag->term_id, '_ntn_game_title', true ) ) )
);

if ( $is_game_tag ) :
	get_header();

	// Mostra l'Hero Header della Hub Page
	get_template_part(
		'template-parts/hub-header',
		null,
		array(
			'object' => $current_tag,
			'info'   => $igdb_info,
		)
	);
	?>
	<main id="primary" class="site-main ntn-main">
		<div class="ntn-wrap ntn-hub-body">
			<!-- Tutti gli articoli del gioco -->
			<section aria-labelledby="ntn-h-tag-all">
				<div class="ntn-sec-head">
					<h3 id="ntn-h-tag-all" class="ntn-sec-title">
						TUTTI GLI ARTICOLI // <?php echo esc_html( strtoupper( ! empty( $igdb_info['title'] ) ? $igdb_info['title'] : $current_tag->name ) ); ?>
					</h3>
					<span class="ntn-sec-aside">Dal più recente</span>
				</div>

				<?php if ( have_posts() ) : ?>
					<div class="ntn-cells ntn-cells--3">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content' );
						endwhile;
						?>
					</div>

					<div class="ntn-pager-wrap">
						<?php
						the_posts_pagination(
							array(
								'mid_size'  => 2,
								'prev_text' => '&larr; Precedente',
								'next_text' => 'Successivo &rarr;',
							)
						);
						?>
					</div>
				<?php else : ?>
					<div class="ntn-empty">
						<span>Vuoto</span>
						<p>Nessun articolo associato a questo tag.</p>
					</div>
				<?php endif; ?>
			</section>
		</div>
	</main>
	<?php
	get_footer();
elseif ( function_exists( 'ntn_core_get_studio_hub_data' ) && ( $studio = ntn_core_get_studio_hub_data( $current_tag ) ) ) :
	// v3.21.0: il tag di uno studio con giochi nel catalogo diventa la Hub dello studio
	require get_template_directory() . '/studio-hub.php';
else :
	// Comportamento standard da archivio
	require get_template_directory() . '/archive.php';
endif;

