<?php
/**
 * Caselle per i dati strutturati (v4.14.3).
 *
 * - Tag: "È un videogioco". Se è spuntata il nome del tag è il nome del gioco anche quando il tag non ha
 *   dati IGDB. Se il tag ha il nome salvato da IGDB, vince quello.
 * - Categoria: "È una piattaforma". Serve per piattaforme che non stanno sotto una Hub Piattaforme (Mac, Linux...).
 *
 * @package NTN_Journal_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Il tag è marcato come videogioco?
 *
 * @param int $term_id Tag.
 * @return bool
 */
function ntn_core_tag_is_game( $term_id ) {
	return '1' === (string) get_term_meta( (int) $term_id, '_ntn_is_game', true );
}

/**
 * La categoria è marcata come piattaforma?
 *
 * @param int $term_id Categoria.
 * @return bool
 */
function ntn_core_category_is_platform( $term_id ) {
	return '1' === (string) get_term_meta( (int) $term_id, '_ntn_is_platform', true );
}

/**
 * Casella nella pagina "Modifica tag" e "Modifica categoria".
 *
 * @param WP_Term $term Tag o categoria.
 */
function ntn_core_schema_flag_field( $term ) {
	if ( ! $term instanceof WP_Term || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$is_tag  = 'post_tag' === $term->taxonomy;
	$key     = $is_tag ? '_ntn_is_game' : '_ntn_is_platform';
	$label   = $is_tag ? 'È un videogioco' : 'È una piattaforma';
	$help    = $is_tag
		? 'Se è spuntata, per Google il nome di questo tag è il nome del gioco anche senza dati IGDB. Se il tag ha il nome salvato da IGDB, vale quello.'
		: 'Spuntala per console, PC, Mac, Linux e simili che non stanno sotto una Hub Piattaforme: il loro nome va ai dati per Google. Marchi, generi e sezioni (Nintendo, Indie, Guide) restano senza.';
	wp_nonce_field( 'ntn_schema_flag_' . $term->term_id, 'ntn_schema_flag_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ntn-schema-flag"><?php echo esc_html( $label ); ?> (NTN Core)</label></th>
		<td>
			<label><input type="checkbox" id="ntn-schema-flag" name="ntn_schema_flag" value="1" <?php checked( '1' === (string) get_term_meta( $term->term_id, $key, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<p class="description"><?php echo esc_html( $help ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'post_tag_edit_form_fields', 'ntn_core_schema_flag_field' );
add_action( 'category_edit_form_fields', 'ntn_core_schema_flag_field' );

/**
 * Salva la casella (permessi e nonce controllati).
 *
 * @param int $term_id Tag o categoria.
 */
function ntn_core_schema_flag_save( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) || empty( $_POST['ntn_schema_flag_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ntn_schema_flag_nonce'] ) ), 'ntn_schema_flag_' . (int) $term_id ) ) {
		return;
	}
	$term = get_term( (int) $term_id );
	if ( ! $term instanceof WP_Term || ! in_array( $term->taxonomy, array( 'post_tag', 'category' ), true ) ) {
		return;
	}
	$key = 'post_tag' === $term->taxonomy ? '_ntn_is_game' : '_ntn_is_platform';
	if ( ! empty( $_POST['ntn_schema_flag'] ) ) {
		update_term_meta( (int) $term_id, $key, '1' );
	} else {
		delete_term_meta( (int) $term_id, $key );
	}
}
add_action( 'edited_post_tag', 'ntn_core_schema_flag_save' );
add_action( 'edited_category', 'ntn_core_schema_flag_save' );
