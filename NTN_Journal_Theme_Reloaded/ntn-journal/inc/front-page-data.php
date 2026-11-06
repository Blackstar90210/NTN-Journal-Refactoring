<?php
/**
 * Dati della Home (fase 3 del restyling, v3.4.0).
 *
 * Il tema legge soltanto articoli e meta già esistenti: nessun nuovo dato viene
 * registrato qui. Ogni blocco è salvato in cache per alcuni minuti, come nella
 * Home precedente.
 *
 * @package NTN_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gioco collegato all'articolo: il primo tag che ha i dati IGDB.
 *
 * @param int $post_id ID dell'articolo.
 * @return array{name:string,url:string}|null
 */
function ntn_front_game_entity( $post_id ) {
	$tags = get_the_tags( $post_id );
	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return null;
	}
	foreach ( $tags as $tag ) {
		if ( get_term_meta( $tag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $tag->term_id, '_ntn_game_title', true ) ) {
			$link = get_tag_link( $tag->term_id );
			return array(
				'name' => $tag->name,
				'url'  => is_string( $link ) ? $link : '',
			);
		}
	}
	return null;
}

/**
 * Dati di un articolo per le schede della Home.
 *
 * @param int    $post_id ID dell'articolo.
 * @param string $size    Formato dell'immagine.
 * @return array<string,mixed>
 */
function ntn_front_card_data( $post_id, $size = 'ntn-grid-thumb' ) {
	$primary_cat = function_exists( 'ntn_get_primary_category' ) ? ntn_get_primary_category( $post_id ) : null;
	$minutes     = (int) get_post_meta( $post_id, '_ntn_reading_time', true );
	$rating      = function_exists( 'get_post_rating' ) ? get_post_rating( $post_id ) : null;
	$excerpt     = wp_strip_all_tags( get_the_excerpt( $post_id ) );
	$author_id   = (int) get_post_field( 'post_author', $post_id );

	return array(
		'id'        => (int) $post_id,
		'title'     => get_the_title( $post_id ),
		'permalink' => get_permalink( $post_id ),
		'excerpt'   => $excerpt,
		'cat_name'  => $primary_cat ? $primary_cat->name : '',
		'author'    => $author_id ? get_the_author_meta( 'display_name', $author_id ) : '',
		'date'      => get_the_date( 'd.m.Y', $post_id ),
		'timestamp' => (int) get_post_timestamp( $post_id ),
		'minutes'   => $minutes > 0 ? $minutes : 1,
		'has_thumb' => has_post_thumbnail( $post_id ),
		'img_size'  => $size,
		'img_alt'   => function_exists( 'ntn_get_image_alt' ) ? ntn_get_image_alt( $post_id ) : '',
		'game'      => ntn_front_game_entity( $post_id ),
		'rating'    => $rating ? (int) round( $rating ) : 0,
	);
}

/**
 * Esegue una query e restituisce i dati delle schede.
 *
 * @param array  $args Argomenti di WP_Query.
 * @param string $size Formato dell'immagine.
 * @return array<int,array<string,mixed>>
 */
function ntn_front_query_cards( $args, $size = 'ntn-grid-thumb' ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => true,
		)
	);
	$query = new WP_Query( $args );
	$cards = array();
	foreach ( $query->posts as $post ) {
		$cards[] = ntn_front_card_data( $post->ID, $size );
	}
	return $cards;
}

/**
 * Nome della cache della Home: cambia a ogni versione del tema, così un aggiornamento la rigenera.
 *
 * @return string
 */
function ntn_front_cache_key() {
	return 'ntn_front_data_' . str_replace( '.', '_', (string) wp_get_theme( get_template() )->get( 'Version' ) );
}

/**
 * Blocchi della Home, in cache per 10 minuti.
 *
 * @return array<string,mixed>
 */
function ntn_front_page_data() {
	$cached = get_transient( ntn_front_cache_key() );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	// 1. Apertura: articoli in evidenza (sticky), completati con gli ultimi fino a 4.
	$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
	$lead   = array();
	if ( ! empty( $sticky ) ) {
		$lead = ntn_front_query_cards(
			array(
				'post__in'       => $sticky,
				'posts_per_page' => 4,
				'orderby'        => 'date',
			),
			'large'
		);
	}
	if ( count( $lead ) < 4 ) {
		$lead = array_merge(
			$lead,
			ntn_front_query_cards(
				array(
					'posts_per_page' => 4 - count( $lead ),
					'post__not_in'   => wp_list_pluck( $lead, 'id' ),
				),
				'large'
			)
		);
	}
	$exclude = wp_list_pluck( $lead, 'id' );

	// 2. Ultime notizie: lista compatta.
	$news = ntn_front_query_cards(
		array(
			'category_name'  => 'notizia,notizie',
			'posts_per_page' => 6,
			'post__not_in'   => $exclude,
		)
	);
	$exclude = array_merge( $exclude, wp_list_pluck( $news, 'id' ) );

	// 3. Recensioni.
	$reviews = ntn_front_query_cards(
		array(
			'category_name'  => 'recensione,recensioni',
			'posts_per_page' => 4,
		)
	);
	$exclude = array_merge( $exclude, wp_list_pluck( $reviews, 'id' ) );

	// 4. Editoriali (v3.21.1, prima "Approfondimenti" con anche gli speciali), senza ripetere gli articoli già mostrati sopra.
	$features = ntn_front_query_cards(
		array(
			'category_name'  => 'editoriale,editoriali',
			'posts_per_page' => 3,
			'post__not_in'   => $exclude,
		)
	);

	// 5. Prossime uscite dal catalogo dei videogiochi.
	$releases = array();
	if ( post_type_exists( 'videogioco' ) ) {
		$games = new WP_Query(
			array(
				'post_type'      => 'videogioco',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'no_found_rows'  => true,
				'meta_key'       => '_ntn_game_release',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_ntn_game_release',
						'value'   => wp_date( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);
		foreach ( $games->posts as $game ) {
			$release = (string) get_post_meta( $game->ID, '_ntn_game_release', true );
			$time    = strtotime( $release );
			// v3.21.0: solo le parti note della data ("2026" invece di "31.12.2026"); "a breve" solo con il giorno
			$label   = ntn_journal_release_label( $release, (string) get_post_meta( $game->ID, '_ntn_game_release_precision', true ), 'd.m.Y' );
			$platforms  = (string) get_post_meta( $game->ID, '_ntn_game_platforms', true );
			if ( '' !== $platforms && function_exists( 'ntn_core_schema_platform_names' ) ) {
				// v4.14.1: "PC (Microsoft Windows)" diventa "PC", come negli Hub e nelle schede dei giochi
				$platforms = implode( ', ', ntn_core_schema_platform_names( array_filter( array_map( 'trim', explode( ',', $platforms ) ), 'strlen' ) ) );
			}
			$releases[] = array(
				'title'     => get_the_title( $game ),
				'permalink' => get_permalink( $game ),
				'date'      => $label,
				'soon'      => $time && ( $time - time() ) < 14 * DAY_IN_SECONDS && false !== strpos( $label, '.' ),
				'developer' => (string) get_post_meta( $game->ID, '_ntn_game_developer', true ),
				'platforms' => $platforms,
			);
		}
	}

	// 6. Contatori della riga dell'edizione.
	$today       = getdate( current_time( 'timestamp' ) );
	$today_query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'year'  => $today['year'],
					'month' => $today['mon'],
					'day'   => $today['mday'],
				),
			),
		)
	);
	$review_cat  = get_category_by_slug( 'recensioni' );
	$games_count = post_type_exists( 'videogioco' ) ? (int) wp_count_posts( 'videogioco' )->publish : 0;

	$data = array(
		'lead'     => $lead,
		'news'     => $news,
		'reviews'  => $reviews,
		'features' => $features,
		'releases' => $releases,
		'counts'   => array(
			'today'   => (int) $today_query->found_posts,
			'reviews' => $review_cat ? (int) $review_cat->count : 0,
			'games'   => $games_count,
		),
	);
	set_transient( ntn_front_cache_key(), $data, 10 * MINUTE_IN_SECONDS );
	return $data;
}

/**
 * Svuota la cache della Home quando si pubblica, si modifica o si cestina un articolo o un gioco.
 *
 * @param int $post_id ID del contenuto.
 */
function ntn_front_flush_cache( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( in_array( get_post_type( $post_id ), array( 'post', 'videogioco' ), true ) ) {
		delete_transient( ntn_front_cache_key() );
	}
}
add_action( 'save_post', 'ntn_front_flush_cache' );
add_action( 'trashed_post', 'ntn_front_flush_cache' );

/**
 * Immagine di una scheda, oppure la copertina a punti se manca.
 *
 * @param array  $card  Dati della scheda.
 * @param string $ratio Classe Tailwind del rapporto (es. aspect-video).
 * @param bool   $eager Caricamento prioritario (solo per l'apertura).
 */
function ntn_front_image( $card, $ratio = 'aspect-video', $eager = false ) {
	echo '<div class="ntn-frame ntn-home-img ' . esc_attr( $ratio ) . '">';
	if ( ! empty( $card['has_thumb'] ) ) {
		$attr = array(
			'class' => 'w-full h-full object-cover',
			'alt'   => $card['img_alt'],
		);
		if ( $eager ) {
			$attr['fetchpriority'] = 'high';
			$attr['loading']       = 'eager';
			$attr['class']        .= ' skip-lazy no-lazy';
		} else {
			$attr['loading'] = 'lazy';
		}
		echo get_the_post_thumbnail( $card['id'], $card['img_size'], $attr ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup generato da WordPress.
	} else {
		echo '<span class="ntn-ph block w-full h-full" aria-hidden="true"></span>';
	}
	echo '</div>';
}

/**
 * Segno rosso del gioco collegato, oppure la categoria in grigio.
 *
 * @param array $card Dati della scheda.
 */
function ntn_front_entity( $card ) {
	if ( ! empty( $card['game'] ) ) {
		echo '<span class="ntn-entity"><span class="ntn-dot" aria-hidden="true"></span>// ' . esc_html( $card['game']['name'] ) . '</span>';
	} elseif ( ! empty( $card['cat_name'] ) ) {
		echo '<span>// ' . esc_html( $card['cat_name'] ) . '</span>';
	}
}

/**
 * I cinque LED del voto, con il giudizio.
 *
 * @param int $rating Voto da 1 a 5.
 */
function ntn_front_leds( $rating ) {
	$rating = max( 0, min( 5, (int) $rating ) );
	if ( ! $rating ) {
		return;
	}
	$label = function_exists( 'get_rating_label' ) ? get_rating_label( $rating ) : '';
	echo '<span class="ntn-leds" role="img" aria-label="' . esc_attr( sprintf( 'Voto %d su 5', $rating ) ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$cls = 'ntn-led';
		if ( $i <= $rating ) {
			$cls .= ' is-on';
		}
		if ( $i === $rating ) {
			$cls .= ' is-last';
		}
		echo '<i class="' . esc_attr( $cls ) . '"></i>';
	}
	echo '</span>';
	if ( $label ) {
		echo '<span class="ntn-led-label">' . esc_html( $label ) . '</span>';
	}
}
