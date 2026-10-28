<?php
/**
 * Helper functions and API for Catalog.
 *
 * @package NTN_Journal_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera l'array di argomenti per WP_Query basato sui parametri in ingresso.
 *
 * @param array $params Parametri validati (es. da REST API o da $_GET).
 * @return array Argomenti per WP_Query.
 */
function ntn_get_catalog_query_args( array $params ): array {
	$paged     = isset( $params['page'] ) ? absint( $params['page'] ) : 1;
	$per_page  = isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 20;
	$franchise = ! empty( $params['franchise'] ) ? sanitize_title( $params['franchise'] ) : '';
	$developer = ! empty( $params['developer'] ) ? sanitize_title( $params['developer'] ) : '';
	$publisher = ! empty( $params['publisher'] ) ? sanitize_title( $params['publisher'] ) : '';
	$engine    = ! empty( $params['engine'] ) ? sanitize_title( $params['engine'] ) : '';
	$year      = ! empty( $params['year'] ) ? absint( $params['year'] ) : 0;
	$search    = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
	$sort      = ! empty( $params['sort'] ) ? sanitize_key( $params['sort'] ) : 'year_desc';

	$allowed_sorts = array( 'title_asc', 'title_desc', 'year_desc', 'year_asc' );
	if ( ! in_array( $sort, $allowed_sorts, true ) ) {
		$sort = 'title_asc';
	}

	$orderby = 'title';
	$order   = 'ASC';
	$meta_query = array();

	if ( $sort === 'title_desc' ) {
		$order = 'DESC';
	} elseif ( $sort === 'year_desc' || $sort === 'year_asc' ) {
		$order   = ( $sort === 'year_desc' ) ? 'DESC' : 'ASC';
		
		// Fix INNER JOIN escludendo i post senza anno, usiamo LEFT JOIN tramite EXISTS logic
		// O meglio, definiamo una meta_query che ordini per _ntn_game_release
		$meta_query['release_clause'] = array(
			'key'     => '_ntn_game_release',
			'compare' => 'EXISTS', // Garantiamo che possiamo usarla per l'ordinamento
		);
		$orderby = array(
			'release_clause' => $order,
			'title'          => 'ASC' // Fallback
		);
		// v3.19.1: a parità di data, i giochi con il giorno esatto stanno accanto agli altri giochi datati (vedi ntn_catalog_precision_clauses)
		$precision_sort = $order;
	}

	$args = array(
		'post_type'      => 'videogioco',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'orderby'        => $orderby,
		'order'          => $order,
		'no_found_rows'  => false,
	);

	$tax_query = array();
	if ( $franchise ) {
		$tax_query[] = array( 'taxonomy' => 'game_franchise', 'field' => 'slug', 'terms' => $franchise );
	}
	if ( $developer ) {
		$tax_query[] = array( 'taxonomy' => 'game_developer', 'field' => 'slug', 'terms' => $developer );
	}
	if ( $publisher ) {
		$tax_query[] = array( 'taxonomy' => 'game_publisher', 'field' => 'slug', 'terms' => $publisher );
	}
	if ( $engine ) {
		$tax_query[] = array( 'taxonomy' => 'game_engine', 'field' => 'slug', 'terms' => $engine );
	}
	
	// Filtro anno (sostituito meta_query con tax_query su game_year)
	if ( $year > 0 ) {
		$tax_query[] = array( 'taxonomy' => 'game_year', 'field' => 'slug', 'terms' => strval( $year ) );
	}

	if ( ! empty( $tax_query ) ) {
		$tax_query['relation'] = 'AND';
		$args['tax_query']     = $tax_query;
	}

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

	if ( $search ) {
		$args['s'] = $search;
	}

	if ( ! empty( $precision_sort ) ) {
		$args['ntn_precision_sort'] = $precision_sort;
	}

	return $args;
}

/**
 * Ordinamento del catalogo per data (v3.19.1). IGDB mette al 31 dicembre i giochi di cui conosce solo
 * l'anno; quelli con il mese o il trimestre li mostriamo come "Ott 2027" o "Q3 2027". Dentro ogni anno:
 * - dal più recente: prima i giochi con solo l'anno, in ordine alfabetico, poi tutti gli altri per data
 *   (2027, 2027… 31.12.2027, Q3 2027, 07.04.2027…); a parità di data prima la finestra più vaga;
 * - dal più vecchio: l'ordine inverso, i giochi con solo l'anno alla fine del loro anno.
 * Vale solo per le query del catalogo con il segnale "ntn_precision_sort" (DESC o ASC).
 *
 * @param array    $clauses Parti della query SQL.
 * @param WP_Query $query   Query.
 * @return array
 */
function ntn_catalog_precision_clauses( $clauses, $query ) {
	if ( ! $query instanceof WP_Query || ! $query->get( 'ntn_precision_sort' ) ) {
		return $clauses;
	}
	global $wpdb;

	$desc = 'ASC' !== $query->get( 'ntn_precision_sort' );

	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ntn_rd ON ( ntn_rd.post_id = {$wpdb->posts}.ID AND ntn_rd.meta_key = '_ntn_game_release' )";
	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ntn_rp ON ( ntn_rp.post_id = {$wpdb->posts}.ID AND ntn_rp.meta_key = '_ntn_game_release_precision' )";

	// 4 giorno, 3 mese, 2 trimestre, 0 solo anno o da annunciare
	$rank = "( CASE ntn_rp.meta_value WHEN 'day' THEN 4 WHEN 'month' THEN 3 WHEN 'quarter' THEN 2 ELSE 0 END )";
	$clauses['orderby'] = implode(
		', ',
		array(
			'LEFT( ntn_rd.meta_value, 4 ) ' . ( $desc ? 'DESC' : 'ASC' ),                          // anno
			"( {$rank} > 0 ) " . ( $desc ? 'ASC' : 'DESC' ),                                        // gruppo: solo anno / con data o finestra
			"CASE WHEN {$rank} > 0 THEN ntn_rd.meta_value END " . ( $desc ? 'DESC' : 'ASC' ),       // data
			$rank . ' ' . ( $desc ? 'ASC' : 'DESC' ),                                               // a parità di data, la finestra più vaga prima
			"{$wpdb->posts}.post_title ASC",                                                         // titolo
		)
	);
	return $clauses;
}
add_filter( 'posts_clauses', 'ntn_catalog_precision_clauses', 10, 2 );
