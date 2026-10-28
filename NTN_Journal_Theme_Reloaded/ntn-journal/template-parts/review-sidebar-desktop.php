<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template part per la Sidebar IGDB Sticky su PC (Dati IGDB + Valutazione Finale)
 *
 * Fase 5 del restyling (v3.7.0): scheda del gioco in cornice da 1 pixel, copertina senza velo,
 * dati tecnici a celle, valutazione con i cinque LED e pulsante IGDB.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Recensione, colonna di destra).
 */

$post_id = get_the_ID();
$rating = get_post_rating( $post_id );

$final_rating = 0;
$rating_label = '';

if ( ! empty( $rating ) ) {
	// Dati recensione originale per il voto
	$final_rating = max( 1, min( 5, intval( $rating ) ) );
	$rating_label = function_exists( 'get_rating_label' ) ? get_rating_label( $final_rating ) : (string) $final_rating;
}

// Dati IGDB
$game_title = ntn_get_igdb_data( $post_id, '_ntn_game_title' );
$cover      = ntn_get_igdb_data( $post_id, '_ntn_game_cover' );
$developer  = ntn_get_igdb_data( $post_id, '_ntn_game_developer' );
$publisher  = ntn_get_igdb_data( $post_id, '_ntn_game_publisher' );
$platforms  = ntn_get_igdb_data( $post_id, '_ntn_game_platforms' );
$release    = ntn_get_igdb_data( $post_id, '_ntn_game_release' );
$genres     = ntn_get_igdb_data( $post_id, '_ntn_game_genres' );
$engine     = ntn_get_igdb_data( $post_id, '_ntn_game_engine' );
$igdb_url   = ntn_get_igdb_data( $post_id, '_ntn_game_url' );
$video      = ntn_get_igdb_data( $post_id, '_ntn_game_video' );
$screenshots= ntn_get_igdb_data( $post_id, '_ntn_game_screenshots' );
$hub_url    = ntn_get_game_hub_url( $post_id );

$review_source_meta = get_post_meta( $post_id, 'ntn_review_source', true );

$display_title = $game_title ? $game_title : get_the_title();

// Data di uscita: solo le parti note (v3.21.0), es. "23/10/2026", "Q3 2027", "2028"
$release_text = $release ? ntn_journal_release_label( $release, ntn_get_igdb_data( $post_id, '_ntn_game_release_precision' ) ) : '';

// Link del giudizio al suo tag
$rating_link = '';
if ( $rating_label ) {
	$rating_slug = sanitize_title( $rating_label );
	if ( in_array( $rating_slug, array( 'bocciato', 'consigliato-con-riserva', 'consigliato', 'must-play' ) ) ) {
		$tag_obj = get_term_by( 'slug', $rating_slug, 'post_tag' );
		if ( $tag_obj && ! is_wp_error( $tag_obj ) && $tag_obj instanceof WP_Term ) {
			$tag_link = get_term_link( $tag_obj );
			$rating_link = ( ! empty( $tag_link ) && ! is_wp_error( $tag_link ) && is_string( $tag_link ) ) ? $tag_link : home_url( '/tag/' . $rating_slug . '/' );
		} else {
			$rating_link = home_url( '/tag/' . $rating_slug . '/' );
		}
	}
}
?>

<div class="ntn-gcard pointer-events-auto">

	<div class="ntn-gcard-h">
		<span>// Scheda del videogioco</span>
		<span class="ntn-gcard-src">IGDB</span>
	</div>

	<?php if ( $cover ) : ?>
		<div class="ntn-gcard-cover">
			<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $game_title ); ?>" class="w-full h-full object-cover" width="1280" height="720" loading="lazy" />
		</div>
	<?php endif; ?>

	<h3 class="ntn-gcard-name">
		<?php if ( $hub_url ) : ?>
			<a href="<?php echo esc_url( $hub_url ); ?>"><?php echo esc_html( $display_title ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $display_title ); ?>
		<?php endif; ?>
	</h3>

	<!-- Dati IGDB -->
	<?php if ( $developer || $publisher || $platforms || $release || $genres || $engine || ! empty( $review_source_meta ) ) : ?>
	<dl class="ntn-gcard-kv">
		<?php if ( $developer ) : ?>
			<div><dt>Sviluppatore</dt><dd class="is-link"><?php echo ntn_link_companies_to_tags( $developer ); // phpcs:ignore WordPress.Security.EscapeOutput -- link già sanificati dalla funzione. ?></dd></div>
		<?php endif; ?>
		<?php if ( $publisher ) : ?>
			<div><dt>Editore</dt><dd class="is-link"><?php echo ntn_link_companies_to_tags( $publisher ); // phpcs:ignore WordPress.Security.EscapeOutput -- link già sanificati dalla funzione. ?></dd></div>
		<?php endif; ?>
		<?php if ( $release_text ) : ?>
			<div><dt>Uscita</dt><dd><?php echo esc_html( $release_text ); ?></dd></div>
		<?php endif; ?>
		<?php if ( $genres ) : ?>
			<div><dt>Genere</dt><dd><?php echo esc_html( is_array( $genres ) ? implode( ', ', array_filter( array_map( 'trim', $genres ) ) ) : (string) $genres ); ?></dd></div>
		<?php endif; ?>
		<?php if ( $engine ) : ?>
			<div><dt>Motore grafico</dt><dd class="is-link"><?php echo ntn_link_engines_to_terms( $engine, 'hover:underline' ); // phpcs:ignore WordPress.Security.EscapeOutput -- link già sanificati dalla funzione. ?></dd></div>
		<?php endif; ?>
		<?php if ( $platforms ) : ?>
			<div class="is-wide"><dt>Piattaforme</dt><dd><?php echo esc_html( is_array( $platforms ) ? implode( ', ', array_filter( array_map( 'trim', $platforms ) ) ) : (string) $platforms ); ?></dd></div>
		<?php endif; ?>
		<?php if ( ! empty( $review_source_meta ) ) : ?>
			<div class="is-wide"><dt>Origine</dt><dd><?php echo esc_html( is_scalar( $review_source_meta ) ? (string) $review_source_meta : '' ); ?></dd></div>
		<?php endif; ?>
	</dl>
	<?php endif; ?>

	<?php if ( ! empty( $rating ) ) : ?>
	<!-- Valutazione finale -->
	<div class="ntn-gcard-score">
		<span class="ntn-rbox-k">// Valutazione finale</span>
		<?php if ( ! empty( $rating_link ) ) : ?>
			<a href="<?php echo esc_url( $rating_link ); ?>" class="ntn-gcard-word">
				<span><?php echo esc_html( $rating_label ); ?></span>
				<span class="ntn-verdict-dot" aria-hidden="true"></span>
			</a>
		<?php else : ?>
			<span class="ntn-gcard-word"><?php echo esc_html( $rating_label ); ?></span>
		<?php endif; ?>
		<div class="ntn-leds" role="img" aria-label="<?php echo esc_attr( sprintf( 'Voto %d su 5', $final_rating ) ); ?>">
			<?php
			for ( $i = 1; $i <= 5; $i++ ) {
				$led_class = 'ntn-led';
				if ( $i <= $final_rating ) {
					$led_class .= ' is-on';
				}
				if ( $i === $final_rating ) {
					$led_class .= ' is-last';
				}
				echo '<i class="' . esc_attr( $led_class ) . '"></i>';
			}
			?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $igdb_url ) && is_scalar( $igdb_url ) ) : ?>
		<a href="<?php echo esc_url( (string) $igdb_url ); ?>" target="_blank" rel="noopener noreferrer nofollow" class="ntn-igdb-btn">Vedi su IGDB.com</a>
	<?php endif; ?>

</div>
