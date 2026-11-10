<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! function_exists( 'ntn_journal_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 */
	function ntn_journal_setup() {
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Supporto per Logo Personalizzato.
		 */
		add_theme_support(
			'custom-logo',
			array(
				'height'               => 100,
				'width'                => 300,
				'flex-height'          => true,
				'flex-width'           => true,
				'header-text'          => array( 'site-title', 'site-description' ),
				'unlink-homepage-logo' => true,
			)
		);

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// Registrazione taglia immagine personalizzata per layout a griglia (Performance)
		add_image_size( 'ntn-grid-thumb', 600, 338, false );

		// Editor Styles
		add_theme_support( 'editor-styles' );
		add_editor_style( array( 'assets/fonts/local-fonts.css', 'editor-style.css' ) );

		// Register Navigation Menus
		register_nav_menus(
			array(
				'menu-1'           => esc_html__( 'Primary Menu', 'ntn-journal' ),
				'menu-contenuti'   => esc_html__( 'Menu Gestione Contenuti', 'ntn-journal' ),
				'menu-piattaforme' => esc_html__( 'Menu Gestione Piattaforme', 'ntn-journal' ),
				'menu-footer'      => esc_html__( 'Menu Footer', 'ntn-journal' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);
	}
endif;
add_action( 'after_setup_theme', 'ntn_journal_setup' );

/**
 * Disabilita XML-RPC per sicurezza
 */
add_filter( 'xmlrpc_enabled', '__return_false' );



/**
 * Performance: Preload Critical Assets (LCP & FCP)
 */
function ntn_journal_performance_preloads() {
	$font_dir = get_template_directory_uri() . '/assets/fonts/';

	// 1. Preload Critical Font (Inter Variable) per migliorare FCP e ridurre FOIT
	echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url( $font_dir . 'inter-variable.woff2' ) . '" crossorigin="anonymous">' . "\n";

	// 1.5 Inline local-fonts.css per rimuovere il render-blocking
	$local_fonts_path = get_template_directory() . '/assets/fonts/local-fonts.css';
	if ( file_exists( $local_fonts_path ) ) {
		echo '<style id="ntn-local-fonts">' . "\n";
		// Sostituiamo i percorsi relativi con URI assoluti altrimenti url('./font.woff2') non funziona dall'HTML root
		$font_css = file_get_contents( $local_fonts_path );
		echo str_replace( "url('./", "url('" . esc_url( $font_dir ), $font_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS del tema stesso, con solo l'indirizzo dei font già pulito con esc_url.
		echo "\n" . '</style>' . "\n";
	}
}
add_action( 'wp_head', 'ntn_journal_performance_preloads', 1 );
