<?php
/**
 * NTN Journal Core - Dati dei giochi e degli articoli
 *
 * Dalla v4.11.0 queste funzioni sono nel plugin: prima erano in inc/helpers.php del tema genitore.
 * I nomi non cambiano, quindi i modelli del tema le chiamano come prima. Ogni funzione è protetta
 * da function_exists; il tema tiene solo versioni di riserva vuote (inc/plugin-fallbacks.php) per
 * quando il plugin è spento.
 *
 * Il file si carica solo se il tema NTN Journal è alla 4.10.1 o successiva: vedi
 * ntn_core_can_load_game_data() in ntn-journal-core.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ntn_translate_igdb_genres' ) ) :
/**
 * Traduce i generi IGDB dall'inglese all'italiano.
 */
function ntn_translate_igdb_genres( $genres_string ) {
	if ( empty( $genres_string ) ) {
		return '';
	}

	$dictionary = array(
		'point-and-click'             => 'Punta e Clicca',
		'fighting'                    => 'Picchiaduro',
		'shooter'                     => 'Sparatutto',
		'music'                       => 'Musicale',
		'platform'                    => 'Platform',
		'puzzle'                      => 'Rompicapo',
		'racing'                      => 'Corse',
		'real time strategy (rts)'    => 'Strategia in Tempo Reale (RTS)',
		'rts'                         => 'Strategia in Tempo Reale (RTS)',
		'role-playing (rpg)'          => 'Gioco di Ruolo',
		'rpg'                         => 'Gioco di Ruolo',
		'simulator'                   => 'Simulatore',
		'sport'                       => 'Sport',
		'strategy'                    => 'Strategia',
		'turn-based strategy (tbs)'   => 'Strategia a Turni (TBS)',
		'tbs'                         => 'Strategia a Turni (TBS)',
		'tactical'                    => 'Tattico',
		'hack and slash/beat \'em up' => 'Hack and Slash',
		'hack and slash'              => 'Hack and Slash',
		'beat \'em up'                => 'Picchiaduro a Scorrimento',
		'quiz/trivia'                 => 'Quiz / Trivia',
		'pinball'                     => 'Flipper',
		'adventure'                   => 'Avventura',
		'indie'                       => 'Indie',
		'arcade'                      => 'Arcade',
		'visual novel'                => 'Visual Novel',
		'card & board game'           => 'Gioco di Carte / Da Tavolo',
		'moba'                        => 'MOBA',
		'action'                      => 'Azione',
		'action-adventure'            => 'Azione / Avventura',
		'survival'                    => 'Survival',
		'horror'                      => 'Horror',
		'stealth'                     => 'Stealth',
		'open world'                  => 'Open World'
	);

	if ( is_array( $genres_string ) ) {
		$genres = $genres_string;
	} elseif ( is_object( $genres_string ) && ! method_exists( $genres_string, '__toString' ) ) {
		return '';
	} else {
		$genres = explode( ',', (string) $genres_string );
	}

	$translated = array();

	foreach ( $genres as $genre ) {
		if ( ! is_scalar( $genre ) && ( ! is_object( $genre ) || ! method_exists( $genre, '__toString' ) ) ) {
			continue;
		}
		$genre_clean = trim( (string) $genre );
		if ( empty( $genre_clean ) ) {
			continue;
		}
		$genre_lower = strtolower( $genre_clean );
		if ( array_key_exists( $genre_lower, $dictionary ) ) {
			$translated[] = $dictionary[$genre_lower];
		} else {
			$translated[] = $genre_clean;
		}
	}

	return implode( ', ', $translated );
}
endif;

if ( ! function_exists( 'get_post_rating' ) ) :
/**
 * Register custom fields / helper functions for theme attributes like ratings.
 */
function get_post_rating( $post_id ) {
	$rating = get_post_meta( $post_id, 'rating', true );
	if ( ! empty( $rating ) ) {
		return floatval( $rating );
	}
	
	return null;
}
endif;

/**
 * Helper function to calculate post reading time
 */
if ( ! function_exists( 'ntn_calculate_reading_time' ) ) {
	function ntn_calculate_reading_time( $post_id ) {
		$reading_time = (int) get_post_meta( $post_id, '_ntn_reading_time', true );
		if ( ! $reading_time ) { $reading_time = 1; }
		return $reading_time . ' MIN LETTURA';
	}
}

if ( ! function_exists( 'ntn_get_primary_category' ) ) :
/**
 * Helper to retrieve the primary category of a post (Yoast, Rank Math, or first category)
 */
function ntn_get_primary_category( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	if ( ! $post_id ) {
		return null;
	}

	$cache_key = 'ntn_primary_cat_' . $post_id;
	$cached = function_exists( 'wp_cache_get' ) ? wp_cache_get( $cache_key, 'ntn_journal' ) : false;
	if ( false !== $cached ) {
		return $cached ? $cached : null;
	}

	$categories = get_the_category( $post_id );
	if ( empty( $categories ) ) {
		if ( function_exists( 'wp_cache_set' ) ) {
			wp_cache_set( $cache_key, false, 'ntn_journal', 3600 );
		}
		return null;
	}

	// Controlla le chiavi meta dei principali plugin SEO per la categoria primaria
	$meta_keys = array(
		'_yoast_wpseo_primary_category', // Yoast SEO
		'rank_math_primary_category',    // Rank Math
		'_aioseo_primary_category',      // All in One SEO
		'_seopress_robots_primary_cat',  // SEOPress
		'_primary_term_category'         // The SEO Framework
	);

	$found_cat = null;
	foreach ( $meta_keys as $key ) {
		$primary_cat_id = get_post_meta( $post_id, $key, true );
		if ( ! empty( $primary_cat_id ) ) {
			foreach ( $categories as $cat ) {
				if ( $cat->term_id == $primary_cat_id ) {
					$found_cat = $cat;
					break 2;
				}
			}
		}
	}

	if ( ! $found_cat ) {
		// 1. Prioritize core content type categories over everything else
		$priority_slugs = array( 'recensioni', 'recensione', 'anteprime', 'anteprima', 'speciali', 'speciale', 'editoriali', 'editoriale', 'guide', 'guida', 'rubriche', 'rubrica', 'news', 'notizie' );
		foreach ( $categories as $cat ) {
			if ( in_array( strtolower( $cat->slug ), $priority_slugs, true ) ) {
				$found_cat = $cat;
				break;
			}
		}
	}

	if ( ! $found_cat && count( $categories ) > 1 ) {
		// 2. Fallback strategy: If we haven't matched a priority type, try to skip platform/utility categories
		$utility_slugs = array( 'console', 'hardware', 'tech', 'videogiochi', 'games', 'film', 'serie-tv', 'cinema', 'pc-windows', 'pc', 'windows', 'ps5', 'playstation-5', 'playstation', 'xbox', 'nintendo-switch', 'switch' );
		foreach ( $categories as $cat ) {
			if ( ! in_array( strtolower( $cat->slug ), $utility_slugs, true ) ) {
				$found_cat = $cat;
				break;
			}
		}
	}

	if ( ! $found_cat ) {
		$found_cat = $categories[0];
	}

	if ( function_exists( 'wp_cache_set' ) ) {
		wp_cache_set( $cache_key, $found_cat, 'ntn_journal', 3600 );
	}
	return $found_cat;
}
endif;

if ( ! function_exists( 'ntn_get_image_alt' ) ) :
/**
 * Ottiene il testo alternativo reale (ALT text) di un'immagine da Media Library.
 * Se vuoto, usa un testo di fallback (es. titolo articolo).
 */
function ntn_get_image_alt( $post_id ) {
	$thumb_id = get_post_thumbnail_id( $post_id );
	if ( $thumb_id ) {
		$alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
		if ( ! empty( $alt ) ) {
			return $alt;
		}
	}
	// Fallback al titolo dell'articolo
	return get_the_title( $post_id );
}
endif;

if ( ! function_exists( 'ntn_get_igdb_data' ) ) :
/**
 * Ottiene un dato IGDB per un articolo o CPT Videogioco.
 * Cerca prima nei post_meta del post, poi nei term_meta dei tag associati o del tag con lo stesso slug.
 */
function ntn_get_igdb_data( $post_id, $meta_key ) {
	static $cache = array();
	$cache_key = $post_id . '|' . $meta_key;
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	// Se l'intero sistema IGDB è disattivato, nascondi i dati nel frontend.
	if ( ! is_admin() && get_option( 'ntn_igdb_scanner_active', 'yes' ) !== 'yes' ) {
		return '';
	}

	$val = '';
	
	// 1. Controlla prima i post_meta
	$post_val = get_post_meta( $post_id, $meta_key, true );
	if ( ! empty( $post_val ) ) {
		$val = $post_val;
	} else {
		// 2. Se vuoto, controlla i term_meta dei tag associati (con supporto a entità HTML e slug)
		$tags = get_the_tags( $post_id );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$term_val = get_term_meta( $tag->term_id, $meta_key, true );
				if ( ! empty( $term_val ) ) {
					$val = $term_val;
					break;
				}
				
				// Fallback su nome decodificato o slug pulito
				$decoded_name = html_entity_decode( $tag->name, ENT_QUOTES, 'UTF-8' );
				if ( $decoded_name !== $tag->name ) {
					$alt_tag = get_term_by( 'name', $decoded_name, 'post_tag' );
					if ( $alt_tag && ! is_wp_error( $alt_tag ) ) {
						$alt_val = get_term_meta( $alt_tag->term_id, $meta_key, true );
						if ( ! empty( $alt_val ) ) {
							$val = $alt_val;
							break;
						}
					}
				}
				
				$clean_slug = preg_replace( '/-\d+$/', '', $tag->slug );
				if ( $clean_slug !== $tag->slug ) {
					$alt_tag = get_term_by( 'slug', $clean_slug, 'post_tag' );
					if ( $alt_tag && ! is_wp_error( $alt_tag ) ) {
						$alt_val = get_term_meta( $alt_tag->term_id, $meta_key, true );
						if ( ! empty( $alt_val ) ) {
							$val = $alt_val;
							break;
						}
					}
				}
			}
		}

		// 3. Se ancora vuoto, cerca il tag avente lo stesso slug o titolo del post
		if ( empty( $val ) ) {
			$slug = get_post_field( 'post_name', $post_id );
			if ( $slug ) {
				$matched_tag = get_term_by( 'slug', $slug, 'post_tag' );
				if ( $matched_tag && ! is_wp_error( $matched_tag ) ) {
					$val = get_term_meta( $matched_tag->term_id, $meta_key, true );
				}
			}
			if ( empty( $val ) ) {
				$title = get_the_title( $post_id );
				if ( $title ) {
					$matched_tag = get_term_by( 'name', $title, 'post_tag' );
					if ( $matched_tag && ! is_wp_error( $matched_tag ) ) {
						$val = get_term_meta( $matched_tag->term_id, $meta_key, true );
					}
				}
			}
		}
	}
	
	// Eccezioni di formattazione
	if ( $meta_key === '_ntn_game_cover' && ! empty( $val ) ) {
		if ( function_exists( 'ntn_normalize_game_cover_url' ) ) {
			$val = ntn_normalize_game_cover_url( $val );
		}
	}
	
	if ( $meta_key === '_ntn_game_genres' && ! empty( $val ) ) {
		// Traduci i generi
		$val = ntn_translate_igdb_genres( $val );
	}

	if ( $meta_key === '_ntn_game_title' && empty( $val ) ) {
		// Se non è presente un titolo gioco esplicito, usiamo il titolo del post solo se è una recensione con voto
		$rating = get_post_rating( $post_id );
		if ( ! empty( $rating ) ) {
			$val = get_the_title( $post_id );
		}
	}
	
	$cache[ $cache_key ] = $val;
	return $val;
}
endif;

if ( ! function_exists( 'ntn_get_game_hub_url' ) ) :
/**
 * Funzione helper per ottenere rapidamente l'URL della hub page di un gioco.
 * Supporta sia il CPT "videogioco" che i Tag di WordPress.
 */
function ntn_get_game_hub_url( $post_id ) {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$result = '';

	// Se il post stesso è un CPT videogioco
	if ( get_post_type( $post_id ) === 'videogioco' ) {
		$permalink = get_permalink( $post_id );
		$result = ( ! empty( $permalink ) && ! is_wp_error( $permalink ) && is_string( $permalink ) ) ? $permalink : '';
		$cache[ $post_id ] = $result;
		return $result;
	}

	$tags = get_the_tags( $post_id );
	if ( $tags && is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			if ( ! ( $tag instanceof WP_Term ) ) {
				continue;
			}
			// Controlla prima se esiste un CPT videogioco con lo stesso slug
			$cpt = get_posts( array(
				'name'        => $tag->slug,
				'post_type'   => 'videogioco',
				'post_status' => 'publish',
				'numberposts' => 1,
			) );
			if ( ! empty( $cpt ) && isset( $cpt[0] ) && $cpt[0] instanceof WP_Post ) {
				$permalink = get_permalink( $cpt[0]->ID );
				$result = ( ! empty( $permalink ) && ! is_wp_error( $permalink ) && is_string( $permalink ) ) ? $permalink : '';
				$cache[ $post_id ] = $result;
				return $result;
			}

			// Cerca nei tag associati se c'è un term_meta IGDB
			$title   = get_term_meta( $tag->term_id, '_ntn_game_title', true );
			$igdb_id = get_term_meta( $tag->term_id, '_ntn_igdb_game_id', true );
			$cover   = get_term_meta( $tag->term_id, '_ntn_game_cover', true );
			if ( ! empty( $title ) || ! empty( $igdb_id ) || ! empty( $cover ) ) {
				$link = get_term_link( $tag );
				$result = ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) ? $link : '';
				$cache[ $post_id ] = $result;
				return $result;
			}
			
			// Fallback su varianti nome decodificato o slug pulito
			$decoded_name = html_entity_decode( $tag->name, ENT_QUOTES, 'UTF-8' );
			if ( $decoded_name !== $tag->name ) {
				$alt_tag = get_term_by( 'name', $decoded_name, 'post_tag' );
				if ( $alt_tag && ! is_wp_error( $alt_tag ) ) {
					$alt_title = get_term_meta( $alt_tag->term_id, '_ntn_game_title', true );
					if ( ! empty( $alt_title ) ) {
						$link = get_term_link( $alt_tag );
						$result = ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) ? $link : '';
						$cache[ $post_id ] = $result;
						return $result;
					}
				}
			}
		}
	}
	
	// Se non c'è, ma c'è un titolo gioco nel post_meta, cerca CPT o tag con quel nome
	$game_title = get_post_meta( $post_id, '_ntn_game_title', true );
	if ( ! empty( $game_title ) && is_string( $game_title ) ) {
		$cpt = get_posts( array(
			'title'       => $game_title,
			'post_type'   => 'videogioco',
			'post_status' => 'publish',
			'numberposts' => 1,
		) );
		if ( ! empty( $cpt ) && isset( $cpt[0] ) && $cpt[0] instanceof WP_Post ) {
			$permalink = get_permalink( $cpt[0]->ID );
			$result = ( ! empty( $permalink ) && ! is_wp_error( $permalink ) && is_string( $permalink ) ) ? $permalink : '';
			$cache[ $post_id ] = $result;
			return $result;
		}

		$term = get_term_by( 'name', $game_title, 'post_tag' );
		if ( ! $term || is_wp_error( $term ) ) {
			$term = get_term_by( 'slug', sanitize_title( $game_title ), 'post_tag' );
		}
		if ( $term && ! is_wp_error( $term ) && $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			$result = ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) ? $link : '';
			$cache[ $post_id ] = $result;
			return $result;
		}
	}
	
	$cache[ $post_id ] = $result;
	return $result;
}
endif;

if ( ! function_exists( 'ntn_get_hub_igdb_info' ) ) :
/**
 * Ottiene tutti i metadati IGDB associati a un Videogioco (CPT "videogioco" o Tag archivio)
 * con fallback bidirezionale post_meta <-> term_meta.
 *
 * @param mixed $object ID del post, ID del term, WP_Post, WP_Term o null per rilevamento automatico.
 * @return array Array dei metadati IGDB formattati.
 */
function ntn_get_hub_igdb_info( $object = null ) {
	static $cache = array();

	// Se l'intero sistema IGDB è disattivato, nascondi i dati nel frontend.
	if ( ! is_admin() && get_option( 'ntn_igdb_scanner_active', 'yes' ) !== 'yes' ) {
		return array();
	}

	$post = null;
	$term = null;

	if ( is_array( $object ) ) {
		$tax = ( ! empty( $object['taxonomy'] ) && is_string( $object['taxonomy'] ) ) ? $object['taxonomy'] : 'post_tag';
		$id  = ( ! empty( $object['id'] ) && is_numeric( $object['id'] ) ) ? intval( $object['id'] ) : ( ( ! empty( $object['term_id'] ) && is_numeric( $object['term_id'] ) ) ? intval( $object['term_id'] ) : 0 );
		if ( $id ) {
			$check_term = get_term( $id, $tax );
			if ( $check_term && ! is_wp_error( $check_term ) && $check_term instanceof WP_Term ) {
				$term = $check_term;
			} else {
				$check_post = get_post( $id );
				if ( $check_post && $check_post instanceof WP_Post ) {
					$post = $check_post;
				}
			}
		}
	} elseif ( is_null( $object ) || empty( $object ) ) {
		$queried = get_queried_object();
		if ( $queried instanceof WP_Post ) {
			$post = $queried;
		} elseif ( $queried instanceof WP_Term ) {
			$term = $queried;
		} elseif ( is_singular() ) {
			$post = get_post();
		}
	} elseif ( $object instanceof WP_Post ) {
		$post = $object;
	} elseif ( $object instanceof WP_Term ) {
		$term = $object;
	} elseif ( is_numeric( $object ) ) {
		$obj_id = intval( $object );
		$check_post = get_post( $obj_id );
		if ( $check_post && $check_post instanceof WP_Post && $check_post->post_type === 'videogioco' ) {
			$post = $check_post;
		} else {
			$check_term = get_term( $obj_id, 'post_tag' );
			if ( $check_term && ! is_wp_error( $check_term ) && $check_term instanceof WP_Term ) {
				$term = $check_term;
			} elseif ( $check_post && $check_post instanceof WP_Post ) {
				$post = $check_post;
			}
		}
	}

	// Cache key dopo la risoluzione dell'entità
	$cache_key = '';
	if ( $post && $post instanceof WP_Post ) {
		$cache_key = 'post_' . $post->ID;
	} elseif ( $term && $term instanceof WP_Term ) {
		$cache_key = 'term_' . $term->term_id;
	}
	if ( $cache_key && isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	// Trova l'entità corrispondente per il fallback bidirezionale
	if ( $post && ! $term && $post instanceof WP_Post ) {
		// A. Controlla prima i tag associati al post
		$post_tags = get_the_tags( $post->ID );
		if ( $post_tags && is_array( $post_tags ) ) {
			foreach ( $post_tags as $ptag ) {
				if ( $ptag instanceof WP_Term && ( get_term_meta( $ptag->term_id, '_ntn_game_cover', true ) || get_term_meta( $ptag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $ptag->term_id, '_ntn_game_title', true ) ) ) {
					$term = $ptag;
					break;
				}
			}
		}

		// B. Se non trovato nei tag associati, cerca per slug, titolo o game_title
		if ( ! $term ) {
			$post_slug = isset( $post->post_name ) ? $post->post_name : '';
			if ( ! empty( $post_slug ) && is_string( $post_slug ) ) {
				$term = get_term_by( 'slug', $post_slug, 'post_tag' );
			}
			if ( ! $term || is_wp_error( $term ) || ! ( $term instanceof WP_Term ) ) {
				$post_title = isset( $post->post_title ) ? $post->post_title : '';
				if ( ! empty( $post_title ) && is_string( $post_title ) ) {
					$term = get_term_by( 'name', $post_title, 'post_tag' );
				}
				if ( ! $term || is_wp_error( $term ) || ! ( $term instanceof WP_Term ) ) {
					$game_title_meta = get_post_meta( $post->ID, '_ntn_game_title', true );
					if ( ! empty( $game_title_meta ) && is_string( $game_title_meta ) ) {
						$term = get_term_by( 'name', $game_title_meta, 'post_tag' );
						if ( ! $term || is_wp_error( $term ) || ! ( $term instanceof WP_Term ) ) {
							$term = get_term_by( 'slug', sanitize_title( $game_title_meta ), 'post_tag' );
						}
					}
				}
				if ( is_wp_error( $term ) || ! ( $term instanceof WP_Term ) ) {
					$term = null;
				}
			}
		}
	} elseif ( $term && ! $post && $term instanceof WP_Term ) {
		$term_slug = isset( $term->slug ) ? $term->slug : '';
		if ( ! empty( $term_slug ) && is_string( $term_slug ) ) {
			$cpt_posts = get_posts( array(
				'name'        => $term_slug,
				'post_type'   => 'videogioco',
				'post_status' => 'any',
				'numberposts' => 1,
			) );
			if ( ! empty( $cpt_posts ) && isset( $cpt_posts[0] ) && $cpt_posts[0] instanceof WP_Post ) {
				$post = $cpt_posts[0];
			}
		}
		if ( ! $post ) {
			$term_name = isset( $term->name ) ? $term->name : '';
			if ( ! empty( $term_name ) && is_string( $term_name ) ) {
				$cpt_posts = get_posts( array(
					'title'       => $term_name,
					'post_type'   => 'videogioco',
					'post_status' => 'any',
					'numberposts' => 1,
				) );
				if ( ! empty( $cpt_posts ) && isset( $cpt_posts[0] ) && $cpt_posts[0] instanceof WP_Post ) {
					$post = $cpt_posts[0];
				}
			}
		}
	}

	// Helper per recuperare meta con fallback bidirezionale
	$get_meta_value = function( $key ) use ( $post, $term ) {
		$val = '';
		if ( $post && $post instanceof WP_Post ) {
			$val = get_post_meta( $post->ID, $key, true );
		}
		if ( ( empty( $val ) || '' === $val ) && $term && $term instanceof WP_Term ) {
			$val = get_term_meta( $term->term_id, $key, true );
		}
		return $val;
	};

	$cover = $get_meta_value( '_ntn_game_cover' );
	if ( ! empty( $cover ) && function_exists( 'ntn_normalize_game_cover_url' ) ) {
		$cover = ntn_normalize_game_cover_url( $cover );
	}
	if ( empty( $cover ) && $post && $post instanceof WP_Post && has_post_thumbnail( $post->ID ) ) {
		$cover = get_the_post_thumbnail_url( $post->ID, 'full' );
		if ( ! empty( $cover ) && function_exists( 'ntn_normalize_game_cover_url' ) ) {
			$cover = ntn_normalize_game_cover_url( $cover );
		}
	}

	$title = $get_meta_value( '_ntn_game_title' );
	if ( empty( $title ) ) {
		if ( $post && isset( $post->post_title ) ) {
			$title = $post->post_title;
		} elseif ( $term && isset( $term->name ) ) {
			$title = $term->name;
		}
	}

	$release    = $get_meta_value( '_ntn_game_release' );
	$developer  = $get_meta_value( '_ntn_game_developer' );
	$publisher  = $get_meta_value( '_ntn_game_publisher' );
	$platforms  = $get_meta_value( '_ntn_game_platforms' );
	$raw_genres = $get_meta_value( '_ntn_game_genres' );
	$genres     = ntn_translate_igdb_genres( $raw_genres );
	$engine     = $get_meta_value( '_ntn_game_engine' );
	$igdb_url   = $get_meta_value( '_ntn_game_url' );
	if ( empty( $igdb_url ) ) {
		$igdb_url = $get_meta_value( '_ntn_igdb_url' );
	}
	$video      = $get_meta_value( '_ntn_game_video' );
	$scr_val    = $get_meta_value( '_ntn_game_screenshots' );

	$data = array(
		'title'       => $title,
		'cover'       => $cover,
		'release'     => $release,
		'release_precision' => $get_meta_value( '_ntn_game_release_precision' ), // v3.21.0: giorno, mese, trimestre o anno
		'developer'   => $developer,
		'publisher'   => $publisher,
		'platforms'   => $platforms,
		'genres'      => $genres,
		'engine'      => $engine,
		'igdb_url'    => $igdb_url,
		'url'         => $igdb_url,
		'video'       => $video,
		'screenshots' => $scr_val,
	);
	
	// Filtra valori vuoti
	$result = array_filter( $data );
	if ( $cache_key ) {
		$cache[ $cache_key ] = $result;
	}
	return $result;
}
endif;

if ( ! function_exists( 'ntn_get_tag_igdb_info' ) ) :
/**
 * Ottiene tutti i metadati IGDB associati a un Tag (Retrocompatibilità).
 */
function ntn_get_tag_igdb_info( $term_id = null ) {
	if ( is_numeric( $term_id ) ) {
		$term = get_term( intval( $term_id ), 'post_tag' );
		if ( $term && ! is_wp_error( $term ) ) {
			return ntn_get_hub_igdb_info( $term );
		}
	}
	return ntn_get_hub_igdb_info( $term_id );
}
endif;
