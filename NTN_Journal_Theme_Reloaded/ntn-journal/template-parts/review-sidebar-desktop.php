<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Template part per la Sidebar IGDB Sticky su PC (Dati IGDB + Valutazione Finale)
 *
 * Fase 5 del restyling (v3.7.0): scheda del gioco in cornice da 1 pixel, copertina senza velo,
 * dati tecnici a celle, valutazione con i cinque LED e pulsante IGDB.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Recensione, colonna di destra).
 *
 * Con due o più giochi la scheda diventa un carosello, una pagina per gioco in ordine alfabetico.
 * Con un gioco solo o senza giochi resta la scheda classica.
 */

$post_id = get_the_ID();
$rating  = ntn_get_post_rating( $post_id );

$final_rating = 0;
$rating_label = '';

if ( ! empty( $rating ) ) {
	// Dati recensione originale per il voto
	$final_rating = max( 1, min( 5, intval( $rating ) ) );
	$rating_label = function_exists( 'ntn_get_rating_label' ) ? ntn_get_rating_label( $final_rating ) : (string) $final_rating;
}

$games      = ntn_get_post_games( $post_id );
$game_count = count( $games );

$review_source_meta = get_post_meta( $post_id, 'ntn_review_source', true );

// Link del giudizio al suo tag
$rating_link = '';
if ( $rating_label ) {
	$rating_slug = sanitize_title( $rating_label );
	if ( in_array( $rating_slug, array( 'bocciato', 'consigliato-con-riserva', 'consigliato', 'must-play' ), true ) ) {
		$tag_obj = get_term_by( 'slug', $rating_slug, 'post_tag' );
		if ( $tag_obj && ! is_wp_error( $tag_obj ) && $tag_obj instanceof WP_Term ) {
			$tag_link    = get_term_link( $tag_obj );
			$rating_link = ( ! empty( $tag_link ) && ! is_wp_error( $tag_link ) && is_string( $tag_link ) ) ? $tag_link : home_url( '/tag/' . $rating_slug . '/' );
		} else {
			$rating_link = home_url( '/tag/' . $rating_slug . '/' );
		}
	}
}

// Copertina del gioco
$render_cover = static function ( $game, $lazy = true ) {
	if ( ! $game['cover'] ) {
		return;
	}
	?>
	<div class="ntn-gcard-cover">
		<img src="<?php echo esc_url( $game['cover'] ); ?>" alt="<?php echo esc_attr( $game['title'] ); ?>" class="w-full h-full object-cover" width="1280" height="720"<?php echo $lazy ? ' loading="lazy"' : ''; ?> />
	</div>
	<?php
};

// Nome del gioco, con il link alla sua pagina
$render_name = static function ( $game ) {
	$display_title = $game['title'] ? $game['title'] : get_the_title();
	?>
	<h3 class="ntn-gcard-name">
		<?php if ( $game['hub_url'] ) : ?>
			<a href="<?php echo esc_url( $game['hub_url'] ); ?>"><?php echo esc_html( $display_title ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $display_title ); ?>
		<?php endif; ?>
	</h3>
	<?php
};

// Dati IGDB
$render_kv = static function ( $game, $review_source ) {
	$developer = $game['developer'];
	$publisher = $game['publisher'];
	$platforms = $game['platforms'];
	// v4.14.0: "PC (Microsoft Windows)" si legge "PC", come nelle recensioni e negli Hub
	if ( $platforms && function_exists( 'ntn_core_schema_platform_names' ) ) {
		$platforms = ntn_core_schema_platform_names( array_filter( is_array( $platforms ) ? $platforms : explode( ',', (string) $platforms ), 'is_scalar' ) );
	}
	$release = $game['release'];
	$genres  = $game['genres'];
	$engine  = $game['engine'];

	// Data di uscita: solo le parti note (v3.21.0), es. "23/10/2026", "Q3 2027", "2028"
	$release_text = $release ? ntn_journal_release_label( $release, $game['release_precision'] ) : '';

	if ( ! ( $developer || $publisher || $platforms || $release || $genres || $engine || ! empty( $review_source ) ) ) {
		return;
	}
	?>
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
		<?php if ( ! empty( $review_source ) ) : ?>
			<div class="is-wide"><dt>Origine</dt><dd><?php echo esc_html( is_scalar( $review_source ) ? (string) $review_source : '' ); ?></dd></div>
		<?php endif; ?>
	</dl>
	<?php
};

// Valutazione finale dell'articolo
$render_score = static function () use ( $rating, $rating_link, $rating_label, $final_rating ) {
	if ( empty( $rating ) ) {
		return;
	}
	?>
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
	<?php
};

// Pulsante verso la pagina del gioco su IGDB
$render_igdb_button = static function ( $game ) {
	$igdb_url = $game['igdb_url'];
	if ( ! empty( $igdb_url ) && is_scalar( $igdb_url ) ) :
		?>
		<a href="<?php echo esc_url( (string) $igdb_url ); ?>" target="_blank" rel="noopener noreferrer nofollow" class="ntn-igdb-btn">Vedi su IGDB.com</a>
		<?php
	endif;
};

if ( $game_count > 1 ) :
	?>

<div class="ntn-gcard ntn-gcard--multi pointer-events-auto" data-ntn-gcarousel aria-roledescription="carosello" aria-label="Giochi dell'articolo">

	<div class="ntn-gcard-h">
		<span>// Scheda gioco <span class="ntn-gcard-src">· IGDB</span></span>
		<span class="ntn-gcard-head-r">
			<span class="ntn-gcard-count"><?php echo esc_html( sprintf( '01 / %02d', $game_count ) ); ?></span>
			<span class="ntn-gnav">
				<button type="button" data-step="-1" aria-label="Gioco precedente" disabled>←</button>
				<button type="button" data-step="1" aria-label="Gioco successivo">→</button>
			</span>
		</span>
	</div>

	<div class="ntn-gtrack">
		<?php foreach ( $games as $slide_index => $game ) : ?>
			<?php $is_reviewed = ! empty( $game['is_current_review'] ); ?>
			<div class="ntn-gslide" role="group" aria-roledescription="gioco" aria-label="<?php echo esc_attr( sprintf( '%d di %d: %s', $slide_index + 1, $game_count, $game['title'] ) ); ?>">

				<?php $render_cover( $game, $slide_index > 0 ); ?>

				<div class="ntn-gpager">
					<span class="dots">
						<?php foreach ( $games as $dot_index => $dot_game ) : ?>
							<button type="button" data-i="<?php echo esc_attr( (string) $dot_index ); ?>" aria-label="<?php echo esc_attr( $dot_game['title'] ); ?>" aria-current="<?php echo $dot_index === $slide_index ? 'true' : 'false'; ?>"></button>
						<?php endforeach; ?>
					</span>
					<span class="lbl">Scorri</span>
				</div>

				<?php
				$render_name( $game );
				$render_kv( $game, $is_reviewed ? $review_source_meta : '' );
				if ( $is_reviewed ) {
					$render_score();
				} else {
					ntn_journal_game_review_row( $game, 'ntn-greview-mini' );
				}
				$render_igdb_button( $game );
				?>

			</div>
		<?php endforeach; ?>
	</div>

</div>

	<?php
else :
	if ( 1 === $game_count ) {
		// Un gioco solo: i dati scritti sull'articolo restano prioritari, come sempre
		$game = ntn_apply_post_meta_to_game( $games[0], $post_id );
	} else {
		// Nessun gioco: vecchi articoli e recensioni con il solo voto
		$game = array(
			'title'             => ntn_get_igdb_data( $post_id, '_ntn_game_title' ),
			'cover'             => ntn_get_igdb_data( $post_id, '_ntn_game_cover' ),
			'developer'         => ntn_get_igdb_data( $post_id, '_ntn_game_developer' ),
			'publisher'         => ntn_get_igdb_data( $post_id, '_ntn_game_publisher' ),
			'platforms'         => ntn_get_igdb_data( $post_id, '_ntn_game_platforms' ),
			'release'           => ntn_get_igdb_data( $post_id, '_ntn_game_release' ),
			'release_precision' => ntn_get_igdb_data( $post_id, '_ntn_game_release_precision' ),
			'genres'            => ntn_get_igdb_data( $post_id, '_ntn_game_genres' ),
			'engine'            => ntn_get_igdb_data( $post_id, '_ntn_game_engine' ),
			'igdb_url'          => ntn_get_igdb_data( $post_id, '_ntn_game_url' ),
			'hub_url'           => ntn_get_game_hub_url( $post_id ),
		);
	}
	?>

<div class="ntn-gcard pointer-events-auto">

	<div class="ntn-gcard-h">
		<span>// Scheda del videogioco</span>
		<span class="ntn-gcard-src">IGDB</span>
	</div>

	<?php
	$render_cover( $game );
	$render_name( $game );
	$render_kv( $game, $review_source_meta );
	$render_score();
	// Se l'articolo ha un voto è già lui la recensione del gioco: la riga servirebbe solo a smentirlo
	if ( 1 === $game_count && empty( $rating ) && empty( $game['is_current_review'] ) ) {
		ntn_journal_game_review_row( $game, 'ntn-greview-mini' );
	}
	$render_igdb_button( $game );
	?>

</div>

	<?php
endif;
