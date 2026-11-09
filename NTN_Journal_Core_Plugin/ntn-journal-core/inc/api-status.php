<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Stato delle chiavi API (v3.17.1, scelta del CEO).
 *
 * Per ogni servizio (Gemini, Groq, IGDB, YouTube) la scheda "Servizi API" mostra:
 * - "Attiva": l'ultima richiesta al servizio è andata a buon fine;
 * - "Salvata, non verificata": la chiave c'è ma non è ancora stata usata;
 * - "Errore": l'ultima richiesta è fallita, con il motivo;
 * - "Mancante": nessuna chiave.
 * Le chiavi non vengono mai scritte nella pagina. Il pulsante "Verifica" fa una richiesta
 * di prova che non consuma crediti (elenco dei modelli per Gemini e Groq, accesso Twitch per IGDB).
 *
 * @package NTN_Journal_Core
 */

/**
 * Servizi gestiti, con le opzioni che contengono le loro chiavi.
 *
 * @return array<string,array{label:string,options:array<string,string>}>
 */
function ntn_core_api_services() {
	return array(
		'gemini' => array(
			'label'   => 'Google Gemini',
			'options' => array( 'ntn_gemini_api_key' => 'API Key' ),
		),
		'groq'   => array(
			'label'   => 'Groq (riserva)',
			'options' => array( 'ntn_groq_api_key' => 'API Key' ),
		),
		'igdb'   => array(
			'label'   => 'IGDB (Twitch)',
			'options' => array(
				'ntn_igdb_client_id'     => 'Client ID',
				'ntn_igdb_client_secret' => 'Client Secret',
			),
		),
		'youtube' => array(
			'label'   => 'YouTube (data dei trailer)',
			'options' => array( 'ntn_youtube_api_key' => 'API Key' ),
		),
	);
}

/**
 * Registra l'esito dell'ultima richiesta a un servizio.
 *
 * @param string $service gemini | groq | igdb.
 * @param bool   $ok      Esito.
 * @param string $message Motivo dell'errore (vuoto se ok).
 */
function ntn_core_api_status_set( $service, $ok, $message = '' ) {
	$all             = get_option( 'ntn_api_status', array() );
	$all             = is_array( $all ) ? $all : array();
	$all[ $service ] = array(
		'state'   => $ok ? 'active' : 'error',
		'message' => $ok ? '' : sanitize_text_field( (string) $message ),
		'time'    => time(),
	);
	update_option( 'ntn_api_status', $all, false );
}

/**
 * Azzera lo stato di un servizio (chiave cambiata o cancellata).
 *
 * @param string $service Servizio.
 */
function ntn_core_api_status_reset( $service ) {
	$all = get_option( 'ntn_api_status', array() );
	if ( is_array( $all ) && isset( $all[ $service ] ) ) {
		unset( $all[ $service ] );
		update_option( 'ntn_api_status', $all, false );
	}
}

/**
 * Stato di un servizio da mostrare nella scheda.
 *
 * @param string $service Servizio.
 * @return array{state:string,label:string,message:string,time:int}
 */
function ntn_core_api_status_get( $service ) {
	$services = ntn_core_api_services();
	if ( ! isset( $services[ $service ] ) ) {
		return array( 'state' => 'missing', 'label' => 'Mancante', 'message' => '', 'time' => 0 );
	}
	foreach ( array_keys( $services[ $service ]['options'] ) as $option ) {
		if ( '' === trim( (string) get_option( $option, '' ) ) ) {
			return array( 'state' => 'missing', 'label' => 'Mancante', 'message' => '', 'time' => 0 );
		}
	}
	$all  = get_option( 'ntn_api_status', array() );
	$info = is_array( $all ) && isset( $all[ $service ] ) && is_array( $all[ $service ] ) ? $all[ $service ] : null;
	if ( ! $info ) {
		return array( 'state' => 'saved', 'label' => 'Salvata, non verificata', 'message' => '', 'time' => 0 );
	}
	return array(
		'state'   => 'active' === $info['state'] ? 'active' : 'error',
		'label'   => 'active' === $info['state'] ? 'Attiva' : 'Errore',
		'message' => isset( $info['message'] ) ? (string) $info['message'] : '',
		'time'    => isset( $info['time'] ) ? (int) $info['time'] : 0,
	);
}

/**
 * Motivo leggibile di una risposta HTTP non riuscita.
 *
 * @param array|WP_Error $response Risposta di wp_remote_*.
 * @return string Vuoto se la risposta è andata a buon fine.
 */
function ntn_core_api_error_reason( $response ) {
	if ( is_wp_error( $response ) ) {
		return 'Connessione non riuscita: ' . $response->get_error_message();
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 === $code ) {
		return '';
	}
	if ( in_array( $code, array( 400, 401, 403 ), true ) ) {
		return 'Chiave non valida, scaduta o senza permessi (codice ' . $code . ').';
	}
	if ( 429 === $code ) {
		return 'Limite di richieste raggiunto: riprova più tardi (codice 429).';
	}
	return 'Il servizio non risponde come previsto (codice ' . $code . ').';
}

/**
 * Richiesta di prova che non consuma crediti.
 *
 * @param string $service gemini | groq | igdb | youtube.
 * @return array{state:string,label:string,message:string,time:int}
 */
function ntn_core_api_verify( $service ) {
	$status = ntn_core_api_status_get( $service );
	if ( 'missing' === $status['state'] ) {
		return $status;
	}

	if ( 'gemini' === $service ) {
		$response = wp_remote_get(
			'https://generativelanguage.googleapis.com/v1beta/models?pageSize=1',
			array(
				'headers' => array( 'x-goog-api-key' => (string) get_option( 'ntn_gemini_api_key', '' ) ),
				'timeout' => 10,
			)
		);
		$reason = ntn_core_api_error_reason( $response );
		ntn_core_api_status_set( 'gemini', '' === $reason, $reason );
	} elseif ( 'groq' === $service ) {
		$response = wp_remote_get(
			'https://api.groq.com/openai/v1/models',
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . (string) get_option( 'ntn_groq_api_key', '' ) ),
				'timeout' => 10,
			)
		);
		$reason = ntn_core_api_error_reason( $response );
		ntn_core_api_status_set( 'groq', '' === $reason, $reason );
	} elseif ( 'youtube' === $service && function_exists( 'ntn_core_youtube_verify_key' ) ) {
		$reason = ntn_core_youtube_verify_key();
		ntn_core_api_status_set( 'youtube', '' === $reason, $reason );
	} elseif ( 'igdb' === $service && function_exists( 'ntn_get_igdb_token' ) ) {
		delete_transient( 'ntn_igdb_access_token' ); // Chiede un accesso nuovo, così la prova è vera
		ntn_get_igdb_token(); // Registra da sola l'esito
	}

	return ntn_core_api_status_get( $service );
}
