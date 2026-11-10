<?php
/**
 * NTN Journal Core - Introiti stimati su Steam (fase 1: solo i dati).
 *
 * Per ogni gioco (tag con i dati IGDB) il plugin salva una stima degli incassi netti su Steam:
 * - l'ID Steam del gioco arriva da IGDB (external_games con external_game_source = 1, cioè Steam);
 * - il numero di recensioni e il prezzo di listino in dollari arrivano da Steam, senza chiave API;
 * - copie vendute: recensioni x 36 (metodo di Boxleiter). Si mostra un solo valore "circa": un intervallo
 *   (prima x 25..50) era troppo largo da leggere. COPIES_MIN e COPIES_MAX restano per chi volesse di nuovo
 *   un intervallo: il sito mostra comunque il valore centrale;
 * - incasso netto: copie x prezzo x 0,295. Come steam-revenue-calculator.com: dal lordo si tolgono
 *   prezzi regionali (-9%), sconti (-20%) e rimborsi (-12%), e resta 0,59 del lordo; da questo resto
 *   si tolgono la quota di Steam (30%) e l'IVA (20%), e ne resta metà: 0,59 x 0,5 = 0,295.
 *
 * Il sito non chiede mai niente a Steam mentre una pagina si carica: le stime sono salvate nei dati
 * del tag e si aggiornano in secondo piano (WP-Cron) una volta a settimana, a lotti di 20 giochi con due
 * sole richieste a Steam per lotto, e poco dopo ogni collegamento o aggiornamento IGDB di un gioco.
 * Giochi gratuiti, senza prezzo, senza pagina Steam o con meno di 50 recensioni: nessuna stima.
 *
 * I numeri del calcolo si possono cambiare da wp-config.php (per esempio
 * define( 'NTN_CORE_STEAM_COPIES_MIN', 30 );) o con il filtro 'ntn_core_steam_revenue_params'.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NTN_CORE_STEAM_COPIES' ) ) {
	define( 'NTN_CORE_STEAM_COPIES', 36 ); // Copie vendute per ogni recensione.
}
if ( ! defined( 'NTN_CORE_STEAM_COPIES_MIN' ) ) {
	define( 'NTN_CORE_STEAM_COPIES_MIN', NTN_CORE_STEAM_COPIES ); // Uguale a COPIES: niente intervallo.
}
if ( ! defined( 'NTN_CORE_STEAM_COPIES_MAX' ) ) {
	define( 'NTN_CORE_STEAM_COPIES_MAX', NTN_CORE_STEAM_COPIES );
}
if ( ! defined( 'NTN_CORE_STEAM_NET_SHARE' ) ) {
	define( 'NTN_CORE_STEAM_NET_SHARE', 0.295 ); // Parte del lordo che resta come incasso netto (vedi in alto).
}
if ( ! defined( 'NTN_CORE_STEAM_MIN_REVIEWS' ) ) {
	define( 'NTN_CORE_STEAM_MIN_REVIEWS', 50 ); // Sotto questo numero di recensioni la stima non è affidabile.
}

define( 'NTN_CORE_STEAM_BATCH', 20 ); // Giochi per lotto (una richiesta a Steam per le recensioni, una per i prezzi).
define( 'NTN_CORE_STEAM_MAX_AGE', 6 * DAY_IN_SECONDS ); // Dopo quanto un dato va aggiornato (il giro è settimanale).
define( 'NTN_CORE_STEAM_WEEKLY_HOOK', 'ntn_core_steam_revenue_weekly' );
define( 'NTN_CORE_STEAM_BATCH_HOOK', 'ntn_core_steam_revenue_batch' );
define( 'NTN_CORE_STEAM_IGDB_FIELDS', 'external_games.uid, external_games.external_game_source' );

/**
 * I numeri usati nel calcolo, già controllati.
 *
 * @return array{copies_min:int,copies_max:int,net_share:float,min_reviews:int}
 */
function ntn_core_steam_params() {
	$params = apply_filters(
		'ntn_core_steam_revenue_params',
		array(
			'copies_min'  => NTN_CORE_STEAM_COPIES_MIN,
			'copies_max'  => NTN_CORE_STEAM_COPIES_MAX,
			'net_share'   => NTN_CORE_STEAM_NET_SHARE,
			'min_reviews' => NTN_CORE_STEAM_MIN_REVIEWS,
		)
	);
	$params     = is_array( $params ) ? $params : array();
	$copies_min = max( 1, (int) ( $params['copies_min'] ?? NTN_CORE_STEAM_COPIES_MIN ) );
	return array(
		'copies_min'  => $copies_min,
		'copies_max'  => max( $copies_min, (int) ( $params['copies_max'] ?? NTN_CORE_STEAM_COPIES_MAX ) ),
		'net_share'   => min( 1.0, max( 0.0, (float) ( $params['net_share'] ?? NTN_CORE_STEAM_NET_SHARE ) ) ),
		'min_reviews' => max( 1, (int) ( $params['min_reviews'] ?? NTN_CORE_STEAM_MIN_REVIEWS ) ),
	);
}

/**
 * I campi salvati sul tag del gioco.
 *
 * @return string[]
 */
function ntn_core_steam_meta_keys() {
	return array(
		'_ntn_core_steam_appid',       // ID Steam, oppure 'none' se IGDB non ne conosce uno.
		'_ntn_core_steam_reviews',     // Numero di recensioni su Steam.
		'_ntn_core_steam_price',       // Prezzo di listino in centesimi di dollaro.
		'_ntn_core_steam_rev_min',     // Incasso netto stimato, minimo (dollari).
		'_ntn_core_steam_rev_max',     // Incasso netto stimato, massimo (dollari).
		'_ntn_core_steam_rev_status',  // ok, free, no_price, few_reviews, not_found, no_appid.
		'_ntn_core_steam_rev_updated', // Quando è stato controllato l'ultima volta (timestamp; 0 = in coda per primo).
	);
}

/**
 * Legge l'ID Steam dalla risposta di IGDB (external_games).
 *
 * IGDB indica il negozio con "external_game_source" (1 = Steam). Il vecchio campo "category" (1 = Steam)
 * resta come ripiego per le risposte salvate prima del cambio.
 *
 * @param array $game Gioco restituito da IGDB.
 * @return string ID Steam (solo cifre), oppure '' se il gioco non è su Steam.
 */
function ntn_core_steam_appid_from_igdb( $game ) {
	if ( ! is_array( $game ) || empty( $game['external_games'] ) || ! is_array( $game['external_games'] ) ) {
		return '';
	}
	foreach ( $game['external_games'] as $external ) {
		if ( ! is_array( $external ) ) {
			continue;
		}
		$source = $external['external_game_source'] ?? ( $external['category'] ?? 0 );
		if ( is_array( $source ) ) {
			$source = $source['id'] ?? 0;
		}
		if ( 1 !== (int) $source ) {
			continue;
		}
		$uid = trim( (string) ( $external['uid'] ?? '' ) );
		if ( preg_match( '/^[0-9]{1,10}$/', $uid ) && (int) $uid > 0 ) {
			return (string) (int) $uid;
		}
	}
	return '';
}

/**
 * Calcola la stima. Funzione pura: non legge e non salva niente.
 *
 * @param int  $reviews     Numero di recensioni su Steam.
 * @param int  $price_cents Prezzo di listino in centesimi di dollaro.
 * @param bool $is_free     true se il gioco è gratuito.
 * @return array{status:string,min?:int,max?:int}
 */
function ntn_core_steam_estimate( $reviews, $price_cents, $is_free = false ) {
	$params = ntn_core_steam_params();
	if ( $is_free ) {
		return array( 'status' => 'free' );
	}
	if ( (int) $price_cents <= 0 ) {
		return array( 'status' => 'no_price' );
	}
	if ( (int) $reviews < $params['min_reviews'] ) {
		return array( 'status' => 'few_reviews' );
	}
	// Si calcola in centesimi interi e si divide per 100 solo alla fine, così l'arrotondamento è esatto.
	$cents_per_copy = (int) $price_cents * $params['net_share'];
	return array(
		'status' => 'ok',
		'min'    => (int) round( (int) $reviews * $params['copies_min'] * $cents_per_copy / 100 ),
		'max'    => (int) round( (int) $reviews * $params['copies_max'] * $cents_per_copy / 100 ),
	);
}

/**
 * Richiesta GET a Steam che si aspetta un JSON.
 *
 * @param string $url Indirizzo completo.
 * @return array|WP_Error
 */
function ntn_core_steam_http_json( $url ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 8,
			'redirection' => 0, // Se Steam rimanda altrove (pagina di benvenuto), non è la risposta giusta.
			'headers'     => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'ntn_steam_http', $response->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error( 'ntn_steam_http', 'Steam ha risposto con il codice ' . $code . '.' );
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'ntn_steam_invalid', 'La risposta di Steam non è valida.' );
	}
	return $data;
}

/**
 * Pulisce un elenco di ID Steam.
 *
 * @param array $appids ID Steam.
 * @return int[]
 */
function ntn_core_steam_clean_appids( $appids ) {
	$clean = array();
	foreach ( (array) $appids as $appid ) {
		$appid = (int) $appid;
		if ( $appid > 0 ) {
			$clean[ $appid ] = $appid;
		}
	}
	return array_values( $clean );
}

/**
 * Numero di recensioni e gratuità di più giochi con una sola richiesta.
 *
 * Usa il servizio pubblico IStoreBrowseService/GetItems (nessuna chiave). L'indirizzo appreviews/<id>?json=1
 * dal 2026 rimanda alla pagina di benvenuto di Steam, quindi non si usa più.
 *
 * @param array $appids ID Steam.
 * @return array|WP_Error Per ogni ID: array( 'found' => bool, 'reviews' => int, 'is_free' => bool ).
 */
function ntn_core_steam_fetch_reviews( $appids ) {
	$appids = ntn_core_steam_clean_appids( $appids );
	if ( empty( $appids ) ) {
		return array();
	}
	$ids = array();
	foreach ( $appids as $appid ) {
		$ids[] = array( 'appid' => $appid );
	}
	$input = array(
		'ids'          => $ids,
		'context'      => array(
			'country_code' => 'US',
			'language'     => 'english',
		),
		'data_request' => array( 'include_reviews' => true ),
	);
	$data  = ntn_core_steam_http_json( 'https://api.steampowered.com/IStoreBrowseService/GetItems/v1/?input_json=' . rawurlencode( (string) wp_json_encode( $input ) ) );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	if ( ! isset( $data['response'] ) || ! is_array( $data['response'] ) ) {
		return new WP_Error( 'ntn_steam_invalid', 'La risposta di Steam non contiene i giochi.' );
	}
	$out = array();
	foreach ( $appids as $appid ) {
		$out[ $appid ] = array(
			'found'   => false,
			'reviews' => 0,
			'is_free' => false,
		);
	}
	foreach ( (array) ( $data['response']['store_items'] ?? array() ) as $item ) {
		$appid = is_array( $item ) ? (int) ( $item['appid'] ?? ( $item['id'] ?? 0 ) ) : 0;
		if ( ! isset( $out[ $appid ] ) || 1 !== (int) ( $item['success'] ?? 0 ) ) {
			continue;
		}
		$out[ $appid ] = array(
			'found'   => true,
			'reviews' => max( 0, (int) ( $item['reviews']['summary_filtered']['review_count'] ?? 0 ) ),
			'is_free' => ! empty( $item['is_free'] ),
		);
	}
	return $out;
}

/**
 * Prezzo di listino in dollari (senza sconti) di più giochi con una sola richiesta (appdetails).
 *
 * @param array $appids ID Steam.
 * @return array|WP_Error Per ogni ID: array( 'found' => bool, 'price' => int centesimi, 0 se senza prezzo ).
 */
function ntn_core_steam_fetch_prices( $appids ) {
	$appids = ntn_core_steam_clean_appids( $appids );
	if ( empty( $appids ) ) {
		return array();
	}
	$data = ntn_core_steam_http_json( 'https://store.steampowered.com/api/appdetails?appids=' . implode( ',', $appids ) . '&cc=us&filters=price_overview' );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$out = array();
	foreach ( $appids as $appid ) {
		$entry = $data[ (string) $appid ] ?? null;
		if ( ! is_array( $entry ) || empty( $entry['success'] ) ) {
			$out[ $appid ] = array(
				'found' => false,
				'price' => 0,
			);
			continue;
		}
		$overview = is_array( $entry['data'] ?? null ) ? ( $entry['data']['price_overview'] ?? null ) : null;
		$price    = 0;
		if ( is_array( $overview ) && 'USD' === ( $overview['currency'] ?? '' ) ) {
			$price = (int) ( $overview['initial'] ?? 0 );
			if ( $price <= 0 ) {
				$price = (int) ( $overview['final'] ?? 0 );
			}
		}
		$out[ $appid ] = array(
			'found' => true,
			'price' => max( 0, $price ),
		);
	}
	return $out;
}

/**
 * Pausa tra due richieste a Steam (1 secondo), per non pesare sul servizio.
 */
function ntn_core_steam_pause() {
	$seconds = (int) apply_filters( 'ntn_core_steam_pause_seconds', 1 );
	if ( $seconds > 0 ) {
		sleep( $seconds );
	}
}

/**
 * Salva l'ID Steam di un gioco. Se cambia, la vecchia stima non vale più e viene tolta.
 *
 * @param int    $term_id ID del tag.
 * @param string $appid   ID Steam, oppure '' se IGDB non ne conosce uno.
 * @return string Il valore salvato (l'ID oppure 'none').
 */
function ntn_core_steam_save_appid( $term_id, $appid ) {
	$appid = preg_match( '/^[0-9]{1,10}$/', (string) $appid ) && (int) $appid > 0 ? (string) (int) $appid : 'none';
	$old   = (string) get_term_meta( $term_id, '_ntn_core_steam_appid', true );
	update_term_meta( $term_id, '_ntn_core_steam_appid', $appid );
	if ( '' !== $old && $old !== $appid ) {
		foreach ( array( '_ntn_core_steam_reviews', '_ntn_core_steam_price', '_ntn_core_steam_rev_min', '_ntn_core_steam_rev_max', '_ntn_core_steam_rev_status' ) as $key ) {
			delete_term_meta( $term_id, $key );
		}
	}
	return $appid;
}

/**
 * Salva l'esito del controllo di un gioco.
 *
 * @param int   $term_id ID del tag.
 * @param array $review  Riga di ntn_core_steam_fetch_reviews().
 * @param array $price   Riga di ntn_core_steam_fetch_prices().
 * @param int   $now     Ora del controllo.
 * @return string Lo stato salvato.
 */
function ntn_core_steam_store_result( $term_id, $review, $price, $now ) {
	if ( empty( $review['found'] ) || empty( $price['found'] ) ) {
		$result = array( 'status' => 'not_found' );
	} else {
		$result = ntn_core_steam_estimate( (int) $review['reviews'], (int) $price['price'], ! empty( $review['is_free'] ) );
		update_term_meta( $term_id, '_ntn_core_steam_reviews', (int) $review['reviews'] );
		update_term_meta( $term_id, '_ntn_core_steam_price', (int) $price['price'] );
	}
	if ( 'ok' === $result['status'] ) {
		update_term_meta( $term_id, '_ntn_core_steam_rev_min', $result['min'] );
		update_term_meta( $term_id, '_ntn_core_steam_rev_max', $result['max'] );
	} else {
		delete_term_meta( $term_id, '_ntn_core_steam_rev_min' );
		delete_term_meta( $term_id, '_ntn_core_steam_rev_max' );
	}
	update_term_meta( $term_id, '_ntn_core_steam_rev_status', $result['status'] );
	update_term_meta( $term_id, '_ntn_core_steam_rev_updated', (int) $now );
	return $result['status'];
}

/**
 * Aggiorna la stima di alcuni giochi: chiede a IGDB gli ID Steam che mancano (una richiesta),
 * poi a Steam recensioni e prezzi (una richiesta ciascuno, con una pausa in mezzo).
 *
 * Se IGDB o Steam non rispondono non salva niente per quei giochi: restano le stime di prima
 * e si riprova al giro successivo.
 *
 * @param int[] $term_ids ID dei tag dei giochi.
 * @return int|WP_Error Quanti giochi sono stati controllati, oppure l'errore.
 */
function ntn_core_steam_refresh_terms( $term_ids ) {
	$term_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $term_ids ) ) ) );
	if ( empty( $term_ids ) ) {
		return 0;
	}
	$now  = time();
	$done = 0;

	// 1. ID Steam che mancano (anche quelli segnati 'none': un gioco può arrivare su Steam più tardi).
	$need = array();
	foreach ( $term_ids as $term_id ) {
		if ( ctype_digit( (string) get_term_meta( $term_id, '_ntn_core_steam_appid', true ) ) ) {
			continue;
		}
		$igdb_id = (int) get_term_meta( $term_id, '_ntn_igdb_game_id', true );
		if ( $igdb_id > 0 ) {
			$need[ $igdb_id ][] = $term_id;
		} else {
			ntn_core_steam_save_appid( $term_id, '' );
		}
	}
	if ( ! empty( $need ) ) {
		if ( ! function_exists( 'ntn_core_igdb_request' ) ) {
			return new WP_Error( 'ntn_steam_igdb', 'Il modulo IGDB non è caricato.' );
		}
		$games = ntn_core_igdb_request( 'fields id, ' . NTN_CORE_STEAM_IGDB_FIELDS . '; where id = (' . implode( ',', array_keys( $need ) ) . '); limit ' . count( $need ) . ';', 10 );
		if ( is_wp_error( $games ) ) {
			return $games;
		}
		$found = array();
		foreach ( $games as $game ) {
			$found[ (int) ( $game['id'] ?? 0 ) ] = ntn_core_steam_appid_from_igdb( $game );
		}
		foreach ( $need as $igdb_id => $ids ) {
			foreach ( $ids as $term_id ) {
				ntn_core_steam_save_appid( $term_id, $found[ $igdb_id ] ?? '' );
			}
		}
	}

	// 2. Giochi senza pagina Steam: niente stima.
	$by_appid = array();
	foreach ( $term_ids as $term_id ) {
		$appid = (string) get_term_meta( $term_id, '_ntn_core_steam_appid', true );
		if ( ctype_digit( $appid ) ) {
			$by_appid[ (int) $appid ][] = $term_id;
			continue;
		}
		foreach ( array( '_ntn_core_steam_reviews', '_ntn_core_steam_price', '_ntn_core_steam_rev_min', '_ntn_core_steam_rev_max' ) as $key ) {
			delete_term_meta( $term_id, $key );
		}
		update_term_meta( $term_id, '_ntn_core_steam_rev_status', 'no_appid' );
		update_term_meta( $term_id, '_ntn_core_steam_rev_updated', $now );
		++$done;
	}
	if ( empty( $by_appid ) ) {
		return $done;
	}

	// 3. Recensioni e prezzi da Steam.
	$reviews = ntn_core_steam_fetch_reviews( array_keys( $by_appid ) );
	if ( is_wp_error( $reviews ) ) {
		return $reviews;
	}
	ntn_core_steam_pause();
	$prices = ntn_core_steam_fetch_prices( array_keys( $by_appid ) );
	if ( is_wp_error( $prices ) ) {
		return $prices;
	}
	foreach ( $by_appid as $appid => $ids ) {
		foreach ( $ids as $term_id ) {
			ntn_core_steam_store_result( $term_id, $reviews[ $appid ] ?? array(), $prices[ $appid ] ?? array(), $now );
			++$done;
		}
	}
	return $done;
}

/**
 * I giochi da controllare: prima quelli appena collegati o aggiornati su IGDB (data 0),
 * poi quelli mai controllati, poi i più vecchi.
 *
 * @param int $limit Quanti al massimo.
 * @return int[] ID dei tag.
 */
function ntn_core_steam_due_terms( $limit ) {
	global $wpdb;
	$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- lavoro in secondo piano, nessuna cache utile.
		$wpdb->prepare(
			"SELECT g.term_id FROM {$wpdb->termmeta} g
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = g.term_id AND tt.taxonomy = 'post_tag'
			LEFT JOIN {$wpdb->termmeta} u ON u.term_id = g.term_id AND u.meta_key = '_ntn_core_steam_rev_updated'
			WHERE g.meta_key = '_ntn_igdb_game_id' AND g.meta_value <> ''
			AND ( u.meta_value IS NULL OR CAST( u.meta_value AS UNSIGNED ) < %d )
			GROUP BY g.term_id
			ORDER BY MIN( CAST( COALESCE( u.meta_value, '1' ) AS UNSIGNED ) ) ASC, g.term_id ASC
			LIMIT %d",
			time() - NTN_CORE_STEAM_MAX_AGE,
			(int) $limit
		)
	);
	return array_map( 'intval', (array) $ids );
}

/**
 * Un lotto del giro settimanale. Se restano altri giochi da controllare, programma il lotto dopo
 * fra un minuto. Se IGDB o Steam non rispondono si ferma: riprova il giro della settimana dopo.
 */
function ntn_core_steam_run_batch() {
	$ids = ntn_core_steam_due_terms( NTN_CORE_STEAM_BATCH );
	if ( empty( $ids ) ) {
		return;
	}
	$done = ntn_core_steam_refresh_terms( $ids );
	if ( is_wp_error( $done ) || $done <= 0 ) {
		return;
	}
	if ( count( $ids ) >= NTN_CORE_STEAM_BATCH && ! wp_next_scheduled( NTN_CORE_STEAM_BATCH_HOOK ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, NTN_CORE_STEAM_BATCH_HOOK );
	}
}
add_action( NTN_CORE_STEAM_WEEKLY_HOOK, 'ntn_core_steam_run_batch' );
add_action( NTN_CORE_STEAM_BATCH_HOOK, 'ntn_core_steam_run_batch' );

/**
 * Programma il giro settimanale (controllo leggero, solo nel pannello).
 */
function ntn_core_steam_schedule() {
	if ( ! wp_next_scheduled( NTN_CORE_STEAM_WEEKLY_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', NTN_CORE_STEAM_WEEKLY_HOOK );
	}
}
add_action( 'admin_init', 'ntn_core_steam_schedule' );

/**
 * Dopo un collegamento o un aggiornamento IGDB: salva l'ID Steam e mette il gioco in testa alla coda,
 * così la stima arriva entro un minuto (in secondo piano, senza rallentare il pannello).
 *
 * @param int    $term_id ID del tag.
 * @param string $appid   ID Steam letto da IGDB ('' se non c'è).
 */
function ntn_core_steam_after_igdb_sync( $term_id, $appid ) {
	$term_id = (int) $term_id;
	if ( $term_id <= 0 ) {
		return;
	}
	ntn_core_steam_save_appid( $term_id, (string) $appid );
	update_term_meta( $term_id, '_ntn_core_steam_rev_updated', 0 ); // 0 = da controllare per primo.
	if ( ! wp_next_scheduled( NTN_CORE_STEAM_BATCH_HOOK ) ) {
		wp_schedule_single_event( time() + 30, NTN_CORE_STEAM_BATCH_HOOK );
	}
}

/**
 * Una cifra in dollari in forma breve, per il pannello: "$86 mila", "$95,3 mln", "$1,25 mld".
 *
 * @param int $dollars Dollari.
 * @return string
 */
function ntn_core_steam_money_short( $dollars ) {
	$dollars = (int) $dollars;
	if ( $dollars >= 1000000000 ) {
		return '$' . number_format_i18n( $dollars / 1000000000, 2 ) . ' mld';
	}
	if ( $dollars >= 1000000 ) {
		return '$' . number_format_i18n( $dollars / 1000000, 1 ) . ' mln';
	}
	if ( $dollars >= 1000 ) {
		return '$' . number_format_i18n( $dollars / 1000 ) . ' mila';
	}
	return '$' . number_format_i18n( $dollars );
}

/**
 * Il valore "circa" per l'intestazione della Hub: "~576 mln $", "~1,2 mld $", "~4,2 mln $", "~86 mila $".
 *
 * @param int $dollars Dollari.
 * @return string
 */
function ntn_core_steam_amount_short( $dollars ) {
	$dollars = (int) $dollars;
	if ( $dollars >= 1000000000 ) {
		return '~' . number_format_i18n( $dollars / 1000000000, 1 ) . ' mld $';
	}
	if ( $dollars >= 1000000 ) {
		return '~' . number_format_i18n( $dollars / 1000000, $dollars >= 10000000 ? 0 : 1 ) . ' mln $';
	}
	if ( $dollars >= 1000 ) {
		return '~' . number_format_i18n( $dollars / 1000 ) . ' mila $';
	}
	return '~' . number_format_i18n( $dollars ) . ' $';
}

/**
 * La cella "Introiti Steam (stima)" della tabella dei tag collegati, scheda IGDB del pannello.
 * Sola lettura: mostra i dati salvati, non fa richieste. Il testo è già protetto (escape).
 *
 * @param int $term_id ID del tag del gioco.
 * @return string HTML.
 */
function ntn_core_steam_admin_cell( $term_id ) {
	$term_id = (int) $term_id;
	$status  = (string) get_term_meta( $term_id, '_ntn_core_steam_rev_status', true );
	$updated = (int) get_term_meta( $term_id, '_ntn_core_steam_rev_updated', true );
	$reviews = (int) get_term_meta( $term_id, '_ntn_core_steam_reviews', true );
	$appid   = (string) get_term_meta( $term_id, '_ntn_core_steam_appid', true );
	$reasons = array(
		'free'        => 'Gratuito: nessuna stima',
		'no_price'    => 'Senza prezzo su Steam (forse non ancora in vendita)',
		'no_appid'    => 'Non su Steam (IGDB non ha una pagina Steam)',
		'few_reviews' => 'Poche recensioni (' . number_format_i18n( $reviews ) . ', ne servono ' . ntn_core_steam_params()['min_reviews'] . ')',
		'not_found'   => 'Steam non trova il gioco',
	);

	$revenue = ntn_core_steam_revenue( $term_id );
	if ( $revenue ) {
		$main  = '<strong>~' . esc_html( ntn_core_steam_money_short( $revenue['value'] ) ) . '</strong>';
		$title = 'circa $' . number_format_i18n( $revenue['value'] ) . ' netti';
		$info  = number_format_i18n( $revenue['reviews'] ) . ' recensioni · $' . number_format_i18n( $revenue['price'], 2 );
	} elseif ( isset( $reasons[ $status ] ) ) {
		$main  = '<span style="opacity: .75;">' . esc_html( $reasons[ $status ] ) . '</span>';
		$title = '';
		$info  = '';
	} else {
		return '<span style="opacity: .6;">In attesa del primo controllo</span>';
	}

	$details = array();
	if ( '' !== $info ) {
		$details[] = $info;
	}
	if ( $updated > 0 ) {
		$details[] = 'aggiornata il ' . wp_date( 'd/m/Y', $updated );
	}
	if ( ctype_digit( $appid ) ) {
		$details[] = 'Steam ' . $appid;
	}
	return '<span' . ( '' !== $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>' . $main . '</span>' .
		( $details ? '<br><small style="opacity: .7;">' . esc_html( implode( ' · ', $details ) ) . '</small>' : '' );
}

/**
 * La stima salvata di un gioco, pronta da mostrare (per la fase 2, il riquadro nella Hub).
 * Non fa mai richieste esterne.
 *
 * @param int $term_id ID del tag del gioco.
 * @return array|null array( 'value' (dollari, il valore mostrato: il centro tra min e max), 'min', 'max',
 *                    'reviews', 'price' (dollari), 'appid', 'updated' ), oppure null se non c'è una stima.
 */
function ntn_core_steam_revenue( $term_id ) {
	$term_id = (int) $term_id;
	if ( $term_id <= 0 || 'ok' !== get_term_meta( $term_id, '_ntn_core_steam_rev_status', true ) ) {
		return null;
	}
	$min = (int) get_term_meta( $term_id, '_ntn_core_steam_rev_min', true );
	$max = (int) get_term_meta( $term_id, '_ntn_core_steam_rev_max', true );
	if ( $min <= 0 || $max < $min ) {
		return null;
	}
	return array(
		'value'   => (int) round( ( $min + $max ) / 2 ),
		'min'     => $min,
		'max'     => $max,
		'reviews' => (int) get_term_meta( $term_id, '_ntn_core_steam_reviews', true ),
		'price'   => (int) get_term_meta( $term_id, '_ntn_core_steam_price', true ) / 100,
		'appid'   => (string) get_term_meta( $term_id, '_ntn_core_steam_appid', true ),
		'updated' => (int) get_term_meta( $term_id, '_ntn_core_steam_rev_updated', true ),
	);
}
