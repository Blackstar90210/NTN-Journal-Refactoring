<?php
/**
 * NTN Journal - Pagine con pochi articoli fuori da Google (v3.20.0)
 *
 * Tag, categorie, Hub di categoria e Hub dei videogiochi sotto una soglia di articoli:
 * - noindex con "segui i collegamenti" (Google non le mostra, ma passa agli articoli);
 * - fuori dalla sitemap.
 * Raggiunta la soglia tornano da sole, senza fare nulla. Le soglie si cambiano in NTN Journal Funzioni → SEO
 * (0 o 1 = regola spenta). Una pagina messa in noindex a mano resta in noindex.
 *
 * @package NTN_Journal_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Articoli minimi per tag e categorie (anche Hub di categoria e pagine dei marchi).
 *
 * @return int
 */
function ntn_core_seo_min_terms(): int {
	return max( 0, (int) get_option( 'ntn_seo_min_posts_terms', 10 ) );
}

/**
 * Articoli minimi per le Hub dei videogiochi.
 *
 * @return int
 */
function ntn_core_seo_min_games(): int {
	return max( 0, (int) get_option( 'ntn_seo_min_posts_games', 3 ) );
}

/**
 * Articoli pubblicati di un tag, o di una categoria con le sue sottocategorie.
 *
 * @param WP_Term $term Tag o categoria.
 * @return int
 */
function ntn_core_seo_term_count( WP_Term $term ): int {
	if ( 'category' === $term->taxonomy && function_exists( 'ntn_core_category_hub_tree' ) && function_exists( 'ntn_core_category_hub_count' ) ) {
		return (int) ntn_core_category_hub_count( ntn_core_category_hub_tree( (int) $term->term_id ) );
	}
	return (int) $term->count;
}

/**
 * Articoli di una Hub di videogioco: quelli del tag del gioco (stesso slug), come nella Hub.
 *
 * @param int $post_id ID della Hub.
 * @return int
 */
function ntn_core_seo_game_count( int $post_id ): int {
	$slug = (string) get_post_field( 'post_name', $post_id );
	$tag  = '' !== $slug ? get_term_by( 'slug', $slug, 'post_tag' ) : false;
	return $tag instanceof WP_Term ? (int) $tag->count : 0;
}

/**
 * Il tag o la categoria ha meno articoli della soglia?
 *
 * @param WP_Term $term Termine.
 * @return bool
 */
function ntn_core_seo_is_thin_term( WP_Term $term ): bool {
	$min = ntn_core_seo_min_terms();
	if ( $min < 2 || ! in_array( $term->taxonomy, array( 'category', 'post_tag' ), true ) ) {
		return false;
	}
	// v3.21.0: la Hub di uno studio con almeno 2 giochi nel catalogo non è una pagina povera
	if ( 'post_tag' === $term->taxonomy && function_exists( 'ntn_core_studio_has_enough_games' ) && ntn_core_studio_has_enough_games( $term ) ) {
		return false;
	}
	return ntn_core_seo_term_count( $term ) < $min;
}

/**
 * La Hub del videogioco ha meno articoli della soglia?
 *
 * @param int $post_id ID della Hub.
 * @return bool
 */
function ntn_core_seo_is_thin_game( int $post_id ): bool {
	$min = ntn_core_seo_min_games();
	if ( $min < 2 || 'videogioco' !== get_post_type( $post_id ) ) {
		return false;
	}
	return ntn_core_seo_game_count( $post_id ) < $min;
}

/**
 * La pagina (corrente o indicata dagli argomenti di The SEO Framework) è sotto la soglia?
 *
 * @param array|null $args Argomenti di TSF ('id', 'tax'), null = pagina corrente.
 * @return bool
 */
function ntn_core_seo_is_thin( $args = null ): bool {
	if ( null === $args ) {
		if ( is_category() || is_tag() ) {
			$term = get_queried_object();
			return $term instanceof WP_Term && ntn_core_seo_is_thin_term( $term );
		}
		return is_singular( 'videogioco' ) && ntn_core_seo_is_thin_game( (int) get_queried_object_id() );
	}
	if ( ! is_array( $args ) || empty( $args['id'] ) ) {
		return false;
	}
	$tax = (string) ( $args['tax'] ?? ( $args['taxonomy'] ?? '' ) );
	if ( '' !== $tax ) {
		$term = get_term( (int) $args['id'], $tax );
		return $term instanceof WP_Term && ntn_core_seo_is_thin_term( $term );
	}
	return empty( $args['pta'] ) && empty( $args['uid'] ) && ntn_core_seo_is_thin_game( (int) $args['id'] );
}

/**
 * The SEO Framework: noindex per le pagine sotto la soglia ("segui i collegamenti" non cambia).
 *
 * @param array      $meta    Direttive robots di TSF.
 * @param array|null $args    Argomenti di TSF.
 * @param int        $options Livello di TSF.
 * @return array
 */
function ntn_core_seo_thin_tsf_robots( $meta, $args = null, $options = 0 ) {
	if ( is_array( $meta ) && ntn_core_seo_is_thin( $args ) ) {
		$meta['noindex'] = 'noindex';
	}
	return $meta;
}
add_filter( 'the_seo_framework_robots_meta_array', 'ntn_core_seo_thin_tsf_robots', 10, 3 );

/**
 * Senza plugin SEO: noindex, follow con le direttive robots di WordPress.
 *
 * @param array $robots Direttive di WordPress.
 * @return array
 */
function ntn_core_seo_thin_wp_robots( $robots ) {
	if ( is_array( $robots ) && ntn_core_seo_is_thin() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'ntn_core_seo_thin_wp_robots' );

/**
 * Hub dei videogiochi sotto la soglia (ID), con il numero di articoli calcolato in una sola lettura dei tag.
 *
 * @return int[]
 */
function ntn_core_seo_thin_game_ids(): array {
	$min = ntn_core_seo_min_games();
	if ( $min < 2 ) {
		return array();
	}
	$counts = array();
	$tags   = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$counts[ $tag->slug ] = (int) $tag->count;
		}
	}
	$thin  = array();
	$games = get_posts( array( 'post_type' => 'videogioco', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	foreach ( $games as $game_id ) {
		$slug = (string) get_post_field( 'post_name', $game_id );
		if ( ( $counts[ $slug ] ?? 0 ) < $min ) {
			$thin[] = (int) $game_id;
		}
	}
	return $thin;
}

/**
 * Sitemap di The SEO Framework: fuori le Hub dei videogiochi sotto la soglia.
 * (Le Hub di categoria seguono la soglia in ntn_core_category_hub_sitemap_urls; tag e categorie TSF non li elenca.)
 *
 * @param int[] $excluded ID esclusi.
 * @return int[]
 */
function ntn_core_seo_thin_tsf_sitemap( $excluded ) {
	return array_values( array_unique( array_merge( is_array( $excluded ) ? $excluded : array(), ntn_core_seo_thin_game_ids() ) ) );
}
add_filter( 'the_seo_framework_sitemap_exclude_ids', 'ntn_core_seo_thin_tsf_sitemap' );

/**
 * Sitemap di WordPress (senza plugin SEO): fuori le Hub dei videogiochi sotto la soglia.
 *
 * @param array  $args      Argomenti della query.
 * @param string $post_type Tipo di contenuto.
 * @return array
 */
function ntn_core_seo_thin_wp_sitemap_posts( $args, $post_type ) {
	if ( 'videogioco' === $post_type && is_array( $args ) ) {
		$thin = ntn_core_seo_thin_game_ids();
		if ( $thin ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $thin );
		}
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'ntn_core_seo_thin_wp_sitemap_posts', 10, 2 );

/**
 * Sitemap di WordPress (senza plugin SEO): fuori i tag e le categorie sotto la soglia.
 *
 * @param array  $args     Argomenti della query dei termini.
 * @param string $taxonomy Tassonomia.
 * @return array
 */
function ntn_core_seo_thin_wp_sitemap_terms( $args, $taxonomy ) {
	if ( ! is_array( $args ) || ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) || ntn_core_seo_min_terms() < 2 ) {
		return $args;
	}
	$thin  = array();
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( ntn_core_seo_is_thin_term( $term ) ) {
				$thin[] = (int) $term->term_id;
			}
		}
	}
	if ( $thin ) {
		$args['exclude'] = array_merge( isset( $args['exclude'] ) ? (array) $args['exclude'] : array(), $thin );
	}
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'ntn_core_seo_thin_wp_sitemap_terms', 10, 2 );

/**
 * Quante pagine sono sotto la soglia, per la Dashboard (in memoria per 10 minuti).
 *
 * @return array{tags:int, categories:int, games:int}
 */
function ntn_core_seo_thin_summary(): array {
	$key    = 'ntn_seo_thin_' . md5( ntn_core_seo_min_terms() . '|' . ntn_core_seo_min_games() );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$out = array( 'tags' => 0, 'categories' => 0, 'games' => count( ntn_core_seo_thin_game_ids() ) );
	foreach ( array( 'post_tag' => 'tags', 'category' => 'categories' ) as $taxonomy => $slot ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
		if ( ! is_array( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			// I tag dei giochi collegati a IGDB portano alla Hub del gioco: contano lì
			if ( 'post_tag' === $taxonomy && get_term_meta( $term->term_id, '_ntn_igdb_game_id', true ) ) {
				continue;
			}
			if ( ntn_core_seo_is_thin_term( $term ) ) {
				++$out[ $slot ];
			}
		}
	}
	set_transient( $key, $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}
