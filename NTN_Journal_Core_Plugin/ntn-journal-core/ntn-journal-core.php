<?php
/**
 * Plugin Name: NTN Journal Core
 * Plugin URI: https://example.com/ntn-journal-core
 * Description: Gestisce i Custom Post Type (Comunicato Stampa, Videogioco), Tassonomie, Meta Box IGDB e logiche di backend separate dal tema.
 * Version: 4.9.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: ntn-journal-core
 * Author: Luca Maiolini & Divisione_WP
 * Author URI: https://ntn-journal.local
 * License: Proprietary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Definisco la costante di versione globale (deve coincidere con quella del commento header e dei temi!)
define( 'NTN_JOURNAL_CORE_VERSION', '4.9.1' );
define( 'NTN_JOURNAL_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NTN_JOURNAL_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include all core files
 */
require_once NTN_JOURNAL_CORE_DIR . 'inc/cpt-comunicati-stampa.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/cpt-videogioco.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/taxonomies.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/api-status.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/ajax-igdb.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/igdb-seo-routing.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/catalog-api.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/rest-catalog.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/migrazione-igdb.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/review-meta.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/rating-labels.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/ai-summaries.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/redirect-manager.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/backup-restore.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/seo-extras.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/schema-markup.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/hub-seo.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/category-hub.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/category-hub-stats.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/seo-thin-pages.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/studio-hub.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/team-page.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/sitemap.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/api-helpers.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/admin/admin-page.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/migration-2.4.0.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/precomputed-meta.php';

/**
 * Registrazione image sizes del core
 */
function ntn_journal_core_image_sizes() {
	add_image_size( 'ntn-mini-thumb', 80, 80, true );
}
add_action( 'after_setup_theme', 'ntn_journal_core_image_sizes' );
