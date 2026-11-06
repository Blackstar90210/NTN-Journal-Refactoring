<?php
/**
 * Plugin Name: NTN Journal Core
 * Plugin URI: https://example.com/ntn-journal-core
 * Description: Gestisce i Custom Post Type (Comunicato Stampa, Videogioco), Tassonomie, Meta Box IGDB e logiche di backend separate dal tema.
 * Version: 4.15.2
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
define( 'NTN_JOURNAL_CORE_VERSION', '4.15.2' );
define( 'NTN_JOURNAL_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NTN_JOURNAL_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dice se il plugin può definire le funzioni dei dati dei giochi (inc/game-data.php, dalla v4.11.0).
 *
 * WordPress carica il plugin prima del tema. Il tema NTN Journal fino alla 4.10.0 definisce le stesse
 * funzioni senza controllo function_exists: definirle anche qui bloccherebbe il sito con "Cannot redeclare".
 * In quel caso il plugin le lascia al tema e mostra un avviso in bacheca. Con un altro tema le definisce sempre.
 *
 * @return bool
 */
function ntn_core_can_load_game_data() {
	if ( 'ntn-journal' !== get_template() ) {
		return true;
	}
	return version_compare( (string) wp_get_theme( 'ntn-journal' )->get( 'Version' ), '4.10.1', '>=' );
}

require_once NTN_JOURNAL_CORE_DIR . 'inc/cache-keys.php';

if ( ntn_core_can_load_game_data() ) {
	require_once NTN_JOURNAL_CORE_DIR . 'inc/game-data.php';
} else {
	add_action( 'admin_notices', function () {
		if ( current_user_can( 'update_themes' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'NTN Journal Core: aggiorna il tema NTN Journal alla stessa versione del plugin. Finché il tema è sotto la 4.10.1, i dati dei giochi restano gestiti dal tema.', 'ntn-journal-core' ) . '</p></div>';
		}
	} );
}

/**
 * Include all core files
 */
require_once NTN_JOURNAL_CORE_DIR . 'inc/cpt-comunicati-stampa.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/cpt-videogioco.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/taxonomies.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/api-status.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/ajax-igdb.php';
require_once NTN_JOURNAL_CORE_DIR . 'inc/igdb-tag-scanner.php';
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
require_once NTN_JOURNAL_CORE_DIR . 'inc/schema-flags.php';
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
