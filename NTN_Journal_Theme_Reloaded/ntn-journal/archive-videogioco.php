<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * L'Hub Centrale / Catalogo di tutti i Videogiochi.
 *
 * Fase 7 del restyling (v3.9.0): intestazione comune sulla fascia e schede con copertina
 * in cornice. Il tema child usa la sua versione con filtri e scorrimento infinito.
 *
 * @package NTN_Journal
 */

get_header();

global $wp_query;
get_template_part(
	'template-parts/archive-head',
	null,
	array(
		'label'       => 'Catalogo',
		'title'       => 'Videogiochi',
		'description' => 'Esplora il nostro hub completo dei videogiochi trattati. Seleziona un gioco per accedere a tutte le recensioni, gli articoli e i contenuti ad esso dedicati.',
		'count'       => isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0,
		'unit'        => array( 'Gioco in catalogo', 'Giochi in catalogo' ),
	)
);
?>

<main id="primary" class="site-main ntn-main">
<div class="ntn-wrap ntn-arch-body">

	<?php if ( have_posts() ) : ?>
		<div class="ntn-games">
			<?php while ( have_posts() ) : the_post(); 
				$post_id   = get_the_ID();
				$igdb_info = function_exists( 'ntn_get_hub_igdb_info' ) ? ntn_get_hub_igdb_info( $post_id ) : array();

				// 1. Priorità Cover IGDB (post_meta, term_meta associato, term_meta da slug/titolo)
				$cover = ! empty( $igdb_info['cover'] ) ? $igdb_info['cover'] : '';
				if ( empty( $cover ) && function_exists( 'ntn_get_igdb_data' ) ) {
					$cover = ntn_get_igdb_data( $post_id, '_ntn_game_cover' );
				}
				if ( empty( $cover ) ) {
					$cover = get_post_meta( $post_id, '_ntn_game_cover', true );
				}
				if ( ! empty( $cover ) && function_exists( 'ntn_normalize_game_cover_url' ) ) {
					$cover = ntn_normalize_game_cover_url( $cover );
				}

				// 2. Fallback all'immagine in evidenza nativa di WordPress
				if ( empty( $cover ) && has_post_thumbnail( $post_id ) ) {
					$cover = get_the_post_thumbnail_url( $post_id, 'medium_large' );
				}

				// 3. Normalizzazione URL e protocollo HTTPS / risoluzione HD
				if ( ! empty( $cover ) ) {
					if ( function_exists( 'ntn_normalize_game_cover_url' ) ) {
						$cover = ntn_normalize_game_cover_url( $cover );
					} else {
						if ( ! is_array( $cover ) && ( ! is_object( $cover ) || method_exists( $cover, '__toString' ) ) ) {
							$cover = trim( (string) $cover );
							if ( strpos( $cover, '//' ) === 0 ) {
								$cover = 'https:' . $cover;
							} elseif ( strpos( $cover, 'http://' ) === 0 && strpos( $cover, 'images.igdb.com' ) !== false ) {
								$cover = 'https://' . substr( $cover, 7 );
							} elseif ( strpos( $cover, 'http' ) !== 0 && preg_match( '/^[a-zA-Z0-9_-]+(\.(jpg|png|jpeg|webp))?$/i', $cover ) ) {
								$clean_id = preg_replace( '/\.(jpg|png|jpeg|webp)$/i', '', $cover );
								$cover    = 'https://images.igdb.com/igdb/image/upload/t_1080p/' . $clean_id . '.jpg';
							}
							if ( strpos( $cover, 'images.igdb.com' ) !== false ) {
								if ( preg_match( '/\/t_[a-z0-9_]+\//', $cover ) ) {
									$cover = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_1080p/', $cover );
								} elseif ( strpos( $cover, '/igdb/image/upload/' ) !== false && strpos( $cover, '/igdb/image/upload/t_' ) === false ) {
									$cover = str_replace( '/igdb/image/upload/', '/igdb/image/upload/t_1080p/', $cover );
								}
							}
						} else {
							$cover = '';
						}
					}
				}

				$release = ! empty( $igdb_info['release'] ) ? $igdb_info['release'] : ( function_exists( 'ntn_get_igdb_data' ) ? ntn_get_igdb_data( $post_id, '_ntn_game_release' ) : get_post_meta( $post_id, '_ntn_game_release', true ) );
			?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'ntn-game-card group' ); ?>>
					<a href="<?php the_permalink(); ?>" class="absolute inset-0 z-10">
						<span class="sr-only"><?php the_title(); ?></span>
					</a>

					<div class="ntn-game-cover">
					<?php if ( $cover ) : ?>
						<img src="<?php echo esc_url( $cover ); ?>" width="400" height="533" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover">
					<?php else : ?>
						<div class="ntn-game-nocover">NO COVER</div>
					<?php endif; ?>
					</div>

					<div class="ntn-game-info">
						<h2 class="ntn-game-title"><?php the_title(); ?></h2>
						<?php 
							// v3.19.1: data completa solo se IGDB conosce il giorno, altrimenti l'anno
							$rel_year = '';
							if ( ! empty( $release ) && function_exists( 'ntn_journal_game_card_date' ) ) {
								$rel_year = ntn_journal_game_card_date( get_the_ID(), $release );
							} elseif ( ! empty( $release ) ) {
								$rel_clean = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
								if ( is_numeric( $rel_clean ) ) {
									if ( strlen( $rel_clean ) === 4 ) {
										$rel_year = $rel_clean;
									} elseif ( strlen( $rel_clean ) === 13 ) {
										$rel_year = date( 'Y', intval( $rel_clean / 1000 ) );
									} else {
										$rel_year = date( 'Y', intval( $rel_clean ) );
									}
								} elseif ( ! empty( $rel_clean ) ) {
									$rel_time = strtotime( $rel_clean );
									$rel_year = $rel_time ? date( 'Y', $rel_time ) : $rel_clean;
								}
							}
							if ( ! empty( $rel_year ) ) : ?>
							<span class="ntn-game-year"><?php echo esc_html( $rel_year ); ?></span>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="ntn-pager-wrap">
			<?php the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_text' => '&larr; Precedente',
				'next_text' => 'Successivo &rarr;',
			) ); ?>
		</div>

	<?php else : ?>
		<div class="ntn-empty">
			<span>VUOTO</span>
			<p>Nessun videogioco presente nel catalogo.</p>
		</div>
	<?php endif; ?>

</div>
</main>

<?php get_footer(); ?>
