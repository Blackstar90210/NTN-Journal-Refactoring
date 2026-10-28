<?php
/**
 * Enqueue scripts and styles for the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ntn_journal_child_enqueue_styles() {
	// Enqueue child style
	wp_enqueue_style( 'ntn-journal-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'ntn-journal-style' ),
		wp_get_theme()->get( 'Version' )
	);

	// Se siamo nell'archivio del catalogo, carichiamo gli script del filtro (dipendente da ntn-live-search per ntn_catalog_vars)
	if ( is_post_type_archive( 'videogioco' ) || is_tax( 'game_engine' ) || is_tax( 'game_franchise' ) || is_tax( 'game_developer' ) || is_tax( 'game_publisher' ) ) {
		wp_enqueue_script( 'ntn-catalog-filters', get_stylesheet_directory_uri() . '/assets/js/catalog-filters.js', array( 'ntn-live-search' ), wp_get_theme()->get( 'Version' ), true );
	}

	// Carichiamo live-search ovunque ci sia l'input di ricerca (sarà incluso in tutte le pagine)
	wp_enqueue_script( 'ntn-live-search', get_stylesheet_directory_uri() . '/assets/js/live-search.js', array(), wp_get_theme()->get( 'Version' ), true );

	// Localizza variabili per le chiamate REST API (sicurezza: nonce e URL)
	wp_localize_script( 'ntn-live-search', 'ntn_catalog_vars', array(
		'rest_url'   => esc_url_raw( rest_url( 'ntn-journal/v1/games' ) ),
		'rest_nonce' => wp_create_nonce( 'wp_rest' ),
		'ajax_url'   => admin_url( 'admin-ajax.php' ), // Mantenuto per compatibilità legacy temporanea se serve
		'nonce'      => wp_create_nonce( 'ntn_catalog_nonce' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'ntn_journal_child_enqueue_styles' );


