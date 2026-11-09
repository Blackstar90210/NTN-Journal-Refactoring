<?php
/**
 * NTN Journal Core - Nomi delle cache condivise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Inizio del nome delle cache degli articoli in evidenza (anche per svuotarle tutte dal pannello).
define( 'NTN_CORE_FEATURED_CACHE_PREFIX', 'ntn_hub_featured_' );

/**
 * Nome della cache degli articoli in evidenza di un Hub del gioco (v4.13.0).
 *
 * Una sola funzione per chi salva la cache (ntn_get_game_featured_content) e per chi la cancella
 * (pannello del plugin, salvataggio degli articoli). Serve anche quando
 * inc/game-data.php non si carica: per questo è in un file a parte, caricato sempre.
 *
 * @param string $type 'post' per la scheda videogioco, 'term' per il tag del gioco.
 * @param int    $id   ID della scheda o del tag.
 * @return string
 */
function ntn_core_featured_cache_key( $type, $id ) {
	return NTN_CORE_FEATURED_CACHE_PREFIX . ( 'term' === $type ? 'term_' : 'post_' ) . (int) $id;
}

// Gruppo della cache in memoria per le risposte pubbliche di ricerca e catalogo (v4.17.0).
define( 'NTN_CORE_REST_CACHE_GROUP', 'ntn_rest' );

/**
 * Parametri del catalogo che contano per la cache, già puliti (v4.17.0).
 *
 * La chiave della cache non deve dipendere da parametri sconosciuti: un visitatore potrebbe aggiungerne
 * di nuovi a ogni richiesta (?x=1, ?x=2…) e creare una memoria diversa per ognuno.
 *
 * @param array $params Parametri della richiesta.
 * @return array
 */
function ntn_core_rest_games_normalized_params( $params ) {
	$params = (array) $params;
	$norm   = array(
		'page'      => isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1,
		'per_page'  => isset( $params['per_page'] ) ? min( 20, absint( $params['per_page'] ) ) : 20,
		'sort'      => isset( $params['sort'] ) ? sanitize_key( (string) $params['sort'] ) : 'year_desc',
		'year'      => isset( $params['year'] ) ? absint( $params['year'] ) : 0,
		'search'    => isset( $params['search'] ) ? trim( (string) $params['search'] ) : '',
		'franchise' => isset( $params['franchise'] ) ? sanitize_title( (string) $params['franchise'] ) : '',
		'developer' => isset( $params['developer'] ) ? sanitize_title( (string) $params['developer'] ) : '',
		'publisher' => isset( $params['publisher'] ) ? sanitize_title( (string) $params['publisher'] ) : '',
		'engine'    => isset( $params['engine'] ) ? sanitize_title( (string) $params['engine'] ) : '',
	);
	return $norm;
}

/**
 * Nome della cache di una richiesta al catalogo (v4.17.0).
 */
function ntn_core_rest_games_cache_key( $params ) {
	return 'ntn_games_' . md5( wp_json_encode( ntn_core_rest_games_normalized_params( $params ) ) );
}

/**
 * Una richiesta al catalogo può essere salvata nel database solo se le combinazioni possibili sono poche:
 * senza ricerca libera, senza filtri e senza anno, e con la pagina entro la ventesima (v4.17.0).
 * Tutte le altre combinazioni sono infinite: restano solo nella cache in memoria (Redis, Memcached), se c'è.
 */
function ntn_core_rest_games_uses_database( $params ) {
	$n = ntn_core_rest_games_normalized_params( $params );
	return '' === $n['search'] && '' === $n['franchise'] && '' === $n['developer'] && '' === $n['publisher'] && '' === $n['engine']
		&& 0 === $n['year'] && $n['page'] <= 20;
}

/**
 * Legge una risposta in cache (v4.17.0).
 *
 * @param string $key          Nome della cache.
 * @param bool   $use_database true se questa richiesta può stare nel database (transient).
 * @return mixed false se non c'è.
 */
function ntn_core_rest_cache_get( $key, $use_database = false ) {
	if ( $use_database || wp_using_ext_object_cache() ) {
		return get_transient( $key );
	}
	return false;
}

/**
 * Salva una risposta in cache (v4.17.0). Senza cache in memoria, le richieste con ricerca libera o filtri
 * non vengono salvate: ogni testo diverso avrebbe riempito la tabella delle opzioni.
 */
function ntn_core_rest_cache_set( $key, $payload, $ttl, $use_database = false ) {
	if ( $use_database || wp_using_ext_object_cache() ) {
		set_transient( $key, $payload, $ttl );
	}
}

/**
 * Toglie una volta le righe lasciate nel database dalle versioni precedenti (v4.17.0): una per ogni testo
 * cercato nella ricerca e per ogni combinazione del catalogo. Da ora quelle richieste non vengono più salvate lì.
 */
function ntn_core_rest_cache_cleanup_once() {
	if ( get_option( 'ntn_rest_cache_cleaned_v1' ) ) {
		return;
	}
	global $wpdb;
	$prefixes = array( 'ntn_livesearch2_', 'ntn_games_' );
	foreach ( $prefixes as $prefix ) {
		foreach ( array( '_transient_', '_transient_timeout_' ) as $start ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $start . $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- pulizia una tantum delle righe vecchie.
		}
	}
	update_option( 'ntn_rest_cache_cleaned_v1', '1', false );
}
add_action( 'admin_init', 'ntn_core_rest_cache_cleanup_once' );
