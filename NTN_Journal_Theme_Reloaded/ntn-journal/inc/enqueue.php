<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Enqueue scripts and styles.
 */
function ntn_journal_scripts() {
	// Versione del tema genitore (non del tema attivo, che può essere il figlio)
	$ver = wp_get_theme( get_template() )->get( 'Version' );

	// Enqueue style.css (contiene ora anche il CSS Tailwind compilato in locale)
	wp_enqueue_style( 'ntn-journal-style', get_template_directory_uri() . '/style.css', array(), $ver );

	// Enqueue Local Fonts (Inter, JetBrains Mono).
	// Rimossa per inline nell'<head> (Performance)

	// Enqueue Lazy Video Script for IGDB Trailer
	if ( is_tag() || is_singular( 'videogioco' ) ) {
		wp_enqueue_script( 'ntn-lazy-video', get_template_directory_uri() . '/assets/js/lazy-video.js', array(), $ver, true );
	}

	// Auto-load compiled Tailwind CSS if present (Production Mode)
	// rimosso file_exists() e filemtime() per evitare stat() bloccanti su I/O ogni richiesta.
	wp_enqueue_style( 'ntn-journal-tailwind', get_template_directory_uri() . '/style-tailwind-output.css', array( 'ntn-journal-style' ), $ver );
	// Enqueue main.js with defer strategy
	wp_enqueue_script(
		'ntn-journal-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		$ver,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_front_page() ) {
		wp_enqueue_script(
			'ntn-journal-front-page',
			get_template_directory_uri() . '/assets/js/front-page.js',
			array( 'ntn-journal-main' ),
			$ver,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	// Ricerca istantanea (v4.10.0, prima nel tema figlio): l'input di ricerca è nell'header di ogni pagina
	wp_enqueue_script( 'ntn-live-search', get_template_directory_uri() . '/assets/js/live-search.js', array(), $ver, true );

	// Variabili per le chiamate REST del plugin (URL e nonce), lette da live-search.js e catalog-filters.js
	wp_localize_script(
		'ntn-live-search',
		'ntn_catalog_vars',
		array(
			'rest_url'   => esc_url_raw( rest_url( 'ntn-journal/v1/games' ) ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'ajax_url'   => admin_url( 'admin-ajax.php' ), // Mantenuto per compatibilità legacy temporanea se serve
			'nonce'      => wp_create_nonce( 'ntn_catalog_nonce' ),
		)
	);

	// Filtri del catalogo (v4.10.0, prima nel tema figlio): catalogo e archivi delle tassonomie dei giochi
	if ( is_post_type_archive( 'videogioco' ) || is_tax( 'game_engine' ) || is_tax( 'game_franchise' ) || is_tax( 'game_developer' ) || is_tax( 'game_publisher' ) ) {
		wp_enqueue_script( 'ntn-catalog-filters', get_template_directory_uri() . '/assets/js/catalog-filters.js', array( 'ntn-live-search' ), $ver, true );
	}
}
add_action( 'wp_enqueue_scripts', 'ntn_journal_scripts' );
