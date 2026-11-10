<?php
/**
 * NTN Journal functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package NTN_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants
define( 'NTN_INC_DIR', get_template_directory() . '/inc/' );

// Load modules
require_once NTN_INC_DIR . 'setup.php';
require_once NTN_INC_DIR . 'enqueue.php';
require_once NTN_INC_DIR . 'widgets.php';
require_once NTN_INC_DIR . 'helpers.php';
require_once NTN_INC_DIR . 'plugin-fallbacks.php'; // Riserve vuote per le funzioni di dati del plugin (v4.11.0)
require_once NTN_INC_DIR . 'menu-filters.php';
require_once NTN_INC_DIR . 'date-social.php';
require_once NTN_INC_DIR . 'related-posts.php';
require_once NTN_INC_DIR . 'performance.php';
require_once NTN_INC_DIR . 'share-buttons.php';
require_once NTN_INC_DIR . 'social-cta.php';
require_once NTN_INC_DIR . 'customizer.php';
require_once NTN_INC_DIR . 'front-page-data.php';
require_once NTN_INC_DIR . 'comments-template.php';
require_once NTN_INC_DIR . 'category-hub-template.php';


require_once NTN_INC_DIR . 'breaking-news.php';
require_once NTN_INC_DIR . 'gallery-lightbox.php';

// I campi meta protetti che le recensioni devono poter leggere (_thumbnail_id, rating, ...) li sblocca,
// dalla 4.26.0, il plugin NTN Journal Core (inc/site-optimizations.php).
