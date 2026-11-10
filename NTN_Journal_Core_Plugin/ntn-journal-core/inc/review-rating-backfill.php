<?php
/**
 * Voti alle vecchie recensioni (v4.16.0).
 *
 * Google riconosce una recensione solo se ha un voto, e il voto deve essere visibile nella pagina. Molte vecchie
 * recensioni hanno già un giudizio sotto forma di tag (Consigliato, Consigliato con riserva, Must Play, Bocciato...):
 * questa funzione lo traduce nel voto con la stessa scala che il sito usa già (ntn_get_rating_label). Il voto nasce solo
 * alla conferma dell'amministratore e non sostituisce mai un voto già scritto.
 *
 * @package NTN_Journal_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Recensioni per pagina nell'elenco. */
const NTN_RATING_BACKFILL_PER_PAGE = 20;

/**
 * Normalizza il nome di un giudizio: minuscolo, senza accenti né simboli.
 *
 * @param mixed $text Nome del tag o della categoria.
 * @return string
 */
function ntn_core_rating_norm( $text ) {
	$text = remove_accents( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) );
	return trim( strtolower( preg_replace( '/[^a-z0-9]+/i', ' ', $text ) ) );
}

/**
 * Giudizi riconosciuti e voto corrispondente. La scala è quella di ntn_get_rating_label():
 * 5 Must Play, 4 Consigliato, 3 Consigliato con riserva, 1-2 Bocciato (qui 2).
 *
 * @return array<string,int> Nome normalizzato => voto da 1 a 5.
 */
function ntn_core_rating_label_map() {
	return (array) apply_filters(
		'ntn_core_rating_label_map',
		array(
			'must play'               => 5,
			'must buy'                => 5,
			'capolavoro'              => 5,
			'consigliato'             => 4,
			'consigliato con riserva' => 3,
			'bocciato'                => 2,
		)
	);
}

/**
 * Voto proposto a partire dai nomi dei tag e delle categorie di una recensione.
 * Se i giudizi trovati portano a voti diversi non si propone nulla.
 *
 * @param string[] $names Nomi dei tag e delle categorie.
 * @return array{rating:int,label:string}|null
 */
function ntn_core_rating_from_term_names( array $names ) {
	$map    = ntn_core_rating_label_map();
	$found  = array();
	$labels = array();
	foreach ( $names as $name ) {
		$key = ntn_core_rating_norm( $name );
		if ( isset( $map[ $key ] ) ) {
			$found[ (int) $map[ $key ] ] = true;
			$labels[]                    = trim( wp_strip_all_tags( (string) $name ) );
		}
	}
	if ( 1 !== count( $found ) ) {
		return null;
	}
	return array(
		'rating' => (int) key( $found ),
		'label'  => implode( ', ', array_unique( $labels ) ),
	);
}

/**
 * Voto proposto per una recensione, dai suoi tag e categorie.
 *
 * @param int $post_id ID dell'articolo.
 * @return array{rating:int,label:string}|null
 */
function ntn_core_post_rating_proposal( $post_id ) {
	$names = wp_get_post_terms( (int) $post_id, array( 'post_tag', 'category' ), array( 'fields' => 'names' ) );
	return ( is_array( $names ) ) ? ntn_core_rating_from_term_names( $names ) : null;
}

/**
 * Recensioni pubblicate senza voto, dalla più recente.
 *
 * @param int $paged    Pagina (da 1). Con 0 restituisce tutte.
 * @param int $per_page Recensioni per pagina.
 * @return WP_Query
 */
function ntn_core_reviews_without_rating_query( $paged = 1, $per_page = NTN_RATING_BACKFILL_PER_PAGE ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'category_name'       => 'recensioni,recensione',
		'orderby'             => 'date',
		'order'               => 'DESC',
		'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- elenco in amministrazione.
			'relation' => 'OR',
			array(
	'key'     => 'rating',
	'compare' => 'NOT EXISTS',
		),
			array(
		'key'     => 'rating',
		'value'   => '',
		'compare' => '=',
		),
		),
	);
	if ( $paged > 0 ) {
		$args['posts_per_page'] = max( 1, (int) $per_page );
		$args['paged']          = (int) $paged;
	} else {
		$args['posts_per_page'] = -1;
		$args['fields']         = 'ids';
		$args['no_found_rows']  = true;
	}
	return new WP_Query( $args );
}

/**
 * Scrive i voti scelti. Non sostituisce mai un voto già presente e salta le non recensioni.
 *
 * @param array<int,int> $ratings ID articolo => voto (1-5).
 * @return int Voti scritti.
 */
function ntn_core_apply_review_ratings( array $ratings ) {
	$written = 0;
	foreach ( $ratings as $post_id => $rating ) {
		$post_id = (int) $post_id;
		$rating  = (int) $rating;
		if ( $post_id < 1 || $rating < 1 || $rating > 5 ) {
			continue;
		}
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) || 'post' !== $post->post_type || ! has_category( array( 'recensioni', 'recensione' ), $post ) ) {
			continue;
		}
		if ( '' !== trim( (string) get_post_meta( $post_id, 'rating', true ) ) ) {
			continue;
		}
		update_post_meta( $post_id, 'rating', (string) $rating );
		if ( function_exists( 'clean_post_cache' ) ) {
			clean_post_cache( $post_id );
		}
		++$written;
	}
	return $written;
}

/**
 * Applica a tutte le recensioni senza voto quello proposto dai loro tag.
 *
 * @return int Voti scritti.
 */
function ntn_core_apply_all_proposed_ratings() {
	$query   = ntn_core_reviews_without_rating_query( 0 );
	$ratings = array();
	foreach ( (array) $query->posts as $post_id ) {
		$proposal = ntn_core_post_rating_proposal( (int) $post_id );
		if ( $proposal ) {
			$ratings[ (int) $post_id ] = $proposal['rating'];
		}
	}
	return ntn_core_apply_review_ratings( $ratings );
}
