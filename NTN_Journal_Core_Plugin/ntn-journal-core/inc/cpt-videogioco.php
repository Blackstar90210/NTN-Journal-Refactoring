<?php
/**
 * Registrazione Custom Post Type: Videogioco
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ntn_register_cpt_videogioco() {

	$labels = array(
		'name'                  => _x( 'Videogiochi', 'Post Type General Name', 'ntn-journal-core' ),
		'singular_name'         => _x( 'Videogioco', 'Post Type Singular Name', 'ntn-journal-core' ),
		'menu_name'             => __( 'Videogiochi', 'ntn-journal-core' ),
		'name_admin_bar'        => __( 'Videogioco', 'ntn-journal-core' ),
		'archives'              => __( 'Archivio Videogiochi', 'ntn-journal-core' ),
		'attributes'            => __( 'Attributi Videogioco', 'ntn-journal-core' ),
		'parent_item_colon'     => __( 'Videogioco Genitore:', 'ntn-journal-core' ),
		'all_items'             => __( 'Tutti i Videogiochi', 'ntn-journal-core' ),
		'add_new_item'          => __( 'Aggiungi Nuovo Videogioco', 'ntn-journal-core' ),
		'add_new'               => __( 'Aggiungi Nuovo', 'ntn-journal-core' ),
		'new_item'              => __( 'Nuovo Videogioco', 'ntn-journal-core' ),
		'edit_item'             => __( 'Modifica Videogioco', 'ntn-journal-core' ),
		'update_item'           => __( 'Aggiorna Videogioco', 'ntn-journal-core' ),
		'view_item'             => __( 'Visualizza Videogioco', 'ntn-journal-core' ),
		'view_items'            => __( 'Visualizza Videogiochi', 'ntn-journal-core' ),
		'search_items'          => __( 'Cerca Videogioco', 'ntn-journal-core' ),
		'not_found'             => __( 'Non trovato', 'ntn-journal-core' ),
		'not_found_in_trash'    => __( 'Non trovato nel cestino', 'ntn-journal-core' ),
		'featured_image'        => __( 'Immagine di Copertina', 'ntn-journal-core' ),
		'set_featured_image'    => __( 'Imposta immagine di copertina', 'ntn-journal-core' ),
		'remove_featured_image' => __( 'Rimuovi immagine di copertina', 'ntn-journal-core' ),
		'use_featured_image'    => __( 'Usa come immagine di copertina', 'ntn-journal-core' ),
		'insert_into_item'      => __( 'Inserisci nel videogioco', 'ntn-journal-core' ),
		'uploaded_to_this_item' => __( 'Caricato su questo videogioco', 'ntn-journal-core' ),
		'items_list'            => __( 'Lista Videogiochi', 'ntn-journal-core' ),
		'items_list_navigation' => __( 'Navigazione lista videogiochi', 'ntn-journal-core' ),
		'filter_items_list'     => __( 'Filtra lista videogiochi', 'ntn-journal-core' ),
	);

	$args = array(
		'label'                 => __( 'Videogioco', 'ntn-journal-core' ),
		'description'           => __( 'Catalogo dei videogiochi trattati su NTN Journal', 'ntn-journal-core' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'taxonomies'            => array( 'game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'post_tag' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-games',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rewrite'               => array( 'slug' => 'videogioco', 'with_front' => false ),
	);

	register_post_type( 'videogioco', $args );

}
add_action( 'init', 'ntn_register_cpt_videogioco', 0 );

/**
 * Disabilita il redirect canonico per permettere la paginazione 
 * degli articoli correlati all'interno della hub page 'videogioco'.
 * 
 * @param string $redirect_url L'URL di redirect calcolato.
 * @param string $requested_url L'URL inizialmente richiesto.
 * @return string|false
 */
function ntn_disable_videogioco_pagination_redirect( $redirect_url, $requested_url ) {
	if ( is_singular( 'videogioco' ) ) {
		$page  = get_query_var( 'page' );
		$paged = get_query_var( 'paged' );
		if ( $page > 0 || $paged > 0 ) {
			return false;
		}
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'ntn_disable_videogioco_pagination_redirect', 10, 2 );
