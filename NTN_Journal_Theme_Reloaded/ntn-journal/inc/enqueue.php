<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Enqueue scripts and styles.
 */
function ntn_journal_scripts() {
	// Enqueue style.css (contiene ora anche il CSS Tailwind compilato in locale)
	wp_enqueue_style( 'ntn-journal-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get( 'Version' ) );

	// Enqueue Local Fonts (Inter, JetBrains Mono).
	// Rimossa per inline nell'<head> (Performance)

	// Enqueue Lazy Video Script for IGDB Trailer
	if ( is_tag() || is_singular( 'videogioco' ) ) {
		wp_enqueue_script( 'ntn-lazy-video', get_template_directory_uri() . '/assets/js/lazy-video.js', array(), wp_get_theme()->get( 'Version' ), true );
	}

	// Auto-load compiled Tailwind CSS if present (Production Mode)
	// rimosso file_exists() e filemtime() per evitare stat() bloccanti su I/O ogni richiesta.
	wp_enqueue_style( 'ntn-journal-tailwind', get_template_directory_uri() . '/style-tailwind-output.css', array( 'ntn-journal-style' ), wp_get_theme()->get( 'Version' ) );
	// Enqueue main.js with defer strategy
	wp_enqueue_script( 'ntn-journal-main', get_template_directory_uri() . '/assets/js/main.js', array(), wp_get_theme()->get('Version'), array( 'in_footer' => true, 'strategy' => 'defer' ) );

	if ( is_front_page() ) {
		wp_enqueue_script( 'ntn-journal-front-page', get_template_directory_uri() . '/assets/js/front-page.js', array('ntn-journal-main'), wp_get_theme()->get('Version'), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'ntn_journal_scripts' );
