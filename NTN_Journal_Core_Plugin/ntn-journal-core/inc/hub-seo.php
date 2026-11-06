<?php
/**
 * NTN Journal - SEO delle Hub Page dei videogiochi (v3.11.0)
 *
 * - Testo di presentazione automatico della Hub, costruito dai dati IGDB.
 * - Entità VideoGame e pagina di raccolta (CollectionPage) per i dati strutturati.
 * - Canonical che punta a sé stessa anche sulle pagine successive della Hub.
 * - Sitemap di WordPress senza i tag che rimandano alle Hub e senza i comunicati in noindex.
 * - Titolo per Google e collegamento a The SEO Framework (titolo e descrizione automatici).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converte la data di uscita IGDB (anno, timestamp in secondi o millisecondi, data testuale) in timestamp.
 *
 * @param mixed $release Valore grezzo della data di uscita.
 * @return int Timestamp, oppure 0 se la data non è leggibile.
 */
function ntn_hub_release_timestamp( $release ): int {
	$rel = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
	if ( '' === $rel ) {
		return 0;
	}
	if ( is_numeric( $rel ) ) {
		if ( strlen( $rel ) === 4 ) {
			return (int) mktime( 0, 0, 0, 1, 1, (int) $rel );
		}
		return strlen( $rel ) === 13 ? (int) ( (int) $rel / 1000 ) : (int) $rel;
	}
	$time = strtotime( $rel );
	return $time ? (int) $time : 0;
}

/**
 * Precisione della data di uscita (v3.19.2): quella salvata dallo Scanner IGDB (day, month, quarter, year,
 * tbd). Se manca (gioco non ancora aggiornato), un 31 dicembre è il segnaposto di IGDB per "solo l'anno".
 *
 * @param mixed  $release   Data grezza.
 * @param string $precision Precisione salvata, anche vuota.
 * @return string day|month|quarter|year
 */
function ntn_release_effective_precision( $release, $precision ): string {
	$precision = is_string( $precision ) ? $precision : '';
	if ( in_array( $precision, array( 'day', 'month', 'quarter', 'year' ), true ) ) {
		return $precision;
	}
	if ( 'tbd' === $precision ) {
		return 'year';
	}
	$rel = is_scalar( $release ) ? trim( (string) $release ) : '';
	if ( 4 === strlen( $rel ) && is_numeric( $rel ) ) {
		return 'year';
	}
	$time = ntn_hub_release_timestamp( $release );
	return ( $time && '12-31' === gmdate( 'm-d', $time ) ) ? 'year' : 'day';
}

/**
 * Data di uscita per i dati strutturati, solo con le parti note: "2026-10-23", "2027-10" o "2027".
 *
 * @param mixed  $release   Data grezza.
 * @param string $precision Precisione salvata.
 * @return string Vuota se la data non è leggibile.
 */
function ntn_release_schema_date( $release, $precision ): string {
	$time = ntn_hub_release_timestamp( $release );
	if ( ! $time ) {
		return '';
	}
	$effective = ntn_release_effective_precision( $release, $precision );
	if ( 'day' === $effective ) {
		return gmdate( 'Y-m-d', $time );
	}
	return 'month' === $effective ? gmdate( 'Y-m', $time ) : gmdate( 'Y', $time );
}

/**
 * Data di uscita a parole, con "in uscita" o "uscito": "in uscita il 23/10/2026", "uscito a ottobre 2025",
 * "in uscita nel terzo trimestre del 2027", "in uscita nel 2028".
 *
 * @param mixed  $release   Data grezza.
 * @param string $precision Precisione salvata.
 * @return string Vuota se la data non è leggibile.
 */
function ntn_release_phrase( $release, $precision ): string {
	$time = ntn_hub_release_timestamp( $release );
	if ( ! $time ) {
		return '';
	}
	$effective = ntn_release_effective_precision( $release, $precision );
	$year      = (int) gmdate( 'Y', $time );
	$month     = (int) gmdate( 'n', $time );

	if ( 'day' === $effective ) {
		$end  = $time;
		$when = 'il ' . gmdate( 'd/m/Y', $time );
	} elseif ( 'month' === $effective ) {
		$months = array( 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre' );
		$end    = (int) gmmktime( 23, 59, 59, $month + 1, 0, $year );
		$when   = 'a ' . $months[ $month - 1 ] . ' ' . $year;
	} elseif ( 'quarter' === $effective ) {
		$quarter = (int) ceil( $month / 3 );
		$names   = array( 1 => 'primo', 2 => 'secondo', 3 => 'terzo', 4 => 'quarto' );
		$end     = (int) gmmktime( 23, 59, 59, $quarter * 3 + 1, 0, $year );
		$when    = 'nel ' . $names[ $quarter ] . ' trimestre del ' . $year;
	} else {
		$end  = (int) gmmktime( 23, 59, 59, 12, 31, $year );
		$when = 'nel ' . $year;
	}
	return ( $end > time() ? 'in uscita ' : 'uscito ' ) . $when;
}

/**
 * Riduce un valore IGDB (stringa o elenco) a un elenco di nomi puliti.
 *
 * @param mixed $value Valore grezzo.
 * @return array<int, string>
 */
function ntn_hub_names( $value ): array {
	$list = is_array( $value ) ? $value : ( is_scalar( $value ) ? explode( ',', (string) $value ) : array() );
	$names = array();
	foreach ( $list as $item ) {
		if ( is_scalar( $item ) ) {
			$name = trim( wp_strip_all_tags( wp_specialchars_decode( (string) $item, ENT_QUOTES ) ) );
			if ( '' !== $name ) {
				$names[] = $name;
			}
		}
	}
	return array_values( array_unique( $names ) );
}

/**
 * Unisce un elenco di nomi in italiano: "A", "A e B", "A, B e C".
 *
 * @param array<int, string> $names Nomi.
 * @return string
 */
function ntn_hub_join_names( array $names ): string {
	if ( count( $names ) < 2 ) {
		return (string) reset( $names );
	}
	$last = array_pop( $names );
	return implode( ', ', $names ) . ' e ' . $last;
}

/**
 * Dati IGDB della Hub, se il tema li rende disponibili.
 *
 * @param int $post_id ID della Hub.
 * @return array<string, mixed>
 */
function ntn_hub_seo_info( int $post_id ): array {
	$info = ntn_get_hub_igdb_info( $post_id );
	return is_array( $info ) ? $info : array();
}

/**
 * Testo di presentazione automatico della Hub, per la pagina, la meta description e i dati strutturati.
 *
 * Esempio: "Ace Combat 8 è un videogioco sviluppato da Bandai Namco Aces e pubblicato da Bandai Namco,
 * in uscita il 15/03/2027 su PC, PlayStation 5 e Xbox Series X|S. Qui trovi tutte le recensioni,
 * le anteprime e le notizie di NTN Journal sul gioco."
 *
 * @param int $post_id ID della Hub.
 * @return string Testo semplice, senza HTML. Vuoto se manca il titolo.
 */
function ntn_get_hub_auto_summary( int $post_id ): string {
	$info  = ntn_hub_seo_info( $post_id );
	$title = ! empty( $info['title'] ) && is_scalar( $info['title'] ) ? (string) $info['title'] : get_the_title( $post_id );
	$title = trim( wp_strip_all_tags( wp_specialchars_decode( $title, ENT_QUOTES ) ) );
	if ( '' === $title ) {
		return '';
	}

	$sentence = $title . ' è un videogioco';

	$developers = ntn_hub_names( $info['developer'] ?? array() );
	$publishers = array_values( array_diff( ntn_hub_names( $info['publisher'] ?? array() ), $developers ) );
	if ( $developers ) {
		$sentence .= ' sviluppato da ' . ntn_hub_join_names( array_slice( $developers, 0, 2 ) );
	}
	if ( $publishers ) {
		$sentence .= ( $developers ? ' e pubblicato da ' : ' pubblicato da ' ) . ntn_hub_join_names( array_slice( $publishers, 0, 2 ) );
	}

	// v3.19.2: solo le parti note della data ("nel 2028", non "il 31/12/2028")
	$release = ntn_release_phrase( $info['release'] ?? '', (string) get_post_meta( $post_id, '_ntn_game_release_precision', true ) );
	if ( '' !== $release ) {
		$sentence .= ', ' . $release;
	}

	$platforms = array_slice( ntn_hub_names( $info['platforms'] ?? array() ), 0, 4 );
	if ( $platforms ) {
		$sentence .= ' su ' . ntn_hub_join_names( $platforms );
	}

	$site = trim( wp_strip_all_tags( (string) get_bloginfo( 'name' ) ) );

	return $sentence . '. Qui trovi tutte le recensioni, le anteprime e le notizie' . ( '' !== $site ? ' di ' . $site : '' ) . ' sul gioco.';
}

/**
 * Entità VideoGame della Hub per i dati strutturati.
 *
 * @param int $post_id ID della Hub.
 * @return array<string, mixed>
 */
function ntn_get_hub_videogame_entity( int $post_id ): array {
	$info  = ntn_hub_seo_info( $post_id );
	$title = ! empty( $info['title'] ) && is_scalar( $info['title'] ) ? (string) $info['title'] : get_the_title( $post_id );

	$entity = array(
		'@type'               => 'VideoGame',
		'@id'                 => get_permalink( $post_id ) . '#videogame',
		'name'                => trim( wp_strip_all_tags( wp_specialchars_decode( $title, ENT_QUOTES ) ) ),
		'url'                 => get_permalink( $post_id ),
		'applicationCategory' => 'Game',
	);

	$igdb_link = ! empty( $info['igdb_url'] ) ? $info['igdb_url'] : ( ! empty( $info['url'] ) ? $info['url'] : '' );
	if ( is_string( $igdb_link ) && false !== strpos( $igdb_link, 'igdb.com' ) ) {
		$entity['sameAs'] = esc_url_raw( $igdb_link );
	}

	if ( ! empty( $info['cover'] ) && is_string( $info['cover'] ) ) {
		$cover = function_exists( 'ntn_normalize_game_cover_url' ) ? ntn_normalize_game_cover_url( $info['cover'] ) : $info['cover'];
		$entity['image'] = esc_url_raw( $cover );
	}

	$developers = ntn_hub_names( $info['developer'] ?? array() );
	if ( $developers ) {
		$entity['author'] = array_map( static function ( $name ) {
			return array( '@type' => 'Organization', 'name' => $name );
		}, array_slice( $developers, 0, 3 ) );
	}
	$publishers = ntn_hub_names( $info['publisher'] ?? array() );
	if ( $publishers ) {
		$entity['publisher'] = array( '@type' => 'Organization', 'name' => $publishers[0] );
	}

	$genres = ntn_hub_names( $info['genres'] ?? array() );
	if ( $genres ) {
		$entity['genre'] = $genres;
	}
	$platforms = ntn_core_schema_platform_names( ntn_hub_names( $info['platforms'] ?? array() ) ); // v4.14.0: "PC (Microsoft Windows)" diventa "PC", come nelle recensioni
	if ( $platforms ) {
		$entity['gamePlatform'] = $platforms;
	}

	$release = ntn_release_schema_date( $info['release'] ?? '', (string) get_post_meta( $post_id, '_ntn_game_release_precision', true ) );
	if ( '' !== $release ) {
		$entity['datePublished'] = $release;
	}

	$summary = ntn_get_hub_auto_summary( $post_id );
	if ( '' !== $summary ) {
		$entity['description'] = $summary;
	}

	return $entity;
}

/**
 * Dati strutturati della Hub senza plugin SEO: pagina di raccolta che parla del videogioco.
 * Con The SEO Framework attivo, lo stesso lavoro lo fa ntn_tsf_add_videogame_schema().
 */
function ntn_hub_collection_schema(): void {
	if ( ! is_singular( 'videogioco' ) || get_option( 'ntn_seo_enable_review_schema', 'yes' ) !== 'yes' ) {
		return;
	}
	if ( defined( 'THE_SEO_FRAMEWORK_PRESENT' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || ( function_exists( 'the_seo_framework' ) && the_seo_framework() ) ) {
		return;
	}

	$post_id = (int) get_queried_object_id();
	$game    = ntn_get_hub_videogame_entity( $post_id );
	$url     = (string) wp_get_canonical_url( $post_id );

	$page = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'CollectionPage',
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => $game['name'],
		'isPartOf'   => array(
			'@type' => 'WebSite',
			'name'  => wp_strip_all_tags( (string) get_bloginfo( 'name' ) ),
			'url'   => home_url( '/' ),
		),
		'about'      => array( '@id' => $game['@id'] ),
		'mainEntity' => $game,
	);
	if ( ! empty( $game['description'] ) ) {
		$page['description'] = $game['description'];
	}
	if ( ! empty( $game['image'] ) ) {
		$page['primaryImageOfPage'] = array( '@type' => 'ImageObject', 'url' => $game['image'] );
	}

	echo '<!-- SEO: Schema Markup della Hub -->';
	echo '<script type="application/ld+json">' . wp_json_encode( $page, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'ntn_hub_collection_schema', 99 );

/**
 * Canonical delle pagine successive della Hub: /videogioco/gioco/page/2/ punta a sé stessa,
 * così Google non trascura gli articoli più vecchi elencati da pagina 2 in poi.
 *
 * @param string  $canonical_url URL canonico calcolato da WordPress.
 * @param WP_Post $post          Post corrente.
 * @return string
 */
function ntn_hub_paged_canonical( $canonical_url, $post ) {
	if ( ! is_singular( 'videogioco' ) || ! is_string( $canonical_url ) ) {
		return $canonical_url;
	}
	$paged = (int) get_query_var( 'paged' );
	if ( $paged > 1 ) {
		global $wp_rewrite;
		$base          = ( $wp_rewrite && ! empty( $wp_rewrite->pagination_base ) ) ? $wp_rewrite->pagination_base : 'page';
		$canonical_url = user_trailingslashit( trailingslashit( $canonical_url ) . $base . '/' . $paged );
	}
	return $canonical_url;
}
add_filter( 'get_canonical_url', 'ntn_hub_paged_canonical', 10, 2 );

/**
 * Sitemap di WordPress: toglie i tag collegati a IGDB, che rimandano alla Hub con un 301.
 * (La sitemap di The SEO Framework è già gestita in igdb-seo-routing.php.)
 *
 * @param array  $args     Argomenti della query dei termini.
 * @param string $taxonomy Tassonomia.
 * @return array
 */
function ntn_hub_sitemap_exclude_igdb_tags( $args, $taxonomy ) {
	if ( 'post_tag' !== $taxonomy || ! is_array( $args ) ) {
		return $args;
	}
	$meta_query         = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta_query[]       = array(
		'key'     => '_ntn_igdb_game_id',
		'compare' => 'NOT EXISTS',
	);
	$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'ntn_hub_sitemap_exclude_igdb_tags', 10, 2 );

/**
 * Sitemap di WordPress: toglie i comunicati stampa segnati come noindex.
 *
 * @param array  $args      Argomenti della query dei contenuti.
 * @param string $post_type Tipo di contenuto.
 * @return array
 */
function ntn_hub_sitemap_exclude_noindex_press( $args, $post_type ) {
	if ( 'comunicato_stampa' !== $post_type || ! is_array( $args ) ) {
		return $args;
	}
	$meta_query         = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta_query[]       = array(
		'relation' => 'OR',
		array(
			'key'     => 'ntn_press_noindex',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'ntn_press_noindex',
			'value'   => '1',
			'compare' => '!=',
		),
	);
	$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'ntn_hub_sitemap_exclude_noindex_press', 10, 2 );

/**
 * La Hub ha almeno una recensione? (tag del gioco nella categoria Recensioni, in cache per un'ora)
 *
 * @param int $post_id ID della Hub.
 * @return bool
 */
function ntn_hub_has_review( int $post_id ): bool {
	$cached = get_transient( 'ntn_hub_has_review_' . $post_id );
	if ( false !== $cached ) {
		return '1' === $cached;
	}
	$slug = get_post_field( 'post_name', $post_id );
	$tag  = $slug ? get_term_by( 'slug', $slug, 'post_tag' ) : false;
	$has  = false;
	if ( $tag instanceof WP_Term ) {
		$query = new WP_Query( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'relation' => 'AND',
				array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tag->term_id ),
				array( 'taxonomy' => 'category', 'field' => 'slug', 'terms' => array( 'recensioni', 'recensione' ) ),
			),
		) );
		$has = ! empty( $query->posts );
	}
	set_transient( 'ntn_hub_has_review_' . $post_id, $has ? '1' : '0', HOUR_IN_SECONDS );
	return $has;
}

/**
 * Titolo della Hub per Google e per la scheda del browser (il titolo H1 nella pagina non cambia).
 * Esempio: "Ace Combat 8: Wings of Theve – Recensione, notizie e scheda".
 *
 * @param int $post_id ID della Hub.
 * @return string
 */
function ntn_get_hub_seo_title( int $post_id ): string {
	$title = trim( wp_strip_all_tags( wp_specialchars_decode( get_the_title( $post_id ), ENT_QUOTES ) ) );
	if ( '' === $title ) {
		return '';
	}
	return $title . ' – ' . ( ntn_hub_has_review( $post_id ) ? 'Recensione, notizie e scheda' : 'Notizie e scheda del gioco' );
}

/**
 * ID della Hub a cui si riferiscono gli argomenti di The SEO Framework (null = pagina corrente).
 *
 * @param array|null $args Argomenti di TSF.
 * @return int ID della Hub, oppure 0 se non è una Hub.
 */
function ntn_hub_id_from_tsf_args( $args ): int {
	if ( null === $args ) {
		return is_singular( 'videogioco' ) ? (int) get_queried_object_id() : 0;
	}
	if ( is_array( $args ) && ! empty( $args['id'] ) && empty( $args['tax'] ) && empty( $args['taxonomy'] ) && empty( $args['pta'] ) ) {
		return 'videogioco' === get_post_type( (int) $args['id'] ) ? (int) $args['id'] : 0;
	}
	return 0;
}

/**
 * Titolo della Hub senza plugin SEO.
 *
 * @param array $parts Parti del titolo del documento.
 * @return array
 */
function ntn_hub_document_title_parts( $parts ) {
	if ( is_singular( 'videogioco' ) && is_array( $parts ) ) {
		$hub_title = ntn_get_hub_seo_title( (int) get_queried_object_id() );
		if ( '' !== $hub_title ) {
			$parts['title'] = $hub_title;
		}
	}
	return $parts;
}
add_filter( 'document_title_parts', 'ntn_hub_document_title_parts' );

/**
 * The SEO Framework (v5+): titolo automatico della Hub. Un titolo scritto a mano nel riquadro di TSF ha sempre la precedenza.
 *
 * @param string     $title Titolo generato da TSF.
 * @param array|null $args  Argomenti di TSF.
 * @return string
 */
function ntn_hub_tsf_title( $title, $args = null ) {
	$hub_id = ntn_hub_id_from_tsf_args( $args );
	if ( $hub_id ) {
		$hub_title = ntn_get_hub_seo_title( $hub_id );
		if ( '' !== $hub_title ) {
			// TSF numera solo le pagine degli articoli divisi: per le pagine successive della Hub aggiungiamo noi "Pagina N"
			$paged = null === $args ? (int) get_query_var( 'paged' ) : 0;
			return $paged > 1 ? $hub_title . ' – Pagina ' . $paged : $hub_title;
		}
	}
	return $title;
}
add_filter( 'the_seo_framework_title_from_generation', 'ntn_hub_tsf_title', 10, 2 );

/**
 * The SEO Framework (v5+): testo da cui TSF ricava la descrizione automatica della Hub, se la Hub non ha un testo proprio.
 * Una descrizione scritta a mano nel riquadro di TSF ha sempre la precedenza.
 *
 * @param string     $excerpt Testo usato da TSF.
 * @param array|null $args    Argomenti di TSF.
 * @return string
 */
function ntn_hub_tsf_description_excerpt( $excerpt, $args = null ) {
	$hub_id = ntn_hub_id_from_tsf_args( $args );
	if ( $hub_id && '' === trim( (string) $excerpt ) ) {
		$summary = ntn_get_hub_auto_summary( $hub_id );
		if ( '' !== $summary ) {
			return $summary;
		}
	}
	return $excerpt;
}
add_filter( 'the_seo_framework_description_excerpt', 'ntn_hub_tsf_description_excerpt', 10, 2 );

/**
 * Giochi del catalogo sviluppati da uno studio, cercati per nome nello sviluppatore IGDB dei tag (v3.20.0).
 *
 * @param string $studio Nome dello studio, come il tag (es. "3DClouds").
 * @param int    $limit  Quanti nomi al massimo.
 * @return array<int, string> Nomi dei giochi.
 */
function ntn_hub_studio_games( string $studio, int $limit = 3 ): array {
	$studio = trim( $studio );
	if ( '' === $studio ) {
		return array();
	}
	$key    = strtolower( preg_replace( '/[^a-z0-9]/i', '', $studio ) );
	$cached = get_transient( 'ntn_studio_games_' . md5( $key ) );
	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}
	$tags  = get_terms(
		array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => true,
			'number'     => 20,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- risultato in cache per un giorno.
				array(
					'key'     => '_ntn_game_developer',
					'value'   => $studio,
					'compare' => 'LIKE',
				),
			),
		)
	);
	$games = array();
	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			// Lo studio deve essere uno degli sviluppatori, non solo una parte del nome (es. "Clouds" in "3DClouds")
			foreach ( ntn_hub_names( get_term_meta( $tag->term_id, '_ntn_game_developer', true ) ) as $dev ) {
				if ( strtolower( preg_replace( '/[^a-z0-9]/i', '', $dev ) ) === $key ) {
					$games[] = trim( wp_strip_all_tags( wp_specialchars_decode( $tag->name, ENT_QUOTES ) ) );
					break;
				}
			}
		}
	}
	$games = array_values( array_unique( $games ) );
	set_transient( 'ntn_studio_games_' . md5( $key ), $games, DAY_IN_SECONDS );
	return array_slice( $games, 0, $limit );
}

/**
 * Descrizione per Google delle pagine dei tag senza descrizione (v3.20.0). Gli studi citano i loro giochi:
 * "3DClouds: tutte le notizie, le anteprime e le recensioni di NTN Journal sullo studio e sui suoi giochi, tra cui AfterZero: The Last Drive."
 * Gli altri tag: "Tutti gli articoli di NTN Journal su Nintendo Direct: notizie, anteprime e recensioni."
 * I tag dei giochi collegati a IGDB non servono: portano alla Hub del gioco.
 *
 * @param WP_Term $tag Tag.
 * @return string
 */
function ntn_hub_tag_seo_description( WP_Term $tag ): string {
	$name = trim( wp_strip_all_tags( wp_specialchars_decode( $tag->name, ENT_QUOTES ) ) );
	$site = trim( wp_strip_all_tags( (string) get_bloginfo( 'name' ) ) );
	$of   = '' !== $site ? ' di ' . $site : '';
	if ( '' === $name ) {
		return '';
	}
	$games = ntn_hub_studio_games( $name );
	if ( $games ) {
		return $name . ': tutte le notizie, le anteprime e le recensioni' . $of . ' sullo studio e sui suoi giochi, tra cui ' . ntn_hub_join_names( $games ) . '.';
	}
	return 'Tutti gli articoli' . $of . ' su ' . $name . ': notizie, anteprime e recensioni.';
}

/**
 * The SEO Framework (v5+): testo della descrizione automatica dei tag senza descrizione.
 * Una descrizione scritta a mano (nel tag o nel riquadro di TSF) ha sempre la precedenza.
 *
 * @param string     $excerpt Testo usato da TSF.
 * @param array|null $args    Argomenti di TSF.
 * @return string
 */
function ntn_hub_tsf_tag_excerpt( $excerpt, $args = null ) {
	if ( '' !== trim( (string) $excerpt ) ) {
		return $excerpt;
	}
	if ( null === $args ) {
		$tag = is_tag() ? get_queried_object() : null;
	} elseif ( is_array( $args ) && ! empty( $args['id'] ) && 'post_tag' === ( $args['tax'] ?? ( $args['taxonomy'] ?? '' ) ) ) {
		$tag = get_term( (int) $args['id'], 'post_tag' );
	} else {
		$tag = null;
	}
	if ( $tag instanceof WP_Term ) {
		$desc = ntn_hub_tag_seo_description( $tag );
		if ( '' !== $desc ) {
			return $desc;
		}
	}
	return $excerpt;
}
add_filter( 'the_seo_framework_description_excerpt', 'ntn_hub_tsf_tag_excerpt', 10, 2 );

/**
 * The SEO Framework (v5+): ritocchi ai tag della Hub, che TSF tratta come un articolo.
 * - og:type "website" invece di "article" (la Hub è una raccolta);
 * - canonical e og:url delle pagine successive (/page/2/) che puntano a sé stesse, come senza plugin SEO.
 * TSF 5.2 non ha filtri dedicati per questi due tag: si usa il filtro generale dei tag già compilati.
 *
 * @param array $tags Dati dei tag di TSF, per nome (es. 'og:type', 'canonical', 'og:url').
 * @return array
 */
function ntn_hub_tsf_meta_render_data( $tags ) {
	if ( ! is_singular( 'videogioco' ) || ! is_array( $tags ) ) {
		return $tags;
	}
	if ( isset( $tags['og:type']['attributes']['content'] ) ) {
		$tags['og:type']['attributes']['content'] = 'website';
	}
	$paged = (int) get_query_var( 'paged' );
	if ( $paged > 1 ) {
		$url = ntn_hub_paged_canonical( (string) get_permalink( get_queried_object_id() ), get_queried_object() );
		if ( isset( $tags['canonical']['attributes']['href'] ) ) {
			$tags['canonical']['attributes']['href'] = $url;
		}
		if ( isset( $tags['og:url']['attributes']['content'] ) ) {
			$tags['og:url']['attributes']['content'] = $url;
		}
	}
	return $tags;
}
add_filter( 'the_seo_framework_meta_render_data', 'ntn_hub_tsf_meta_render_data' );
