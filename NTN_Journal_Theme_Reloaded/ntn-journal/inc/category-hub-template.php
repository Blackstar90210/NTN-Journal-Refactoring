<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Hub di categoria (v3.14.0): instradamento del modello e script dei disegni a punti.
 *
 * I dati arrivano dal plugin (ntn_core_category_hub_context / ntn_core_get_category_hub_data).
 * Se il plugin è spento o la categoria non ha la Hub accesa, resta l'archivio normale.
 *
 * @package NTN_Journal
 */

/**
 * La categoria corrente è una Hub di sezione?
 *
 * @return bool
 */
function ntn_journal_is_category_hub() {
	return is_category() && function_exists( 'ntn_core_category_hub_context' ) && null !== ntn_core_category_hub_context();
}

/**
 * Usa category-hub.php per le categorie con la Hub accesa (e per i marchi di una Hub Console).
 *
 * @param string $template Modello scelto da WordPress.
 * @return string
 */
function ntn_journal_category_hub_template( $template ) {
	if ( ntn_journal_is_category_hub() ) {
		// v4.2.0: il tipo Statistiche userà il suo modello appena esiste; fino ad allora, quello comune
		$hub_context  = ntn_core_category_hub_context();
		$hub_template = locate_template( 'statistiche' === $hub_context['type'] ? array( 'category-hub-statistiche.php', 'category-hub.php' ) : array( 'category-hub.php' ) );
		if ( $hub_template ) {
			return $hub_template;
		}
	}
	return $template;
}
add_filter( 'category_template', 'ntn_journal_category_hub_template' );

/**
 * Script dei disegni a punti (Italia e controller) e del filtro A–Z: solo sulle Hub di categoria.
 */
function ntn_journal_category_hub_assets() {
	if ( ntn_journal_is_category_hub() ) {
		wp_enqueue_script( 'ntn-dot-art', get_template_directory_uri() . '/assets/js/dot-art.js', array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'ntn_journal_category_hub_assets' );
