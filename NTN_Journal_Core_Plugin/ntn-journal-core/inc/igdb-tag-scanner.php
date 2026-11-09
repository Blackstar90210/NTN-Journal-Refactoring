<?php
/**
 * Scanner dei tag IGDB (v4.15.0).
 *
 * Cerca su IGDB i tag degli articoli che non hanno ancora un gioco collegato e prepara una lista da confermare.
 * La scansione non scrive nulla sui contenuti: i dati del gioco e la Hub nascono solo alla conferma, con lo
 * stesso salvataggio del pannello (ntn_igdb_link_tag_to_game).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tag per ogni richiesta della scansione: lotti piccoli per non stressare il sito e IGDB. */
const NTN_IGDB_SCAN_BATCH = 20;

/**
 * Nomi di tag che su IGDB coincidono con una voce ma sul sito non sono giochi. Non entrano mai in lista.
 *
 * @return string[] Nomi in minuscolo.
 */
function ntn_igdb_scan_default_excluded() {
	return (array) apply_filters( 'ntn_igdb_scan_default_excluded', array( 'steam', 'meta', 'amazon', 'playground', 'bulkhead' ) );
}

/**
 * Tag da esaminare: non hanno dati di gioco, non sono esclusi e stanno in almeno un articolo senza gioco collegato.
 * Così una nuova scansione propone solo i tag nuovi.
 *
 * @return int[] ID dei tag, in ordine stabile.
 */
function ntn_igdb_scan_candidate_ids() {
	global $wpdb;

	$game_keys = "'_ntn_igdb_game_id','_ntn_game_title'";

	$ids = $wpdb->get_col(
		"SELECT DISTINCT tt.term_id
		FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'post_tag'
		INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id AND p.post_type = 'post' AND p.post_status = 'publish'
		WHERE tt.term_id NOT IN (
			SELECT term_id FROM {$wpdb->termmeta}
			WHERE ( meta_key IN ({$game_keys}) AND meta_value <> '' ) OR meta_key = '_ntn_not_game'
		)
		AND p.ID NOT IN (
			SELECT tr2.object_id FROM {$wpdb->term_relationships} tr2
			INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id AND tt2.taxonomy = 'post_tag'
			INNER JOIN {$wpdb->termmeta} tm2 ON tm2.term_id = tt2.term_id AND tm2.meta_key IN ({$game_keys}) AND tm2.meta_value <> ''
		)
		AND p.ID NOT IN (
			SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ntn_game_title' AND meta_value <> ''
		)
		ORDER BY tt.term_id ASC"
	); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nessun dato esterno nella query

	return array_map( 'intval', (array) $ids );
}

/**
 * Richiesta a IGDB per i giochi con esattamente questo nome (maiuscole a parte), senza le edizioni.
 *
 * @param string $name Nome del tag.
 * @return string Testo della richiesta.
 */
function ntn_igdb_scan_query( $name ) {
	$clean = preg_replace( '/[\r\n\t]+/', ' ', (string) $name );
	return 'fields id,name,first_release_date,version_parent,cover.url; where name ~ "' . addcslashes( $clean, '"\\' ) . '"; limit 10;';
}

/**
 * Porta i risultati di IGDB al formato della lista, dal più recente al più vecchio.
 *
 * @param mixed $games Risposta di IGDB.
 * @return array[] Ogni gioco ha id, name, year, cover.
 */
function ntn_igdb_scan_format_games( $games ) {
	$list = array();
	foreach ( (array) $games as $game ) {
		if ( ! is_array( $game ) || empty( $game['id'] ) || empty( $game['name'] ) || ! empty( $game['version_parent'] ) ) {
			continue;
		}
		$cover = isset( $game['cover']['url'] ) ? (string) $game['cover']['url'] : '';
		if ( 0 === strpos( $cover, '//' ) ) {
			$cover = 'https:' . $cover;
		}
		$list[] = array(
			'id'    => (int) $game['id'],
			'name'  => sanitize_text_field( $game['name'] ),
			'year'  => ! empty( $game['first_release_date'] ) ? gmdate( 'Y', (int) $game['first_release_date'] ) : '',
			'cover' => esc_url_raw( $cover ),
		);
	}
	usort(
		$list,
		static function ( $a, $b ) {
			return strcmp( (string) $b['year'], (string) $a['year'] );
		}
	);
	return $list;
}

/**
 * Cerca un nome su IGDB. Il risultato resta in memoria per un giorno.
 *
 * @param string $name Nome del tag.
 * @return array[]|WP_Error Giochi trovati.
 */
function ntn_igdb_scan_lookup( $name ) {
	$transient = 'ntn_igdb_scan_' . md5( strtolower( (string) $name ) );
	$cached    = get_transient( $transient );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$data = ntn_core_igdb_request( ntn_igdb_scan_query( $name ) );
	usleep( 300000 ); // IGDB accetta al massimo 4 richieste al secondo
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$list = ntn_igdb_scan_format_games( $data );
	set_transient( $transient, $list, DAY_IN_SECONDS );
	return $list;
}

/**
 * Gruppo della riga. "safe" è solo un risultato unico per un tag presente nel titolo dell'articolo, senza un
 * tag più preciso accanto (Hell Let Loose e Hell Let Loose: Vietnam).
 *
 * @param int  $found      Giochi trovati su IGDB.
 * @param bool $in_title   Il nome del tag è nel titolo o nell'indirizzo di un suo articolo.
 * @param bool $has_longer Un altro tag dello stesso articolo contiene questo nome.
 * @return string safe, choose o none.
 */
function ntn_igdb_scan_group( $found, $in_title, $has_longer ) {
	if ( $found < 1 ) {
		return 'none';
	}
	return ( 1 === (int) $found && $in_title && ! $has_longer ) ? 'safe' : 'choose';
}

/**
 * Prepara la riga di un tag.
 *
 * @param WP_Term $tag Tag.
 * @return array|WP_Error|null La riga, l'errore di IGDB, oppure null se il tag è escluso.
 */
function ntn_igdb_scan_row( $tag ) {
	$name = html_entity_decode( $tag->name, ENT_QUOTES, 'UTF-8' );
	$low  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name ) : strtolower( $name );
	if ( in_array( $low, ntn_igdb_scan_default_excluded(), true ) ) {
		return null;
	}

	$games = ntn_igdb_scan_lookup( $name );
	if ( is_wp_error( $games ) ) {
		return $games;
	}

	$posts = get_posts(
		array(
			'tag_id'           => $tag->term_id,
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => 10,
			'suppress_filters' => true,
		)
	);

	$in_title   = false;
	$has_longer = false;
	$example    = '';
	foreach ( $posts as $post ) {
		$title = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
		$title = preg_replace( '/\s+/u', ' ', $title );
		if ( '' === $example ) {
			$example = $title;
		}
		$hay = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title ) : strtolower( $title );
		if ( false !== strpos( $hay, $low ) || 0 === strpos( $post->post_name, $tag->slug ) ) {
			$in_title = true;
			$example  = $title;
		}
		foreach ( (array) get_the_tags( $post->ID ) as $other ) {
			if ( ! $other instanceof WP_Term || $other->term_id === $tag->term_id ) {
				continue;
			}
			$other_name = html_entity_decode( $other->name, ENT_QUOTES, 'UTF-8' );
			$other_low  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $other_name ) : strtolower( $other_name );
			if ( strlen( $other_low ) > strlen( $low ) && false !== strpos( $other_low, $low ) ) {
				$has_longer = true;
			}
		}
	}

	return array(
		'tag_id'   => (int) $tag->term_id,
		'tag'      => $name,
		'articles' => (int) $tag->count,
		'example'  => $example,
		'in_title' => $in_title,
		'group'    => ntn_igdb_scan_group( count( $games ), $in_title, $has_longer ),
		'games'    => $games,
	);
}

/**
 * Controlli comuni alle richieste dello scanner: nonce, permessi, scanner acceso.
 */
function ntn_igdb_scan_guard() {
	check_ajax_referer( 'ntn_igdb_scan', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}
	if ( 'yes' !== get_option( 'ntn_igdb_scanner_active', 'yes' ) ) {
		wp_send_json_error( 'Lo Scanner IGDB è disattivato' );
	}
}

/**
 * Scansione a lotti: restituisce le righe di un lotto. Non scrive dati di gioco.
 */
function ntn_ajax_igdb_scan_tags() {
	ntn_igdb_scan_guard();

	$offset = isset( $_POST['offset'] ) ? max( 0, intval( $_POST['offset'] ) ) : 0;
	$ids    = ntn_igdb_scan_candidate_ids();
	$rows   = array();

	foreach ( array_slice( $ids, $offset, NTN_IGDB_SCAN_BATCH ) as $id ) {
		$tag = get_term( $id, 'post_tag' );
		if ( ! $tag instanceof WP_Term ) {
			continue;
		}
		$row = ntn_igdb_scan_row( $tag );
		if ( is_wp_error( $row ) ) {
			wp_send_json_error( $row->get_error_message() );
		}
		if ( $row ) {
			$rows[] = $row;
		}
	}

	wp_send_json_success(
		array(
			'rows'  => $rows,
			'total' => count( $ids ),
			'next'  => $offset + NTN_IGDB_SCAN_BATCH,
		)
	);
}
add_action( 'wp_ajax_ntn_igdb_scan_tags', 'ntn_ajax_igdb_scan_tags' );

/**
 * Conferma di una riga: collega il tag al gioco e crea la Hub, con il salvataggio del pannello.
 */
function ntn_ajax_igdb_scan_confirm() {
	ntn_igdb_scan_guard();

	$tag_id  = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;
	$igdb_id = isset( $_POST['igdb_id'] ) ? absint( $_POST['igdb_id'] ) : 0;
	if ( ! $tag_id || ! $igdb_id ) {
		wp_send_json_error( 'Dati mancanti' );
	}

	$hub_id = ntn_igdb_link_tag_to_game( $tag_id, $igdb_id );
	if ( is_wp_error( $hub_id ) ) {
		wp_send_json_error( $hub_id->get_error_message() );
	}
	wp_send_json_success( array( 'hub_url' => (string) get_permalink( (int) $hub_id ) ) );
}
add_action( 'wp_ajax_ntn_igdb_scan_confirm', 'ntn_ajax_igdb_scan_confirm' );

/**
 * "Non è un gioco": il tag non torna più in lista.
 */
function ntn_ajax_igdb_scan_not_game() {
	ntn_igdb_scan_guard();

	$tag_id = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;
	$tag    = $tag_id ? get_term( $tag_id, 'post_tag' ) : null;
	if ( ! $tag instanceof WP_Term ) {
		wp_send_json_error( 'Tag non trovato' );
	}
	update_term_meta( $tag_id, '_ntn_not_game', '1' );
	wp_send_json_success();
}
add_action( 'wp_ajax_ntn_igdb_scan_not_game', 'ntn_ajax_igdb_scan_not_game' );

/**
 * Rimette in lista tutti i tag segnati come "Non è un gioco", per correggere un errore.
 */
function ntn_ajax_igdb_scan_reset_excluded() {
	ntn_igdb_scan_guard();

	$removed = delete_metadata( 'term', 0, '_ntn_not_game', '', true );
	wp_send_json_success( array( 'removed' => (bool) $removed ) );
}
add_action( 'wp_ajax_ntn_igdb_scan_reset_excluded', 'ntn_ajax_igdb_scan_reset_excluded' );
