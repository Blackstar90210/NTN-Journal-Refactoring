<?php
/**
 * NTN Journal Core - Data vera di caricamento dei trailer YouTube (v4.22.0)
 *
 * Nei dati per Google (JSON-LD) ogni trailer è un VideoObject con il campo "uploadDate".
 * Fino alla 4.21.0 il campo conteneva la data di pubblicazione dell'articolo, che non è la data in cui
 * il video è stato caricato su YouTube. Con una chiave API di YouTube (scheda "Servizi API") il plugin
 * legge la data vera, la salva e la usa. Senza chiave, o se YouTube non risponde, il sito si comporta
 * come prima: nessuna richiesta a YouTube mentre la pagina si carica, mai un errore per il visitatore.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NTN_CORE_YT_DATE_PREFIX', 'ntn_yt_date_' );
define( 'NTN_CORE_YT_DATE_HOOK', 'ntn_core_youtube_date_lookup' );

/**
 * Pulisce l'identificativo di un video YouTube (11 caratteri). Restituisce '' se non è valido.
 *
 * @param mixed $video_id Identificativo o parte di indirizzo.
 * @return string
 */
function ntn_core_youtube_clean_id( $video_id ) {
	if ( ! is_string( $video_id ) ) {
		return '';
	}
	return preg_match( '/^[A-Za-z0-9_-]{11}/', trim( $video_id ), $m ) ? $m[0] : '';
}

/**
 * La chiave API di YouTube salvata ('' se manca).
 *
 * @return string
 */
function ntn_core_youtube_api_key() {
	return trim( (string) get_option( 'ntn_youtube_api_key', '' ) );
}

/**
 * Chiede a YouTube la data di caricamento di un video.
 *
 * @param string $video_id Identificativo già pulito.
 * @return string|WP_Error Data ISO 8601, oppure errore: 'ntn_yt_missing_key', 'ntn_yt_http', 'ntn_yt_not_found'.
 */
function ntn_core_youtube_request_date( $video_id ) {
	$key = ntn_core_youtube_api_key();
	if ( '' === $key ) {
		return new WP_Error( 'ntn_yt_missing_key', 'Chiave API di YouTube mancante.' );
	}
	$url      = add_query_arg(
		array(
			'part'   => 'snippet',
			'id'     => $video_id,
			'fields' => 'items(snippet(publishedAt))',
		),
		'https://www.googleapis.com/youtube/v3/videos'
	);
	$response = wp_remote_get(
		$url,
		array(
			'headers' => array( 'x-goog-api-key' => $key ), // La chiave non finisce nell'indirizzo
			'timeout' => 8,
		)
	);
	$reason   = function_exists( 'ntn_core_api_error_reason' ) ? ntn_core_api_error_reason( $response ) : '';
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'ntn_yt_http', '' !== $reason ? $reason : 'YouTube non risponde.' );
	}
	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$date = is_array( $body ) && isset( $body['items'][0]['snippet']['publishedAt'] ) ? (string) $body['items'][0]['snippet']['publishedAt'] : '';
	if ( '' === $date || false === strtotime( $date ) ) {
		return new WP_Error( 'ntn_yt_not_found', 'Video non trovato (privato o rimosso).' );
	}
	return $date;
}

/**
 * Lavoro in secondo piano: chiede la data di un video e la salva.
 * Esito buono: per un anno. Video non trovato: 7 giorni. Errore di rete o di chiave: 1 ora.
 *
 * @param string $video_id Identificativo del video.
 */
function ntn_core_youtube_fetch_date( $video_id ) {
	$video_id = ntn_core_youtube_clean_id( $video_id );
	if ( '' === $video_id ) {
		return;
	}
	$result = ntn_core_youtube_request_date( $video_id );
	$name   = NTN_CORE_YT_DATE_PREFIX . $video_id;
	if ( ! is_wp_error( $result ) ) {
		set_transient( $name, $result, YEAR_IN_SECONDS );
		if ( function_exists( 'ntn_core_api_status_set' ) ) {
			ntn_core_api_status_set( 'youtube', true );
		}
		return;
	}
	if ( 'ntn_yt_not_found' === $result->get_error_code() ) {
		set_transient( $name, 'none', 7 * DAY_IN_SECONDS );
		return;
	}
	set_transient( $name, 'none', HOUR_IN_SECONDS );
	if ( function_exists( 'ntn_core_api_status_set' ) ) {
		ntn_core_api_status_set( 'youtube', false, $result->get_error_message() );
	}
}
add_action( NTN_CORE_YT_DATE_HOOK, 'ntn_core_youtube_fetch_date' );

/**
 * La data da scrivere in "uploadDate" per un trailer.
 *
 * Non fa mai richieste a YouTube durante il caricamento della pagina: se la data non è ancora salvata,
 * mette in coda un lavoro in secondo piano e per ora restituisce il ripiego.
 *
 * @param string $video_id Identificativo del video (o parte di indirizzo che lo contiene all'inizio).
 * @param string $fallback Data da usare se non c'è quella vera (la data dell'articolo, come prima).
 * @return string
 */
function ntn_core_youtube_upload_date( $video_id, $fallback = '' ) {
	$id = ntn_core_youtube_clean_id( $video_id );
	if ( '' === $id || '' === ntn_core_youtube_api_key() ) {
		return $fallback;
	}
	$stored = get_transient( NTN_CORE_YT_DATE_PREFIX . $id );
	if ( is_string( $stored ) && 'none' !== $stored && false !== strtotime( $stored ) ) {
		return $stored;
	}
	if ( false === $stored && ! wp_next_scheduled( NTN_CORE_YT_DATE_HOOK, array( $id ) ) ) {
		wp_schedule_single_event( time() + 10, NTN_CORE_YT_DATE_HOOK, array( $id ) );
	}
	return $fallback;
}

/**
 * Prova della chiave per il pulsante "Verifica": legge la data di un video pubblico di YouTube.
 * Costa 1 unità sulle 10.000 giornaliere gratuite.
 *
 * @return string Vuoto se la chiave funziona, altrimenti il motivo.
 */
function ntn_core_youtube_verify_key() {
	$result = ntn_core_youtube_request_date( 'dQw4w9WgXcQ' );
	return is_wp_error( $result ) && 'ntn_yt_not_found' !== $result->get_error_code() ? $result->get_error_message() : '';
}
