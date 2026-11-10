<?php
/**
 * Avviso in bacheca quando plugin e temi NTN non hanno la stessa versione (v4.18.0).
 *
 * Plugin, tema parent e tema child condividono lo stesso numero di versione e vanno aggiornati
 * insieme, nell'ordine plugin, parent, child. Se dopo un aggiornamento uno dei tre resta indietro,
 * la bacheca lo dice con i numeri a confronto, così non serve scoprirlo dai sintomi sul sito.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Confronta le versioni e dice quali componenti non coincidono con quella del plugin.
 *
 * @param string      $core   Versione del plugin.
 * @param string      $parent Versione del tema parent (stringa vuota se non installato).
 * @param string|null $child  Versione del tema child se è quello attivo, altrimenti null.
 * @return array[]    Una riga per componente fuori allineamento: label, found, expected.
 */
if ( ! function_exists( 'ntn_core_version_mismatches' ) ) {
	function ntn_core_version_mismatches( $core, $parent, $child = null ) {
		$core = trim( (string) $core );
		$rows = array();
		if ( '' === $core ) {
			return $rows;
		}
		if ( '' !== trim( (string) $parent ) && trim( (string) $parent ) !== $core ) {
			$rows[] = array(
				'label'    => 'Tema parent (ntn-journal)',
				'found'    => trim( (string) $parent ),
				'expected' => $core,
			);
		}
		if ( null !== $child && '' !== trim( (string) $child ) && trim( (string) $child ) !== $core ) {
			$rows[] = array(
				'label'    => 'Tema child (ntn-journal-reloaded)',
				'found'    => trim( (string) $child ),
				'expected' => $core,
			);
		}
		return $rows;
	}
}

/**
 * Legge le versioni installate e mostra l'avviso se non coincidono.
 */
function ntn_core_version_check_notice() {
	if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'update_themes' ) ) {
		return;
	}
	// Con un altro tema NTN non è in uso e non c'è nulla da confrontare.
	if ( 'ntn-journal' !== get_template() ) {
		return;
	}
	// Sotto la 4.10.1 c'è già l'avviso dedicato in ntn-journal-core.php.
	if ( function_exists( 'ntn_core_can_load_game_data' ) && ! ntn_core_can_load_game_data() ) {
		return;
	}

	$parent = (string) wp_get_theme( 'ntn-journal' )->get( 'Version' );
	$child  = null;
	$active = wp_get_theme();
	if ( 'ntn-journal-reloaded' === $active->get_stylesheet() ) {
		$child = (string) $active->get( 'Version' );
	}

	$rows = ntn_core_version_mismatches( NTN_JOURNAL_CORE_VERSION, $parent, $child );
	if ( empty( $rows ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'NTN Journal: le versioni non coincidono.', 'ntn-journal-core' ) . '</strong> ';
	echo esc_html( sprintf( 'Il plugin è alla %s.', NTN_JOURNAL_CORE_VERSION ) ) . '</p><ul style="list-style:disc;margin-left:20px">';
	foreach ( $rows as $row ) {
		echo '<li>' . esc_html( sprintf( '%s è alla %s, dovrebbe essere alla %s.', $row['label'], $row['found'], $row['expected'] ) ) . '</li>';
	}
	echo '</ul><p>' . esc_html__( 'Installa gli ZIP della stessa versione, in questo ordine: plugin, tema parent, tema child. Se li hai già installati, svuota la cache del sito e ricarica la pagina.', 'ntn-journal-core' ) . '</p></div>';
}
add_action( 'admin_notices', 'ntn_core_version_check_notice' );
