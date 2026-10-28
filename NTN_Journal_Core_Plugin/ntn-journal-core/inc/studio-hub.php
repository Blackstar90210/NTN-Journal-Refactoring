<?php
/**
 * NTN Journal - Hub dello studio (v3.21.0)
 *
 * Ogni tag il cui nome è lo sviluppatore IGDB di almeno un gioco del catalogo diventa la Hub dello studio,
 * sullo stesso indirizzo del tag (es. /tag/3dclouds/): niente da accendere a mano. Contano solo gli
 * sviluppatori, non gli editori. La pagina la disegna il tema (studio-hub.php); qui ci sono i dati,
 * la regola per Google e l'elenco dei giochi per i dati strutturati.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html, pagina "Hub studio".
 *
 * @package NTN_Journal_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chiave di confronto di un nome di studio: senza spazi, simboli, accenti e maiuscole ("3D Clouds" = "3DClouds").
 *
 * @param string $name Nome.
 * @return string
 */
function ntn_core_studio_key( string $name ): string {
	return strtolower( (string) preg_replace( '/[^a-z0-9]/i', '', remove_accents( wp_specialchars_decode( $name, ENT_QUOTES ) ) ) );
}

/**
 * Indice degli studi: chiave dello studio => ID dei tag dei giochi che ha sviluppato (dallo sviluppatore IGDB).
 * Una sola lettura dei tag, in memoria per un'ora.
 *
 * @return array<string, int[]>
 */
function ntn_core_studio_index(): array {
	$cached = get_transient( 'ntn_studio_index' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$index = array();
	$tags  = get_terms(
		array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => false,
			'meta_key'   => '_ntn_game_developer', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- risultato in memoria per un'ora.
		)
	);
	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$devs = get_term_meta( $tag->term_id, '_ntn_game_developer', true );
			$devs = function_exists( 'ntn_hub_names' ) ? ntn_hub_names( $devs ) : array_filter( array_map( 'trim', explode( ',', (string) $devs ) ) );
			foreach ( $devs as $dev ) {
				$key = ntn_core_studio_key( (string) $dev );
				if ( '' !== $key ) {
					$index[ $key ][] = (int) $tag->term_id;
				}
			}
		}
	}
	set_transient( 'ntn_studio_index', $index, HOUR_IN_SECONDS );
	return $index;
}

/**
 * Giochi del catalogo sviluppati dallo studio del tag (ID dei tag dei giochi, senza il tag stesso).
 *
 * @param WP_Term $tag Tag.
 * @return int[]
 */
function ntn_core_studio_game_tags( WP_Term $tag ): array {
	if ( 'post_tag' !== $tag->taxonomy || get_term_meta( $tag->term_id, '_ntn_igdb_game_id', true ) ) {
		return array(); // Un tag di un gioco porta alla Hub del gioco, non è uno studio
	}
	$index = ntn_core_studio_index();
	$key   = ntn_core_studio_key( (string) $tag->name );
	$ids   = isset( $index[ $key ] ) ? $index[ $key ] : array();
	return array_values( array_unique( array_diff( array_map( 'intval', $ids ), array( (int) $tag->term_id ) ) ) );
}

/**
 * Hub "Sviluppatori italiani" (o altra Hub Studi) che elenca lo studio, per l'etichetta e il percorso.
 *
 * @param WP_Term $tag Tag dello studio.
 * @return array{label:string, url:string}|null
 */
function ntn_core_studio_parent_hub( WP_Term $tag ): ?array {
	if ( ! function_exists( 'ntn_core_category_hubs' ) || ! function_exists( 'ntn_core_get_category_hub_data' ) ) {
		return null;
	}
	$key = ntn_core_studio_key( (string) $tag->name );
	foreach ( ntn_core_category_hubs() as $hub ) {
		if ( 'studi' !== ntn_core_category_hub_meta( (int) $hub->term_id, 'type' ) ) {
			continue;
		}
		$data = ntn_core_get_category_hub_data( $hub );
		foreach ( ( $data['studios'] ?? array() ) as $studio ) {
			if ( ntn_core_studio_key( (string) $studio['name'] ) === $key ) {
				return array( 'label' => (string) $data['title'], 'url' => (string) $data['root']['url'] );
			}
		}
	}
	return null;
}

/**
 * Dati della Hub dello studio.
 *
 * @param WP_Term|null $tag Tag (null = tag corrente).
 * @return array|null Null se il tag non è uno studio.
 */
function ntn_core_get_studio_hub_data( $tag = null ): ?array {
	if ( null === $tag ) {
		$tag = is_tag() ? get_queried_object() : null;
	}
	if ( ! $tag instanceof WP_Term ) {
		return null;
	}
	$game_tags = ntn_core_studio_game_tags( $tag );
	if ( empty( $game_tags ) ) {
		return null;
	}

	$cache_key = 'ntn_studio_hub_' . md5( $tag->term_id . '|' . $tag->count . '|' . implode( ',', $game_tags ) . '|' . get_option( 'ntn_cat_hub_version', '0' ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$games = array();
	foreach ( $game_tags as $game_tag_id ) {
		$game_tag = get_term( $game_tag_id, 'post_tag' );
		if ( ! $game_tag instanceof WP_Term ) {
			continue;
		}
		$hub_posts = get_posts( array( 'name' => $game_tag->slug, 'post_type' => 'videogioco', 'post_status' => 'publish', 'numberposts' => 1 ) );
		$hub_id    = ! empty( $hub_posts ) ? (int) $hub_posts[0]->ID : 0;
		$meta      = static function ( $key ) use ( $hub_id, $game_tag ) {
			$value = $hub_id ? get_post_meta( $hub_id, $key, true ) : '';
			return '' !== (string) $value ? $value : get_term_meta( $game_tag->term_id, $key, true );
		};
		$cover = (string) $meta( '_ntn_game_cover' );
		if ( '' !== $cover && function_exists( 'ntn_normalize_game_cover_url' ) ) {
			$cover = (string) ntn_normalize_game_cover_url( $cover );
		}
		$url     = $hub_id ? get_permalink( $hub_id ) : get_term_link( $game_tag );
		$games[] = array(
			'title'     => trim( wp_strip_all_tags( wp_specialchars_decode( $game_tag->name, ENT_QUOTES ) ) ),
			'url'       => is_string( $url ) ? $url : '',
			'hub_id'    => $hub_id,
			'cover'     => $cover,
			'release'   => (string) $meta( '_ntn_game_release' ),
			'precision' => (string) $meta( '_ntn_game_release_precision' ),
			'articles'  => (int) $game_tag->count,
		);
	}
	if ( empty( $games ) ) {
		return null;
	}
	// Dal gioco più recente; quelli senza data in fondo
	usort( $games, static function ( $a, $b ) {
		if ( $a['release'] === $b['release'] ) {
			return strnatcasecmp( $a['title'], $b['title'] );
		}
		if ( '' === $a['release'] || '' === $b['release'] ) {
			return '' === $a['release'] ? 1 : -1;
		}
		return strcmp( $b['release'], $a['release'] );
	} );

	$latest = get_posts( array( 'tag_id' => $tag->term_id, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
	$name   = trim( wp_strip_all_tags( wp_specialchars_decode( $tag->name, ENT_QUOTES ) ) );
	$data   = array(
		'name'        => $name,
		'url'         => (string) get_term_link( $tag ),
		'description' => '' !== trim( (string) $tag->description ) ? trim( wp_strip_all_tags( (string) $tag->description ) ) : ( function_exists( 'ntn_hub_tag_seo_description' ) ? ntn_hub_tag_seo_description( $tag ) : '' ),
		'parent'      => ntn_core_studio_parent_hub( $tag ),
		'games'       => $games,
		'stats'       => array(
			array( 'Giochi', (string) count( $games ) ),
			array( 'Articoli', (string) (int) $tag->count ),
			array( 'Ultimo articolo', ! empty( $latest ) ? (string) get_the_date( 'd.m.Y', $latest[0] ) : '' ),
		),
	);
	set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

/**
 * Lo studio ha abbastanza giochi nel catalogo per essere visibile a Google anche con pochi articoli?
 * (Regola della Hub dello studio: almeno 2 giochi oppure la soglia di articoli di tag e categorie.)
 *
 * @param WP_Term $tag Tag.
 * @return bool
 */
function ntn_core_studio_has_enough_games( WP_Term $tag ): bool {
	return count( ntn_core_studio_game_tags( $tag ) ) >= 2;
}

/**
 * Dati per Google: elenco dei giochi dello studio (si spegne con il freno d'emergenza dello Schema Markup).
 */
function ntn_core_studio_hub_schema(): void {
	if ( ! is_tag() || get_option( 'ntn_seo_enable_review_schema', 'yes' ) !== 'yes' ) {
		return;
	}
	$data = ntn_core_get_studio_hub_data();
	if ( ! $data ) {
		return;
	}
	$items = array();
	foreach ( $data['games'] as $i => $game ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'item'     => array( '@type' => 'VideoGame', 'name' => $game['title'] ),
		);
		if ( '' !== $game['url'] ) {
			$item['item']['url'] = $game['url'];
		}
		$items[] = $item;
	}
	$list = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => $data['name'] . ' – giochi dello studio',
		'numberOfItems'   => count( $items ),
		'itemListElement' => $items,
	);
	echo '<!-- SEO: Schema Markup della Hub dello studio -->';
	echo '<script type="application/ld+json">' . wp_json_encode( $list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'ntn_core_studio_hub_schema', 99 );

/* ==========================================================================
   Google e condivisioni (v3.22.3): percorso, sitemap e immagine della Hub dello studio
   ========================================================================== */

/**
 * Tag dello studio a cui si riferiscono gli argomenti di The SEO Framework (null = pagina corrente).
 *
 * @param array|null $args Argomenti di TSF ('id', 'tax').
 * @return WP_Term|null Null se non è la Hub di uno studio.
 */
function ntn_core_studio_term_from_tsf_args( $args ): ?WP_Term {
	if ( null === $args ) {
		$term = is_tag() ? get_queried_object() : null;
	} elseif ( is_array( $args ) && ! empty( $args['id'] ) && 'post_tag' === ( $args['tax'] ?? ( $args['taxonomy'] ?? '' ) ) ) {
		$term = get_term( (int) $args['id'], 'post_tag' );
	} else {
		$term = null;
	}
	return ( $term instanceof WP_Term && ntn_core_get_studio_hub_data( $term ) ) ? $term : null;
}

/**
 * Percorso per Google: TSF scrive "Q-Gin > 3DClouds", la pagina mostra Home // Sviluppatori italiani // 3DClouds.
 * Aggiunge la Hub che elenca lo studio dopo la Home e rinumera le voci.
 *
 * @param array $items  Voci ListItem del BreadcrumbList.
 * @param array $parent Hub dello studio ('label', 'url').
 * @return array
 */
function ntn_core_studio_breadcrumb_items( array $items, array $parent ): array {
	if ( count( $items ) < 2 ) {
		return $items;
	}
	foreach ( $items as $item ) {
		if ( is_array( $item ) && isset( $item['item'] ) && $item['item'] === $parent['url'] ) {
			return $items; // Già presente
		}
	}
	array_splice( $items, 1, 0, array( array(
		'@type'    => 'ListItem',
		'position' => 2,
		'item'     => $parent['url'],
		'name'     => wp_strip_all_tags( $parent['label'] ),
	) ) );
	foreach ( $items as $i => $item ) {
		if ( is_array( $item ) ) {
			$items[ $i ]['position'] = $i + 1;
		}
	}
	return $items;
}

/**
 * The SEO Framework: percorso della Hub dello studio nei dati strutturati, sia come voce a sé sia dentro la pagina.
 *
 * @param array      $graph Entità del grafo di TSF.
 * @param array|null $args  Argomenti di TSF.
 * @return array
 */
function ntn_core_studio_tsf_breadcrumb( $graph, $args = null ) {
	$term = is_array( $graph ) ? ntn_core_studio_term_from_tsf_args( null ) : null;
	$data = $term ? ntn_core_get_studio_hub_data( $term ) : null;
	if ( ! $data || empty( $data['parent'] ) ) {
		return $graph;
	}
	foreach ( $graph as $key => $entity ) {
		if ( ! is_array( $entity ) ) {
			continue;
		}
		if ( 'BreadcrumbList' === ( $entity['@type'] ?? '' ) && ! empty( $entity['itemListElement'] ) && is_array( $entity['itemListElement'] ) ) {
			$graph[ $key ]['itemListElement'] = ntn_core_studio_breadcrumb_items( $entity['itemListElement'], $data['parent'] );
		} elseif ( isset( $entity['breadcrumb']['itemListElement'] ) && is_array( $entity['breadcrumb']['itemListElement'] ) ) {
			$graph[ $key ]['breadcrumb']['itemListElement'] = ntn_core_studio_breadcrumb_items( $entity['breadcrumb']['itemListElement'], $data['parent'] );
		}
	}
	return $graph;
}
add_filter( 'the_seo_framework_schema_graph_data', 'ntn_core_studio_tsf_breadcrumb', 20, 2 );

/**
 * Sitemap di The SEO Framework: TSF non elenca i tag, quindi aggiungiamo le Hub degli studi visibili a Google
 * (fuori quelle sotto la soglia di seo-thin-pages.php), con la data dell'ultimo articolo.
 *
 * @param array $custom_urls URL aggiuntivi, per indirizzo.
 * @return array
 */
function ntn_core_studio_hub_sitemap_urls( $custom_urls ) {
	$custom_urls = is_array( $custom_urls ) ? $custom_urls : array();
	if ( empty( ntn_core_studio_index() ) ) {
		return $custom_urls;
	}
	$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true ) );
	if ( ! is_array( $tags ) ) {
		return $custom_urls;
	}
	foreach ( $tags as $tag ) {
		if ( empty( ntn_core_studio_game_tags( $tag ) ) ) {
			continue; // Non è uno studio (o è il tag di un gioco)
		}
		if ( function_exists( 'ntn_core_seo_is_thin_term' ) && ntn_core_seo_is_thin_term( $tag ) ) {
			continue;
		}
		$url = get_term_link( $tag );
		if ( ! is_string( $url ) ) {
			continue;
		}
		$latest = get_posts( array( 'tag_id' => $tag->term_id, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'no_found_rows' => true ) );
		$custom_urls[ $url ] = array( 'lastmod' => ! empty( $latest ) ? (string) get_post_field( 'post_modified_gmt', $latest[0] ) : '' );
	}
	return $custom_urls;
}
add_filter( 'the_seo_framework_sitemap_additional_urls', 'ntn_core_studio_hub_sitemap_urls' );

/**
 * Immagine per le condivisioni della Hub dello studio: quella dell'ultimo articolo con un'immagine in evidenza.
 *
 * @param WP_Term $tag Tag dello studio.
 * @return array{url:string, id:int} URL vuoto se nessun articolo ha un'immagine.
 */
function ntn_core_studio_hub_social_image( WP_Term $tag ): array {
	static $cache = array();
	if ( isset( $cache[ $tag->term_id ] ) ) {
		return $cache[ $tag->term_id ];
	}
	$image = array( 'url' => '', 'id' => 0 );
	$ids   = get_posts( array(
		'tag_id'        => $tag->term_id,
		'post_status'   => 'publish',
		'numberposts'   => 1,
		'fields'        => 'ids',
		'no_found_rows' => true,
		'meta_key'      => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- un solo articolo, solo quelli con immagine.
	) );
	if ( ! empty( $ids ) ) {
		$thumb_id = (int) get_post_thumbnail_id( $ids[0] );
		$url      = $thumb_id ? (string) wp_get_attachment_image_url( $thumb_id, 'full' ) : '';
		if ( '' !== $url ) {
			$image = array( 'url' => $url, 'id' => $thumb_id );
		}
	}
	$cache[ $tag->term_id ] = $image;
	return $image;
}

/**
 * Generatore di immagini per The SEO Framework: la Hub dello studio usa l'immagine del suo ultimo articolo.
 *
 * @param array|null $args Argomenti di TSF.
 * @param string     $size Dimensione richiesta.
 * @return Generator
 */
function ntn_core_studio_hub_tsf_image_generator( $args = null, $size = 'full' ) {
	$term  = ntn_core_studio_term_from_tsf_args( $args );
	$image = $term ? ntn_core_studio_hub_social_image( $term ) : array( 'url' => '' );
	if ( '' !== $image['url'] ) {
		yield $image;
	}
}

/**
 * Mette il generatore della Hub dello studio davanti agli altri (quello di seo-extras.php per i tag dà il logo).
 *
 * @param array      $params  Parametri di generazione di TSF.
 * @param array|null $args    Argomenti di TSF.
 * @param string     $context Contesto.
 * @return array
 */
function ntn_core_studio_hub_tsf_image_params( $params, $args = null, $context = 'social' ) {
	if ( ! is_array( $params ) || ! ntn_core_studio_term_from_tsf_args( $args ) ) {
		return $params;
	}
	$cbs           = isset( $params['cbs'] ) && is_array( $params['cbs'] ) ? $params['cbs'] : array();
	$params['cbs'] = array( 'ntn_studio_hub' => 'ntn_core_studio_hub_tsf_image_generator' ) + $cbs;
	return $params;
}
add_filter( 'the_seo_framework_image_generation_params', 'ntn_core_studio_hub_tsf_image_params', 11, 3 );
