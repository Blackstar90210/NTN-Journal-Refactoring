<?php
/**
 * Casella per i dati strutturati (v4.14.3).
 *
 * Categoria: "È una piattaforma". Serve per piattaforme che non stanno sotto una Hub Piattaforme (Mac, Linux...).
 *
 * @package NTN_Journal_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
 * Casella nella pagina "Modifica categoria".
 *
 * @param WP_Term $term Categoria.
 */
function ntn_core_schema_flag_field( $term ) {
	if ( ! $term instanceof WP_Term || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	wp_nonce_field( 'ntn_schema_flag_' . $term->term_id, 'ntn_schema_flag_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ntn-schema-flag">È una piattaforma (NTN Core)</label></th>
		<td>
			<label><input type="checkbox" id="ntn-schema-flag" name="ntn_schema_flag" value="1" <?php checked( ntn_core_category_is_platform( $term->term_id ) ); ?>> È una piattaforma</label>
			<p class="description">Spuntala per console, PC, Mac, Linux e simili che non stanno sotto una Hub Piattaforme: il loro nome va ai dati per Google. Marchi, generi e sezioni (Nintendo, Indie, Guide) restano senza.</p>
		</td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', 'ntn_core_schema_flag_field' );

/**
 * Salva la casella (permessi e nonce controllati).
 *
 * @param int $term_id Categoria.
 */
function ntn_core_schema_flag_save( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) || empty( $_POST['ntn_schema_flag_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ntn_schema_flag_nonce'] ) ), 'ntn_schema_flag_' . (int) $term_id ) ) {
		return;
	}
	if ( ! empty( $_POST['ntn_schema_flag'] ) ) {
		update_term_meta( (int) $term_id, '_ntn_is_platform', '1' );
	} else {
		delete_term_meta( (int) $term_id, '_ntn_is_platform' );
	}
}
add_action( 'edited_category', 'ntn_core_schema_flag_save' );
