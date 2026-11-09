<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Register Custom Meta Box for Review Details.
 */
function ntn_add_review_meta_box() {
	add_meta_box(
		'ntn_review_meta_box',
		'Box Recensione',
		'ntn_render_review_meta_box',
		'post',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ntn_add_review_meta_box' );

function ntn_render_review_meta_box( $post ) {
	// Retrieve current values
	$rating = get_post_meta( $post->ID, 'rating', true );
	$pros = get_post_meta( $post->ID, 'pros', true );
	$cons = get_post_meta( $post->ID, 'cons', true );
	$verdict = get_post_meta( $post->ID, 'verdict', true );
	$reviewed_item = get_post_meta( $post->ID, 'reviewed_item', true );
	$review_source = get_post_meta( $post->ID, 'ntn_review_source', true );

	// Convert arrays to string if they are stored as arrays
	if ( is_array( $pros ) ) {
		$pros = implode( "\n", $pros );
	}
	if ( is_array( $cons ) ) {
		$cons = implode( "\n", $cons );
	}

	// Add nonce for security
	wp_nonce_field( 'ntn_save_review_meta', 'ntn_review_meta_nonce' );
	?>
	<div style="padding: 15px 0;">
		<p class="description" style="margin-bottom: 25px; font-style: italic;">Compila questi campi per gli articoli con categoria "Recensione" per abilitare il Box Recensione grafico a fine articolo.</p>
		
		<div style="margin-bottom: 20px; border-left: 4px solid #ef4444; padding-left: 15px;">
			<label for="ntn_reviewed_item" style="display: block; font-weight: 800; margin-bottom: 8px; font-family: monospace; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">// Gioco / Prodotto Recensito</label>
			<input type="text" id="ntn_reviewed_item" name="ntn_reviewed_item" value="<?php echo esc_attr( $reviewed_item ); ?>" style="width: 100%; max-width: 600px; padding: 8px 12px; font-size: 14px; border-radius: 0;" placeholder="es. Death Stranding 2" />
			<p class="description" style="margin: 6px 0 0; font-size: 12px;">Se lasciato vuoto, verrà utilizzato il titolo dell'articolo.</p>
		</div>

		<div style="margin-bottom: 20px; border-left: 4px solid #3b82f6; padding-left: 15px;">
			<label for="ntn_review_source" style="display: block; font-weight: 800; margin-bottom: 8px; font-family: monospace; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">// Origine / Provenienza</label>
			<select id="ntn_review_source" name="ntn_review_source" style="width: 100%; max-width: 600px; padding: 8px 12px; font-size: 14px; border-radius: 0;">
				<option value="" <?php selected( $review_source, '' ); ?>>Non specificata</option>
				<option value="Fornito dall'editore" <?php selected( $review_source, "Fornito dall'editore" ); ?>>Fornito dall'editore</option>
				<option value="Fornito dallo studio di sviluppo" <?php selected( $review_source, "Fornito dallo studio di sviluppo" ); ?>>Fornito dallo studio di sviluppo</option>
				<option value="Acquistato dall'autore" <?php selected( $review_source, "Acquistato dall'autore" ); ?>>Acquistato dall'autore</option>
			</select>
			<p class="description" style="margin: 6px 0 0; font-size: 12px;">Indica come è stato ottenuto il gioco (verrà mostrato nel box recensione).</p>
		</div>

		<div style="margin-bottom: 20px; border-left: 4px solid #ef4444; padding-left: 15px;">
			<label for="ntn_rating" style="display: block; font-weight: 800; margin-bottom: 8px; font-family: monospace; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">// Voto Finale (da 1 a 5)</label>
			<div style="display: flex; align-items: center; gap: 15px;">
				<input type="number" id="ntn_rating" name="ntn_rating" value="<?php echo esc_attr( $rating ); ?>" step="1" min="1" max="5" style="width: 120px; padding: 8px 12px; font-size: 16px; font-weight: bold; font-family: monospace; border-radius: 0;" placeholder="es. 4" />
				<span id="ntn_rating_dynamic_label" style="display: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-family: monospace; font-size: 12px; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;"></span>
			</div>
			<p class="description" style="margin: 6px 0 0; font-size: 12px;">Inserisci un valore numerico intero (da 1 a 5).</p>
		</div>

		<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
			<div style="flex: 1; min-width: 300px; border-left: 4px solid #22c55e; padding-left: 15px;">
				<label for="ntn_pros" style="display: block; font-weight: 800; margin-bottom: 8px; font-family: monospace; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">// PRO (uno per riga)</label>
				<textarea id="ntn_pros" name="ntn_pros" rows="5" style="width: 100%; padding: 10px 12px; font-family: monospace; font-size: 13px; border-radius: 0;" placeholder="es. Gameplay stratificato e profondo&#10;Colonna sonora magistrale&#10;Grafica all'avanguardia"><?php echo esc_textarea( $pros ); ?></textarea>
			</div>

			<div style="flex: 1; min-width: 300px; border-left: 4px solid #ef4444; padding-left: 15px;">
				<label for="ntn_cons" style="display: block; font-weight: 800; margin-bottom: 8px; font-family: monospace; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">// CONTRO (uno per riga)</label>
				<textarea id="ntn_cons" name="ntn_cons" rows="5" style="width: 100%; padding: 10px 12px; font-family: monospace; font-size: 13px; border-radius: 0;" placeholder="es. Pacing lento nelle prime ore&#10;Non adatto a tutti"><?php echo esc_textarea( $cons ); ?></textarea>
			</div>
		</div>

		<div style="margin-bottom: 10px; border-left: 4px solid #ef4444; padding-left: 15px;">
			<label for="ntn_verdict" style="display: block; font-weight: 800; margin-bottom: 8px; font-family: monospace; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">// Il Verdetto (Testo riassuntivo finale)</label>
			<textarea id="ntn_verdict" name="ntn_verdict" rows="4" style="width: 100%; padding: 10px 12px; font-size: 14px; border-radius: 0;" placeholder="Scrivi un paragrafo riassuntivo del verdetto finale..."><?php echo esc_textarea( $verdict ); ?></textarea>
		</div>

		<script>
			document.addEventListener('DOMContentLoaded', function() {
				var ratingInput = document.getElementById('ntn_rating');
				var labelSpan = document.getElementById('ntn_rating_dynamic_label');
				
				function updateRatingLabel() {
					if (!ratingInput || !labelSpan) return;
					var val = parseFloat(ratingInput.value);
					if (isNaN(val)) {
						labelSpan.style.display = 'none';
						return;
					}
					
					labelSpan.style.display = 'inline-block';
					
					if (val === 1 || val === 2) {
						labelSpan.textContent = 'BOCCIATO';
						labelSpan.style.backgroundColor = '#ef4444'; // Red
					} else if (val === 3) {
						labelSpan.textContent = 'CONSIGLIATO CON RISERVA';
						labelSpan.style.backgroundColor = '#f59e0b'; // Amber
					} else if (val === 4) {
						labelSpan.textContent = 'CONSIGLIATO';
						labelSpan.style.backgroundColor = '#22c55e'; // Green
					} else if (val === 5) {
						labelSpan.textContent = 'MUST PLAY';
						labelSpan.style.backgroundColor = '#a855f7'; // Purple
					}
				}
				
				if (ratingInput) {
					ratingInput.addEventListener('input', updateRatingLabel);
					// Add setTimeout fallback for WordPress metabox dynamic loading
					setTimeout(updateRatingLabel, 100);
				}
			});
		</script>
	</div>
	<?php
}

function ntn_save_review_meta_box_data( $post_id ) {
	// Security checks
	if ( ! isset( $_POST['ntn_review_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['ntn_review_meta_nonce'], 'ntn_save_review_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save fields
	if ( isset( $_POST['ntn_reviewed_item'] ) ) {
		update_post_meta( $post_id, 'reviewed_item', sanitize_text_field( wp_unslash( $_POST['ntn_reviewed_item'] ) ) );
	}
	if ( isset( $_POST['ntn_review_source'] ) ) {
		update_post_meta( $post_id, 'ntn_review_source', sanitize_text_field( wp_unslash( $_POST['ntn_review_source'] ) ) );
	}
	if ( isset( $_POST['ntn_rating'] ) ) {
		update_post_meta( $post_id, 'rating', sanitize_text_field( wp_unslash( $_POST['ntn_rating'] ) ) );
	}
	if ( isset( $_POST['ntn_pros'] ) ) {
		update_post_meta( $post_id, 'pros', sanitize_textarea_field( wp_unslash( $_POST['ntn_pros'] ) ) );
	}
	if ( isset( $_POST['ntn_cons'] ) ) {
		update_post_meta( $post_id, 'cons', sanitize_textarea_field( wp_unslash( $_POST['ntn_cons'] ) ) );
	}
	if ( isset( $_POST['ntn_verdict'] ) ) {
		update_post_meta( $post_id, 'verdict', sanitize_textarea_field( wp_unslash( $_POST['ntn_verdict'] ) ) );
	}
}
add_action( 'save_post', 'ntn_save_review_meta_box_data' );

/**
 * Voto della recensione pulito (v4.25.3): numero tra 1 e 5 salvato come testo, come oggi.
 * Vuoto, zero o testo non numerico diventano "" (nessun voto), cioè come il sito li legge già.
 *
 * @param mixed $value Valore da salvare.
 * @return string
 */
function ntn_core_sanitize_review_rating( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}
	$value = trim( sanitize_text_field( (string) $value ) );
	if ( '' === $value || ! is_numeric( $value ) ) {
		return '';
	}
	$number = (float) $value;
	if ( $number <= 0 ) {
		return '';
	}
	$number = min( 5.0, $number );
	return ( floor( $number ) == $number ) ? (string) (int) $number : (string) round( $number, 1 );
}

/**
 * Pro e contro puliti (v4.25.3): testo su più righe; i vecchi valori salvati come elenco restano elenco.
 *
 * @param mixed $value Valore da salvare.
 * @return string|string[]
 */
function ntn_core_sanitize_review_lines( $value ) {
	if ( is_array( $value ) ) {
		return array_values( array_map( 'sanitize_textarea_field', array_filter( $value, 'is_scalar' ) ) );
	}
	return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
}

/**
 * Testo del verdetto pulito (v4.25.3).
 *
 * @param mixed $value Valore da salvare.
 * @return string
 */
function ntn_core_sanitize_review_textarea( $value ) {
	return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
}

/**
 * Nome del gioco recensito pulito (v4.25.3).
 *
 * @param mixed $value Valore da salvare.
 * @return string
 */
function ntn_core_sanitize_review_text( $value ) {
	return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
}

/**
 * Può modificare i campi della recensione solo chi può modificare l'articolo (v4.25.3).
 *
 * @param bool   $allowed  Valore proposto.
 * @param string $meta_key Nome del campo.
 * @param int    $post_id  Articolo.
 * @return bool
 */
function ntn_core_review_meta_auth( $allowed, $meta_key, $post_id ) {
	return current_user_can( 'edit_post', (int) $post_id );
}

/**
 * Campi della recensione (nomi senza prefisso, tenuti così per non spostare i dati).
 *
 * @return array<string, callable>
 */
function ntn_core_review_meta_fields() {
	return array(
		'rating'        => 'ntn_core_sanitize_review_rating',
		'pros'          => 'ntn_core_sanitize_review_lines',
		'cons'          => 'ntn_core_sanitize_review_lines',
		'verdict'       => 'ntn_core_sanitize_review_textarea',
		'reviewed_item' => 'ntn_core_sanitize_review_text',
	);
}

/**
 * Dichiara a WordPress i campi della recensione (v4.25.3), senza rinominarli né spostare i dati.
 * Restano testo (come oggi), uno per articolo, e non vanno nell'API: nessuna parte del sito li legge da lì.
 */
function ntn_core_register_review_meta() {
	foreach ( ntn_core_review_meta_fields() as $meta_key => $sanitize ) {
		register_post_meta(
			'post',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => $sanitize,
				'auth_callback'     => 'ntn_core_review_meta_auth',
				'show_in_rest'      => false,
			)
		);
	}
}
add_action( 'init', 'ntn_core_register_review_meta' );

