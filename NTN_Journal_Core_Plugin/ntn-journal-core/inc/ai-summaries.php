<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ==============================================================================
 * GEMINI API: AUTOGENERATE EXCERPT (SOTTOTITOLO) - REST API & GUTENBERG INTEGRATION
 * ==============================================================================
 */

// 1. Registrazione campi Meta per esporli alle REST API
function ntn_journal_register_ai_excerpt_meta() {
	register_post_meta( 'post', '_is_ai_excerpt', array(
		'show_in_rest'  => true,
		'single'        => true,
		'type'          => 'string',
		'auth_callback' => function() {
			return current_user_can( 'edit_posts' );
		}
	) );

	register_post_meta( 'post', '_ai_generated_excerpt_text', array(
		'show_in_rest'  => true,
		'single'        => true,
		'type'          => 'string',
		'auth_callback' => function() {
			return current_user_can( 'edit_posts' );
		}
	) );
}
add_action( 'init', 'ntn_journal_register_ai_excerpt_meta' );

// 2. Registrazione Endpoint REST per la generazione On-Demand
function ntn_journal_register_ai_rest_route() {
	register_rest_route( 'ntn/v1', '/generate-summary', array(
		'methods'  => 'POST',
		'callback' => 'ntn_journal_rest_generate_summary',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		}
	) );
}
add_action( 'rest_api_init', 'ntn_journal_register_ai_rest_route' );

// Callback dell'Endpoint REST
function ntn_journal_rest_generate_summary( WP_REST_Request $request ) {
	$post_id = $request->get_param( 'post_id' );
	if ( empty( $post_id ) ) {
		return new WP_Error( 'missing_post_id', 'ID del post mancante.', array( 'status' => 400 ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || empty( $post->post_content ) ) {
		return new WP_Error( 'empty_content', 'Il post è vuoto o non esiste.', array( 'status' => 400 ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'unauthorized', 'Non hai i permessi per generare il riassunto per questo post.', array( 'status' => 403 ) );
	}

	$clean_content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$content_chunk = mb_substr( $clean_content, 0, 3000 );
	
	if ( empty( trim( $content_chunk ) ) ) {
		return new WP_Error( 'empty_content', 'Nessun testo valido estratto per il riassunto.', array( 'status' => 400 ) );
	}

	$prompt = "Sei un giornalista videoludico e un esperto SEO. Scrivi un riassunto (meta description) di alta qualità per il testo fornito, ottimizzato per i motori di ricerca. Cattura il nucleo della notizia includendo le parole chiave in modo naturale, per massimizzare il CTR. Evita incipit didascalici o banali (es. 'X è un videogioco...').\nRegole FONDAMENTALI:\n1. Ottimizzazione SEO: tono puramente informativo, autorevole e discorsivo.\n2. DIVIETO DI CALL-TO-ACTION: Non usare MAI verbi all'imperativo (es. 'Scopri', 'Leggi', 'Guarda') né frasi promozionali o commerciali.\n3. Scrivi UNA o DUE frasi articolate.\n4. Lunghezza rigorosa: avvicinati a 140 caratteri totali (massimo 145), circa 20-25 parole.\n5. Restituisci ESCLUSIVAMENTE il testo finale concluso con il punto, senza virgolette né introduzioni.\n\nTesto: " . $content_chunk;
	
	$transient_key = 'ntn_ai_summary_' . md5( $content_chunk );
	$cached_summary = get_transient( $transient_key );
	if ( false !== $cached_summary ) {
		return rest_ensure_response( array(
			'success' => true,
			'excerpt' => $cached_summary,
		) );
	}

	$generated_excerpt = '';

	// Tentativo 1: Gemini
	$gemini_key = get_option( 'ntn_gemini_api_key' );
	if ( ! empty( $gemini_key ) ) {
		$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $gemini_key;
		$body = array(
			'contents' => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
			'generationConfig' => array( 'temperature' => 0.7 ),
			'safetySettings' => array(
				array( 'category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE' ),
				array( 'category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE' ),
				array( 'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE' ),
				array( 'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE' )
			)
		);

		$response = wp_remote_post( $api_url, array(
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => 8,
		) );

		// v3.17.1: lo stato della chiave nella scheda "Servizi API"
		if ( function_exists( 'ntn_core_api_status_set' ) ) {
			$gemini_reason = ntn_core_api_error_reason( $response );
			ntn_core_api_status_set( 'gemini', '' === $gemini_reason, $gemini_reason );
		}

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body_json = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body_json['candidates'][0]['content']['parts'][0]['text'] ) ) {
				$generated_excerpt = trim( $body_json['candidates'][0]['content']['parts'][0]['text'] );
			}
		}
	}

	// Tentativo 2: Groq (Fallback)
	$groq_key = get_option( 'ntn_groq_api_key' );
	if ( empty( $generated_excerpt ) && ! empty( $groq_key ) ) {
		$groq_url = 'https://api.groq.com/openai/v1/chat/completions';
		$groq_body = array(
			'model' => 'llama-3.3-70b-versatile',
			'messages' => array(
				array(
					'role' => 'system',
					'content' => "Sei un giornalista videoludico e un esperto SEO. Scrivi un riassunto (meta description) di alta qualità per un articolo, ottimizzato per i motori di ricerca. Cattura la notizia centrale includendo le parole chiave in modo naturale per massimizzare il CTR. Evita incipit banali o enciclopedici (es. 'X è un gioco...').\nREGOLE ASSOLUTE E INVALICABILI:\n1. Ottimizzazione SEO: tono puramente informativo e autorevole, mai commerciale.\n2. DIVIETO DI CALL-TO-ACTION: Non usare MAI verbi all'imperativo (es. 'Scopri', 'Leggi', 'Non perderti') o chiusure promozionali.\n3. Scrivi UNA o DUE frasi articolate al massimo.\n4. Lunghezza rigorosa: tra 130 e 145 caratteri (spazi inclusi), ovvero circa 20-25 parole.\n5. Nessun saluto o introduzione. Restituisci SOLO il testo finale concluso col punto."
				),
				array(
					'role' => 'user',
					'content' => "Estrai un riassunto giornalistico perfetto da questo testo:\n\n" . $content_chunk
				)
			),
			'temperature' => 0.4,
			'max_tokens' => 80
		);

		$groq_response = wp_remote_post( $groq_url, array(
			'headers'     => array( 
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $groq_key
			),
			'body'        => wp_json_encode( $groq_body ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => 8,
		) );

		if ( function_exists( 'ntn_core_api_status_set' ) ) {
			$groq_reason = ntn_core_api_error_reason( $groq_response );
			ntn_core_api_status_set( 'groq', '' === $groq_reason, $groq_reason );
		}

		if ( ! is_wp_error( $groq_response ) && 200 === wp_remote_retrieve_response_code( $groq_response ) ) {
			$groq_json = json_decode( wp_remote_retrieve_body( $groq_response ), true );
			if ( isset( $groq_json['choices'][0]['message']['content'] ) ) {
				$generated_excerpt = trim( $groq_json['choices'][0]['message']['content'] );
			}
		}
	}

	if ( empty( $generated_excerpt ) ) {
		return new WP_Error( 'api_failed', 'Nessuna API è riuscita a generare il riassunto. Controlla le chiavi API o riprova più tardi.', array( 'status' => 500 ) );
	}

	$generated_excerpt = preg_replace( '/^[\'"]|[\'"]$/', '', $generated_excerpt );

	set_transient( $transient_key, $generated_excerpt, MONTH_IN_SECONDS );

	return rest_ensure_response( array(
		'success' => true,
		'excerpt' => $generated_excerpt,
	) );
}

// 3. Controllo rimozione badge su salvataggio manuale
function ntn_journal_check_ai_excerpt_modification( $post_id, $post, $update ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'post' !== $post->post_type ) {
		return;
	}

	$last_ai_text = get_post_meta( $post_id, '_ai_generated_excerpt_text', true );
	
	if ( ! empty( $last_ai_text ) && ! empty( $post->post_excerpt ) ) {
		$normalized_excerpt = preg_replace('/\s+/', ' ', trim( wp_strip_all_tags( $post->post_excerpt ) ) );
		$normalized_last_ai = preg_replace('/\s+/', ' ', trim( wp_strip_all_tags( $last_ai_text ) ) );
		
		if ( $normalized_excerpt !== $normalized_last_ai ) {
			// L'utente ha modificato manualmente l'estratto, quindi rimuoviamo il badge AI
			delete_post_meta( $post_id, '_is_ai_excerpt' );
			delete_post_meta( $post_id, '_ai_generated_excerpt_text' );
		}
	} else if ( empty( $post->post_excerpt ) && ! empty( $last_ai_text ) ) {
        // Se l'utente svuota l'estratto manualmente, rimuoviamo i meta
        delete_post_meta( $post_id, '_is_ai_excerpt' );
        delete_post_meta( $post_id, '_ai_generated_excerpt_text' );
    }
}
add_action( 'save_post', 'ntn_journal_check_ai_excerpt_modification', 10, 3 );

/**
 * Enqueue block editor assets for Gutenberg (AI Summary Plugin)
 */
function ntn_journal_block_editor_assets() {
	wp_enqueue_script(
		'ntn-ai-summary-gutenberg-js',
		NTN_JOURNAL_CORE_URL . 'assets/js/ai-summary-gutenberg.js',
		array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch' ),
		filemtime( NTN_JOURNAL_CORE_DIR . 'assets/js/ai-summary-gutenberg.js' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'ntn_journal_block_editor_assets' );

