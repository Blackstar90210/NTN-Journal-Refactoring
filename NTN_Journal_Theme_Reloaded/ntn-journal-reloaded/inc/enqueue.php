<?php
/**
 * Enqueue scripts and styles for the child theme.
 *
 * Dalla v4.10.0 ricerca istantanea, filtri del catalogo e card del gioco sono nel tema genitore:
 * qui si carica solo lo style.css del figlio, dopo il CSS compilato del genitore, così le
 * personalizzazioni vincono a parità di specificità.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ntn_journal_child_enqueue_styles() {
	wp_enqueue_style( 'ntn-journal-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'ntn-journal-tailwind' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ntn_journal_child_enqueue_styles' );
