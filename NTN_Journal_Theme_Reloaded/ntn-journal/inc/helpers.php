<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Percorso dell'immagine di copertina predefinita, inclusa localmente nel
 * tema, usata quando un articolo non ha un'immagine in evidenza.
 * Sostituisce i precedenti riferimenti hotlinkati a images.unsplash.com,
 * non ammessi dalle linee guida WordPress.org (tutte le risorse vanno
 * incluse nel pacchetto del tema).
 */
/**
 * Foto dell'autore con le iniziali a matrice di punti come riserva (v3.17.1, scelta del CEO).
 *
 * Le iniziali stanno sempre sotto; sopra c'è la foto del profilo chiesta a Gravatar senza
 * immagine di riserva ("404"). Se la foto non esiste, l'immagine si toglie da sola e restano
 * le iniziali, al posto della sagoma grigia di WordPress.
 *
 * @param int    $user_id ID dell'utente.
 * @param string $name    Nome mostrato (per le iniziali).
 * @param int    $size    Lato della foto in pixel (doppio per gli schermi ad alta densità).
 * @return string Markup da mettere dentro .ntn-author-avatar.
 */
function ntn_journal_author_avatar( $user_id, $name, $size = 112 ) {
	$initials = '';
	foreach ( array_slice( (array) preg_split( '/\s+/', trim( (string) $name ) ), 0, 2 ) as $word ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
	}
	$initials = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $initials ) : strtoupper( $initials );
	$out      = '<span>' . esc_html( $initials ) . '</span>';

	$url = ( $user_id && get_option( 'show_avatars', 1 ) ) ? get_avatar_url( (int) $user_id, array( 'size' => (int) $size, 'default' => '404' ) ) : '';
	if ( $url ) {
		$out .= '<img src="' . esc_url( $url ) . '" alt="" width="' . (int) $size . '" height="' . (int) $size . '" loading="lazy" decoding="async" onerror="this.remove()">';
	}
	return $out;
}

function ntn_journal_default_cover_url() {
	return get_template_directory_uri() . '/assets/images/default-cover.png';
}

if ( ! function_exists( 'ntn_get_post_games' ) ) :
/**
 * Elenca i videogiochi di un articolo, uno per ogni tag gioco, senza mescolare i dati tra un gioco e l'altro.
 *
 * L'elenco è senza doppioni e in ordine alfabetico (ignora maiuscole e accenti). Se l'articolo non ha tag
 * gioco ma ha il post_meta _ntn_game_title (vecchi articoli), restituisce un solo gioco letto dai post_meta.
 * I valori non sono ancora sottoposti a escape: lo fanno i template.
 *
 * @param int $post_id ID dell'articolo.
 * @return array[] Ogni gioco ha: term, igdb_id, title, cover, release, release_precision, developer, publisher,
 *                 platforms, genres, engine, igdb_url, hub_url, review, review_rating, is_current_review.
 */
function ntn_get_post_games( $post_id ) {
	static $cache = array();

	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return array();
	}

	// Se l'intero sistema IGDB è disattivato, nascondi i dati nel frontend.
	if ( ! is_admin() && get_option( 'ntn_igdb_scanner_active', 'yes' ) !== 'yes' ) {
		return array();
	}

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$games = array();
	$seen  = array();
	$tags  = get_the_tags( $post_id );

	if ( $tags && ! is_wp_error( $tags ) ) {
		foreach ( $tags as $tag ) {
			$term = ntn_resolve_game_term( $tag );
			if ( ! $term ) {
				continue;
			}

			$game = ntn_get_term_game( $term );
			$key  = $game['igdb_id'] ? 'id:' . $game['igdb_id'] : 'title:' . strtolower( ntn_game_sort_key( $game['title'] ) );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$games[]      = $game;
		}
	}

	if ( empty( $games ) ) {
		$fallback = ntn_get_post_meta_game( $post_id );
		if ( $fallback ) {
			$games[] = $fallback;
		}
	}

	foreach ( $games as $index => $game ) {
		$games[ $index ]['is_current_review'] = $game['review'] && (int) $game['review']->ID === $post_id;
	}

	// A parità di titolo resta l'ordine di partenza, anche con PHP 7.4 dove usort non lo garantisce.
	$sort_keys = array_map(
		static function ( $game ) {
			return ntn_game_sort_key( $game['title'] );
		},
		$games
	);
	uksort(
		$games,
		static function ( $a, $b ) use ( $sort_keys ) {
			$order = strcasecmp( $sort_keys[ $a ], $sort_keys[ $b ] );
			return 0 !== $order ? $order : $a - $b;
		}
	);
	$games = array_values( $games );

	$cache[ $post_id ] = $games;
	return $games;
}
endif;

if ( ! function_exists( 'ntn_resolve_game_term' ) ) :
/**
 * Trova il tag gioco che corrisponde a un tag dell'articolo: il tag stesso oppure la sua variante con il
 * nome decodificato o con lo slug senza suffisso numerico. Gli altri tag non sono giochi.
 *
 * @param mixed $tag Tag dell'articolo.
 * @return WP_Term|null Il tag con i dati del gioco, o null.
 */
function ntn_resolve_game_term( $tag ) {
	if ( ! ( $tag instanceof WP_Term ) ) {
		return null;
	}

	$is_game = static function ( $term ) {
		return $term instanceof WP_Term && ( get_term_meta( $term->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $term->term_id, '_ntn_game_title', true ) );
	};

	if ( $is_game( $tag ) ) {
		return $tag;
	}

	$decoded_name = html_entity_decode( $tag->name, ENT_QUOTES, 'UTF-8' );
	if ( $decoded_name !== $tag->name ) {
		$alt_tag = get_term_by( 'name', $decoded_name, 'post_tag' );
		if ( $is_game( $alt_tag ) ) {
			return $alt_tag;
		}
	}

	$clean_slug = preg_replace( '/-\d+$/', '', $tag->slug );
	if ( $clean_slug !== $tag->slug ) {
		$alt_tag = get_term_by( 'slug', $clean_slug, 'post_tag' );
		if ( $is_game( $alt_tag ) ) {
			return $alt_tag;
		}
	}

	return null;
}
endif;

if ( ! function_exists( 'ntn_get_term_game' ) ) :
/**
 * Raccoglie i dati di un solo gioco a partire dal suo tag. Il risultato vale per tutta la richiesta.
 *
 * @param WP_Term $term Tag del gioco.
 * @return array Dati del gioco nel formato di ntn_get_post_games().
 */
function ntn_get_term_game( WP_Term $term ) {
	static $cache = array();
	if ( isset( $cache[ $term->term_id ] ) ) {
		return $cache[ $term->term_id ];
	}

	$cpt = get_posts( array(
		'name'        => $term->slug,
		'post_type'   => 'videogioco',
		'post_status' => 'publish',
		'numberposts' => 1,
	) );
	$cpt = ( ! empty( $cpt ) && $cpt[0] instanceof WP_Post ) ? $cpt[0] : null;

	$igdb_id = (int) get_term_meta( $term->term_id, '_ntn_igdb_game_id', true );
	if ( ! $igdb_id && $cpt ) {
		$igdb_id = (int) get_post_meta( $cpt->ID, '_ntn_igdb_game_id', true );
	}

	$hub_url = $cpt ? get_permalink( $cpt->ID ) : get_term_link( $term );
	$review  = ntn_get_game_review( $term, $cpt );

	$cache[ $term->term_id ] = ntn_format_post_game(
		ntn_get_hub_igdb_info( $term ),
		array(
			'term'    => $term,
			'igdb_id' => $igdb_id,
			'hub_url' => is_string( $hub_url ) ? $hub_url : '',
			'review'  => $review,
		)
	);
	return $cache[ $term->term_id ];
}
endif;

if ( ! function_exists( 'ntn_get_post_meta_game' ) ) :
/**
 * Costruisce un gioco dai post_meta dell'articolo, per i vecchi articoli senza tag gioco.
 *
 * @param int $post_id ID dell'articolo.
 * @return array|null Dati del gioco nel formato di ntn_get_post_games(), o null se manca il titolo.
 */
function ntn_get_post_meta_game( $post_id ) {
	$title = get_post_meta( $post_id, '_ntn_game_title', true );
	if ( empty( $title ) || ! is_string( $title ) ) {
		return null;
	}

	$term = get_term_by( 'name', $title, 'post_tag' );
	if ( ! ( $term instanceof WP_Term ) ) {
		$term = get_term_by( 'slug', sanitize_title( $title ), 'post_tag' );
	}
	$term = ( $term instanceof WP_Term ) ? $term : null;

	$review = $term ? ntn_get_game_review( $term ) : null;
	if ( ! $review && has_category( array( 'recensioni', 'recensione' ), $post_id ) ) {
		$review = get_post( $post_id );
	}

	$cover    = get_post_meta( $post_id, '_ntn_game_cover', true );
	$genres   = get_post_meta( $post_id, '_ntn_game_genres', true );
	$igdb_url = get_post_meta( $post_id, '_ntn_game_url', true );
	if ( empty( $igdb_url ) ) {
		$igdb_url = get_post_meta( $post_id, '_ntn_igdb_url', true );
	}

	return ntn_format_post_game(
		array(
			'title'             => $title,
			'cover'             => ( ! empty( $cover ) && function_exists( 'ntn_normalize_game_cover_url' ) ) ? ntn_normalize_game_cover_url( $cover ) : $cover,
			'release'           => get_post_meta( $post_id, '_ntn_game_release', true ),
			'release_precision' => get_post_meta( $post_id, '_ntn_game_release_precision', true ),
			'developer'         => get_post_meta( $post_id, '_ntn_game_developer', true ),
			'publisher'         => get_post_meta( $post_id, '_ntn_game_publisher', true ),
			'platforms'         => get_post_meta( $post_id, '_ntn_game_platforms', true ),
			'genres'            => ntn_translate_igdb_genres( $genres ),
			'engine'            => get_post_meta( $post_id, '_ntn_game_engine', true ),
			'igdb_url'          => $igdb_url,
		),
		array(
			'term'    => $term,
			'igdb_id' => (int) get_post_meta( $post_id, '_ntn_igdb_game_id', true ),
			'hub_url' => ntn_get_game_hub_url( $post_id ),
			'review'  => $review,
		)
	);
}
endif;

if ( ! function_exists( 'ntn_get_game_review' ) ) :
/**
 * Trova la recensione di un gioco: quella scelta a mano, se è pubblicata e in categoria Recensioni,
 * altrimenti l'ultima pubblicata con il tag del gioco. Mai anteprime o notizie.
 *
 * @param WP_Term      $term Tag del gioco.
 * @param WP_Post|null $cpt  Scheda videogioco collegata al tag, se esiste.
 * @return WP_Post|null La recensione, o null.
 */
function ntn_get_game_review( WP_Term $term, $cpt = null ) {
	$review_categories = array( 'recensioni', 'recensione' );

	$manual_ids = array( get_term_meta( $term->term_id, '_ntn_featured_review_id', true ) );
	if ( $cpt instanceof WP_Post ) {
		array_unshift( $manual_ids, get_post_meta( $cpt->ID, '_ntn_featured_review_id', true ) );
	}

	foreach ( $manual_ids as $manual_id ) {
		$manual = $manual_id ? get_post( (int) $manual_id ) : null;
		if ( $manual instanceof WP_Post && 'publish' === $manual->post_status && has_category( $review_categories, $manual ) ) {
			return $manual;
		}
	}

	$query = new WP_Query( array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => 1,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'tax_query'              => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $term->term_id,
			),
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => $review_categories,
			),
		),
	) );

	return ! empty( $query->posts ) ? $query->posts[0] : null;
}
endif;

if ( ! function_exists( 'ntn_format_post_game' ) ) :
/**
 * Porta i dati di un gioco al formato di ntn_get_post_games(), con ogni campo sempre presente.
 *
 * @param array $info  Dati del gioco letti dai meta.
 * @param array $extra Valori term, igdb_id, hub_url e review.
 * @return array Dati del gioco.
 */
function ntn_format_post_game( array $info, array $extra ) {
	$game = array(
		'term'              => $extra['term'],
		'igdb_id'           => $extra['igdb_id'],
		'hub_url'           => $extra['hub_url'],
		'review'            => $extra['review'],
		'review_rating'     => $extra['review'] ? max( 0, min( 5, (int) round( (float) get_post_rating( $extra['review']->ID ) ) ) ) : 0,
		'is_current_review' => false,
	);

	foreach ( array( 'title', 'cover', 'release', 'release_precision', 'developer', 'publisher', 'platforms', 'genres', 'engine', 'igdb_url' ) as $field ) {
		$game[ $field ] = isset( $info[ $field ] ) ? $info[ $field ] : '';
	}

	return $game;
}
endif;

if ( ! function_exists( 'ntn_apply_post_meta_to_game' ) ) :
/**
 * Applica a un gioco i dati scritti sull'articolo: ogni post_meta non vuoto vince sul dato del tag.
 * Serve agli articoli con un gioco solo, dove questi dati hanno sempre avuto la precedenza.
 *
 * @param array $game    Dati del gioco nel formato di ntn_get_post_games().
 * @param int   $post_id ID dell'articolo.
 * @return array Dati del gioco aggiornati.
 */
function ntn_apply_post_meta_to_game( array $game, $post_id ) {
	$fields = array(
		'title'             => '_ntn_game_title',
		'cover'             => '_ntn_game_cover',
		'release'           => '_ntn_game_release',
		'release_precision' => '_ntn_game_release_precision',
		'developer'         => '_ntn_game_developer',
		'publisher'         => '_ntn_game_publisher',
		'platforms'         => '_ntn_game_platforms',
		'genres'            => '_ntn_game_genres',
		'engine'            => '_ntn_game_engine',
		'igdb_url'          => '_ntn_game_url',
	);

	foreach ( $fields as $field => $meta_key ) {
		$value = get_post_meta( $post_id, $meta_key, true );
		if ( empty( $value ) ) {
			continue;
		}
		if ( 'cover' === $field && function_exists( 'ntn_normalize_game_cover_url' ) ) {
			$value = ntn_normalize_game_cover_url( $value );
		} elseif ( 'genres' === $field ) {
			$value = ntn_translate_igdb_genres( $value );
		}
		$game[ $field ] = $value;
	}

	return $game;
}
endif;

/**
 * Stampa la riga con il link alla recensione di un gioco, o l'avviso che non è stato recensito.
 *
 * @param array    $game        Dati del gioco nel formato di ntn_get_post_games().
 * @param string   $class       Classe della riga.
 * @param int|null $panel_index Nelle schede a linguette, il pannello del gioco: dal secondo in poi la riga parte nascosta.
 */
function ntn_journal_game_review_row( array $game, $class, $panel_index = null ) {
	$attributes = '';
	if ( null !== $panel_index ) {
		$attributes = ' data-i="' . esc_attr( (string) $panel_index ) . '"' . ( $panel_index > 0 ? ' hidden' : '' );
	}

	if ( ! $game['review'] ) {
		?>
		<div class="<?php echo esc_attr( $class ); ?> is-none"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- già sanificato qui sopra. ?>>
			<span>// Non recensito</span>
			<span class="ntn-leds" aria-hidden="true"><?php echo str_repeat( '<i class="ntn-led is-off"></i>', 5 ); ?></span>
		</div>
		<?php
		return;
	}

	$review_rating = (int) $game['review_rating'];
	?>
	<a href="<?php echo esc_url( get_permalink( $game['review'] ) ); ?>" class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Leggi la recensione di %s', $game['title'] ) ); ?>"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- già sanificato qui sopra. ?>>
		<span>Leggi la recensione →</span>
		<?php if ( $review_rating > 0 ) : ?>
			<span class="ntn-leds" aria-hidden="true">
				<?php
				for ( $i = 1; $i <= 5; $i++ ) {
					$led_class = 'ntn-led';
					if ( $i <= $review_rating ) {
						$led_class .= ' is-on';
					}
					if ( $i === $review_rating ) {
						$led_class .= ' is-last';
					}
					echo '<i class="' . esc_attr( $led_class ) . '"></i>';
				}
				?>
			</span>
		<?php endif; ?>
	</a>
	<?php
}

if ( ! function_exists( 'ntn_game_sort_key' ) ) :
/**
 * Prepara un titolo per l'ordinamento e il confronto: senza entità HTML e senza accenti.
 *
 * @param mixed $title Titolo del gioco.
 * @return string Titolo normalizzato.
 */
function ntn_game_sort_key( $title ) {
	return remove_accents( html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ) );
}
endif;

if ( ! function_exists( 'ntn_get_game_featured_content' ) ) :
/**
 * Cerca l'articolo in evidenza associato a un Videogioco (CPT o Tag).
 * Priorità: Manuale (_ntn_featured_review_id) > Recensioni > Anteprime > Editoriali > Notizie.
 *
 * @param mixed $object ID del post, ID del term, WP_Post, WP_Term o null per rilevamento automatico.
 * @return array|false Array con 'post' e 'type' ('review'|'preview'|'editorial'|'news'|'article'), o false.
 */
function ntn_get_game_featured_content( $object = null ) {
	$cache_enabled = get_option( 'ntn_journal_enable_hub_cache', 'yes' );
	
	$post = null;
	$term = null;

	if ( is_null( $object ) || empty( $object ) ) {
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
		if ( $check_post && $check_post->post_type === 'videogioco' ) {
			$post = $check_post;
		} else {
			$check_term = get_term( $obj_id, 'post_tag' );
			if ( $check_term && ! is_wp_error( $check_term ) ) {
				$term = $check_term;
			} elseif ( $check_post ) {
				$post = $check_post;
			}
		}
	}

	// Trova l'entità corrispondente per fallback bidirezionale
	if ( $post && ! $term ) {
		// A. Controlla prima i tag associati al post
		$post_tags = get_the_tags( $post->ID );
		if ( $post_tags ) {
			foreach ( $post_tags as $ptag ) {
				if ( get_term_meta( $ptag->term_id, '_ntn_game_cover', true ) || get_term_meta( $ptag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $ptag->term_id, '_ntn_game_title', true ) ) {
					$term = $ptag;
					break;
				}
			}
		}

		// B. Se non trovato nei tag associati, cerca per slug, titolo o game_title
		if ( ! $term ) {
			$term = get_term_by( 'slug', $post->post_name, 'post_tag' );
			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'name', $post->post_title, 'post_tag' );
				if ( ! $term || is_wp_error( $term ) ) {
					$game_title_meta = get_post_meta( $post->ID, '_ntn_game_title', true );
					if ( ! empty( $game_title_meta ) ) {
						$term = get_term_by( 'name', $game_title_meta, 'post_tag' );
						if ( ! $term || is_wp_error( $term ) ) {
							$term = get_term_by( 'slug', sanitize_title( $game_title_meta ), 'post_tag' );
						}
					}
				}
				if ( is_wp_error( $term ) ) {
					$term = null;
				}
			}
		}
	} elseif ( $term && ! $post ) {
		$cpt_posts = get_posts( array(
			'name'        => $term->slug,
			'post_type'   => 'videogioco',
			'post_status' => 'any',
			'numberposts' => 1,
		) );
		if ( ! empty( $cpt_posts ) ) {
			$post = $cpt_posts[0];
		} else {
			$cpt_posts = get_posts( array(
				'title'       => $term->name,
				'post_type'   => 'videogioco',
				'post_status' => 'any',
				'numberposts' => 1,
			) );
			if ( ! empty( $cpt_posts ) ) {
				$post = $cpt_posts[0];
			}
		}
	}

	$cache_id = '';
	if ( $post ) {
		$cache_id = 'post_' . $post->ID;
	} elseif ( $term ) {
		$cache_id = 'term_' . $term->term_id;
	}

	$transient_key = 'ntn_hub_featured_' . $cache_id;
	
	if ( 'yes' === $cache_enabled && ! empty( $cache_id ) ) {
		$cached_result = get_transient( $transient_key );
		if ( false !== $cached_result ) {
			return $cached_result;
		}
	}

	$result = false;

	// 1. Controllo selezione manuale (_ntn_featured_review_id) con fallback bidirezionale
	$manual_id = 0;
	if ( $post ) {
		$manual_id = get_post_meta( $post->ID, '_ntn_featured_review_id', true );
	}
	if ( empty( $manual_id ) && $term ) {
		$manual_id = get_term_meta( $term->term_id, '_ntn_featured_review_id', true );
	}

	if ( ! empty( $manual_id ) ) {
		$featured_post = get_post( intval( $manual_id ) );
		if ( $featured_post && $featured_post->post_status === 'publish' ) {
			$type = 'article';
			if ( has_category( 'recensioni', $featured_post ) || has_category( 'recensione', $featured_post ) ) {
				$type = 'review';
			} elseif ( has_category( 'anteprime', $featured_post ) || has_category( 'anteprima', $featured_post ) ) {
				$type = 'preview';
			} elseif ( has_category( 'editoriali', $featured_post ) || has_category( 'editoriale', $featured_post ) ) {
				$type = 'editorial';
			} elseif ( has_category( 'notizie', $featured_post ) || has_category( 'news', $featured_post ) ) {
				$type = 'news';
			}
			$result = array( 'post' => $featured_post, 'type' => $type );
		}
	}

	// 2. Ricerca automatica con ordine di priorità tassativo
	if ( ! $result ) {
		$tag_slug = '';
		$tag_id = 0;
		if ( $term ) {
			$tag_slug = $term->slug;
			$tag_id = $term->term_id;
		} elseif ( $post ) {
			$tag_slug = $post->post_name;
		}

		if ( ! empty( $tag_slug ) || ! empty( $tag_id ) ) {
			$tax_term_field = $tag_id ? 'term_id' : 'slug';
			$tax_term_value = $tag_id ? $tag_id : $tag_slug;

			$priority_types = array(
				'review'    => array( 'recensioni', 'recensione' ),
				'preview'   => array( 'anteprime', 'anteprima' ),
				'editorial' => array( 'editoriali', 'editoriale' ),
				'news'      => array( 'notizie', 'news' ),
			);

			foreach ( $priority_types as $type_key => $category_slugs ) {
				$query = new WP_Query( array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => 1,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'tax_query'           => array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'post_tag',
							'field'    => $tax_term_field,
							'terms'    => $tax_term_value,
						),
						array(
							'taxonomy' => 'category',
							'field'    => 'slug',
							'terms'    => $category_slugs,
						),
					),
				) );

				if ( $query->have_posts() ) {
					$result = array(
						'post' => $query->posts[0],
						'type' => $type_key,
					);
					break;
				}
			}
		}
	}

	if ( 'yes' === $cache_enabled && ! empty( $cache_id ) ) {
		set_transient( $transient_key, $result, DAY_IN_SECONDS );
	}

	return $result;
}
endif;

if ( ! function_exists( 'ntn_get_tag_featured_content' ) ) :
/**
 * Cerca la recensione principale associata a un Tag (Retrocompatibilità per l'Hub Page).
 * Ritorna l'oggetto post o array se trovato, altrimenti false.
 */
function ntn_get_tag_featured_content( $term_id ) {
	if ( is_numeric( $term_id ) ) {
		$term = get_term( intval( $term_id ), 'post_tag' );
		if ( $term && ! is_wp_error( $term ) ) {
			return ntn_get_game_featured_content( $term );
		}
	}
	return ntn_get_game_featured_content( $term_id );
}
endif;

/**
 * Trasforma una stringa di sviluppatori o publisher in link se esiste il tag corrispondente.
 *
 * @param mixed $companies_string Stringa o array di sviluppatori/publisher.
 * @return string HTML con i link o testo semplice se il tag non esiste.
 */
function ntn_link_companies_to_tags( $companies_string ) {
	if ( empty( $companies_string ) ) {
		return '';
	}

	if ( is_array( $companies_string ) ) {
		$companies = $companies_string;
	} elseif ( is_object( $companies_string ) && ! method_exists( $companies_string, '__toString' ) ) {
		return '';
	} else {
		$companies = explode( ',', (string) $companies_string );
	}

	$output_array = array();

	foreach ( $companies as $company ) {
		if ( ! is_scalar( $company ) && ( ! is_object( $company ) || ! method_exists( $company, '__toString' ) ) ) {
			continue;
		}
		$company = trim( (string) $company );
		if ( empty( $company ) ) continue;

		// Cerca il tag per nome o per slug
		$term = get_term_by( 'name', $company, 'post_tag' );
		if ( ! $term || is_wp_error( $term ) ) {
			$term = get_term_by( 'slug', sanitize_title( $company ), 'post_tag' );
		}

		if ( $term && ! is_wp_error( $term ) && $term instanceof WP_Term ) {
			// Se il tag esiste, crea il link
			$link = get_term_link( $term );
			$formatted_name = '// ' . strtoupper( $company );

			if ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) {
				$output_array[] = '<a href="' . esc_url( $link ) . '" class="inline-flex items-center gap-1.5 hover:text-red-500 dark:hover:text-red-400 transition-colors group"><span>' . esc_html( $formatted_name ) . '</span><span class="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse shrink-0"></span></a>';
			} else {
				$output_array[] = esc_html( $company );
			}
		} else {
			// Nessun tag trovato, restituisci testo semplice
			$output_array[] = esc_html( $company );
		}
	}

	return implode( ', ', $output_array );
}

/**
 * CSS Class per i badge del motore grafico.
 */
define( 'NTN_ENGINE_BADGE_CSS', 'font-mono text-[9px] text-zinc-300 border border-zinc-700 px-2 py-1 leading-none inline-flex items-center justify-center uppercase hover:border-red-500 hover:text-white transition-colors' );

/**
 * Trasforma una stringa di motori grafici (es. "Unity, Unreal Engine 5") in badge cliccabili collegati all'archivio della tassonomia game_engine.
 *
 * @param mixed $engines_string Stringa o array di motori grafici.
 * @param string $badge_class Classe CSS opzionale per il badge.
 * @return string HTML dei badge linkati.
 */
function ntn_link_engines_to_terms( $engines_string, $badge_class = NTN_ENGINE_BADGE_CSS ) {
	if ( empty( $engines_string ) ) {
		return '';
	}

	if ( is_array( $engines_string ) ) {
		$engines = $engines_string;
	} elseif ( is_object( $engines_string ) && ! method_exists( $engines_string, '__toString' ) ) {
		return '';
	} else {
		$engines = explode( ',', (string) $engines_string );
	}

	$output_array = array();
	$enable_links = get_option( 'ntn_journal_enable_game_engine_links', 'yes' );

	foreach ( $engines as $eng ) {
		if ( ! is_scalar( $eng ) && ( ! is_object( $eng ) || ! method_exists( $eng, '__toString' ) ) ) {
			continue;
		}
		$eng_name = trim( (string) $eng );
		if ( empty( $eng_name ) ) continue;

		if ( 'yes' === $enable_links ) {
			// Cerca il termine nella tassonomia game_engine
			$term = get_term_by( 'name', $eng_name, 'game_engine' );
			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'slug', sanitize_title( $eng_name ), 'game_engine' );
			}

			if ( $term && ! is_wp_error( $term ) && $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				if ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) {
					$output_array[] = '<a href="' . esc_url( $link ) . '" class="' . esc_attr( $badge_class ) . '">' . esc_html( $eng_name ) . '</a>';
					continue;
				}
			}
		}

		$span_class     = trim( preg_replace( '/\bhover:[^\s]+/', '', $badge_class ) );
		$output_array[] = '<span class="' . esc_attr( $span_class ) . '">' . esc_html( $eng_name ) . '</span>';
	}

	return implode( ' ', $output_array );
}

/**
 * Data sotto la copertina nel catalogo (v3.19.1), secondo la precisione salvata dal plugin:
 * giorno "23.10.2026", mese "Ott 2027", trimestre "Q3 2027", altrimenti solo l'anno, come prima.
 * I giochi non ancora aggiornati dallo Scanner IGDB non hanno la precisione e mostrano l'anno.
 *
 * @param int   $post_id ID del videogioco.
 * @param mixed $release Data salvata (Y-m-d, anno, timestamp in secondi o millisecondi).
 * @return string Data da mostrare, oppure stringa vuota.
 */
function ntn_journal_game_card_date( $post_id, $release ): string {
	$rel_clean = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
	if ( '' === $rel_clean ) {
		return '';
	}

	$precision = function_exists( 'ntn_get_igdb_data' ) ? ntn_get_igdb_data( $post_id, '_ntn_game_release_precision' ) : get_post_meta( $post_id, '_ntn_game_release_precision', true );
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $rel_clean, $parts ) ) {
		$month = (int) $parts[2];
		if ( 'day' === $precision ) {
			return $parts[3] . '.' . $parts[2] . '.' . $parts[1];
		}
		if ( 'month' === $precision && $month >= 1 && $month <= 12 ) {
			$months = array( 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' );
			return $months[ $month - 1 ] . ' ' . $parts[1];
		}
		if ( 'quarter' === $precision && $month >= 1 && $month <= 12 ) {
			// IGDB può segnare il trimestre al primo o all'ultimo giorno: il mese basta a riconoscerlo
			return 'Q' . (int) ceil( $month / 3 ) . ' ' . $parts[1];
		}
	}

	if ( is_numeric( $rel_clean ) ) {
		if ( strlen( $rel_clean ) === 4 ) {
			return $rel_clean;
		}
		return date( 'Y', strlen( $rel_clean ) === 13 ? intval( $rel_clean / 1000 ) : intval( $rel_clean ) );
	}
	$rel_time = strtotime( $rel_clean );
	return $rel_time ? date( 'Y', $rel_time ) : $rel_clean;
}

/**
 * Data di uscita nelle schede del gioco (v3.21.0): solo le parti note.
 * Giorno "23/10/2026" (formato a scelta), mese "Ottobre 2027", trimestre "Q3 2027", anno "2028".
 * Senza precisione salvata (gioco non ancora aggiornato dallo Scanner IGDB) un 31 dicembre vale "solo l'anno",
 * perché è il segnaposto di IGDB; le altre date restano complete, come prima.
 *
 * @param mixed  $release    Data salvata (Y-m-d, anno, timestamp in secondi o millisecondi, testo).
 * @param mixed  $precision  Precisione salvata dal plugin (day, month, quarter, year, tbd), anche vuota.
 * @param string $day_format Formato con il giorno noto.
 * @return string
 */
function ntn_journal_release_label( $release, $precision = '', string $day_format = 'd/m/Y' ): string {
	$rel = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
	if ( '' === $rel ) {
		return '';
	}
	if ( is_numeric( $rel ) ) {
		if ( 4 === strlen( $rel ) ) {
			return $rel;
		}
		$time = 13 === strlen( $rel ) ? intval( $rel / 1000 ) : intval( $rel );
	} else {
		$date = date_create( $rel );
		if ( ! $date ) {
			return $rel;
		}
		$time = (int) $date->format( 'U' );
	}

	$precision = is_string( $precision ) ? $precision : '';
	if ( ! in_array( $precision, array( 'day', 'month', 'quarter', 'year' ), true ) ) {
		$precision = ( 'tbd' === $precision || '12-31' === date( 'm-d', $time ) ) ? 'year' : 'day';
	}
	$month = (int) date( 'n', $time );
	if ( 'day' === $precision ) {
		return date( $day_format, $time );
	}
	if ( 'month' === $precision ) {
		$months = array( 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre' );
		return $months[ $month - 1 ] . ' ' . date( 'Y', $time );
	}
	if ( 'quarter' === $precision ) {
		return 'Q' . (int) ceil( $month / 3 ) . ' ' . date( 'Y', $time );
	}
	return date( 'Y', $time );
}
