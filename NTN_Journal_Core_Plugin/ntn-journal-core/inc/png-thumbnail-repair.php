<?php
/**
 * Riparazione delle miniature dei PNG trasparenti (v4.21.0).
 *
 * Fino alla 4.20.0 le miniature dei PNG trasparenti venivano salvate in JPG, con lo sfondo pieno.
 * La 4.20.1 corregge i nuovi caricamenti; questo strumento sistema le immagini già caricate.
 * Tre passi separati, nella scheda Immagini del pannello (dalla 4.24.0; prima in Extra):
 *   1. Controlla: solo lettura, trova i PNG davvero trasparenti con miniature JPG.
 *   2. Ripara: rifà le miniature (in PNG grazie alla 4.20.1) e registra i vecchi file JPG.
 *   3. Pulizia in due tempi: 3a sposta in quarantena i vecchi JPG che nessuno usa (con Ripristina),
 *      3b dopo almeno 7 giorni e con un backup fatto, elimina la quarantena.
 * L'immagine originale non viene mai toccata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NTN_PNG_REPAIR_SCAN_BATCH', 20 );
define( 'NTN_PNG_REPAIR_FIX_BATCH', 5 );
define( 'NTN_PNG_REPAIR_MOVE_BATCH', 15 );
define( 'NTN_PNG_REPAIR_QUARANTINE_DAYS', 7 );
define( 'NTN_PNG_REPAIR_QUARANTINE_DIR', 'ntn-quarantena' );

/**
 * Stato salvato dei tre passi (opzioni non caricate a ogni pagina).
 */
function ntn_core_png_repair_get( $key ) {
	$value = get_option( 'ntn_png_repair_' . $key, array() );
	return is_array( $value ) ? $value : array();
}

function ntn_core_png_repair_set( $key, array $value ) {
	update_option( 'ntn_png_repair_' . $key, $value, false );
}

/**
 * Percorso relativo alla cartella uploads ('2026/10/logo-300x200.jpg'), o '' se il file è fuori.
 */
function ntn_core_png_repair_relative( $path ) {
	$uploads = wp_get_upload_dir();
	$base    = wp_normalize_path( trailingslashit( $uploads['basedir'] ) );
	$path    = wp_normalize_path( (string) $path );
	return 0 === strpos( $path, $base ) ? substr( $path, strlen( $base ) ) : '';
}

/**
 * Percorso completo da un percorso relativo, solo se resta dentro uploads (niente "..").
 */
function ntn_core_png_repair_absolute( $relative ) {
	$relative = ltrim( wp_normalize_path( (string) $relative ), '/' );
	if ( '' === $relative || false !== strpos( $relative, '..' ) ) {
		return '';
	}
	$uploads = wp_get_upload_dir();
	return wp_normalize_path( trailingslashit( $uploads['basedir'] ) ) . $relative;
}

/**
 * Miniature JPG registrate nei metadati di un allegato: nome della misura => percorso relativo.
 */
function ntn_core_png_repair_jpg_sizes( $attachment_id ) {
	$meta = wp_get_attachment_metadata( $attachment_id );
	$file = get_attached_file( $attachment_id );
	$out  = array();
	if ( ! is_array( $meta ) || empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) || ! $file ) {
		return $out;
	}
	$dir = trailingslashit( dirname( $file ) );
	foreach ( $meta['sizes'] as $size => $data ) {
		if ( ! is_array( $data ) || empty( $data['file'] ) ) {
			continue;
		}
		$mime = isset( $data['mime-type'] ) ? $data['mime-type'] : '';
		if ( 'image/jpeg' === $mime || preg_match( '/\.jpe?g$/i', $data['file'] ) ) {
			$rel = ntn_core_png_repair_relative( $dir . $data['file'] );
			if ( '' !== $rel ) {
				$out[ $size ] = $rel;
			}
		}
	}
	return $out;
}

/**
 * Il plugin sta applicando la correzione 4.20.1? Senza, rifare le miniature le rifarebbe in JPG.
 */
function ntn_core_png_repair_can_fix() {
	return function_exists( 'ntn_core_owns_site_optimizations' ) && ntn_core_owns_site_optimizations()
		&& false !== has_filter( 'image_editor_output_format', 'ntn_core_convert_png_to_jpeg_thumbnails' )
		&& false !== has_filter( 'intermediate_image_sizes_advanced', 'ntn_core_remember_image_source' );
}

/**
 * C'è abbastanza memoria per lavorare l'immagine? Stima prudente: 3 copie a 4 byte per pixel.
 */
function ntn_core_png_repair_memory_ok( $file ) {
	$size = @getimagesize( $file );
	if ( ! $size ) {
		return false;
	}
	$limit = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
	if ( $limit <= 0 ) {
		return true; // nessun limite
	}
	$needed = (int) $size[0] * (int) $size[1] * 4 * 3;
	return ( $limit - memory_get_usage( true ) ) > $needed;
}

/**
 * Dove compare il nome di un file nel database. Lista vuota = nessuno lo usa.
 * Prudente: basta il nome del file (es. logo-300x200.jpg) dentro un testo qualsiasi per considerarlo usato.
 *
 * @param string $basename Nome del file senza cartella.
 * @return string[] Luoghi in cui compare.
 */
function ntn_core_png_repair_reference_places( $basename ) {
	global $wpdb;
	$basename = (string) $basename;
	if ( '' === $basename ) {
		return array( 'nome vuoto' );
	}
	$like   = '%' . $wpdb->esc_like( $basename ) . '%';
	$places = array();
	$checks = array(
		'articoli e pagine' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s OR post_excerpt LIKE %s",
		'metadati'          => "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
		'impostazioni'      => "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_value LIKE %s AND option_name NOT LIKE %s",
		'termini'           => "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_value LIKE %s",
		'utenti'            => "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_value LIKE %s",
		'commenti'          => "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_content LIKE %s",
	);
	foreach ( $checks as $label => $sql ) {
		$args = array( $like );
		if ( 'articoli e pagine' === $label ) {
			$args[] = $like;
		} elseif ( 'impostazioni' === $label ) {
			$args[] = $wpdb->esc_like( 'ntn_png_repair_' ) . '%'; // il registro di questo strumento non conta
		}
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		if ( null === $count ) {
			$places[] = $label . ' (controllo non riuscito)'; // nel dubbio il file resta
		} elseif ( (int) $count > 0 ) {
			$places[] = $label;
		}
	}
	return $places;
}

/**
 * Cartella della quarantena, protetta da index.php e .htaccess.
 */
function ntn_core_png_repair_quarantine_dir() {
	$uploads = wp_get_upload_dir();
	$dir     = wp_normalize_path( trailingslashit( $uploads['basedir'] ) . NTN_PNG_REPAIR_QUARANTINE_DIR );
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		@file_put_contents( $dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" );
	}
	return $dir;
}

/**
 * Toglie le cartelle anno/mese rimaste vuote nella quarantena (index.php e .htaccess restano).
 */
function ntn_core_png_repair_prune_dirs() {
	$uploads = wp_get_upload_dir();
	$root    = wp_normalize_path( trailingslashit( $uploads['basedir'] ) . NTN_PNG_REPAIR_QUARANTINE_DIR );
	if ( ! is_dir( $root ) ) {
		return;
	}
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $item ) {
		if ( $item->isDir() ) {
			@rmdir( $item->getPathname() ); // riesce solo se la cartella è vuota
		}
	}
}

/**
 * Giorni passati da quando la quarantena è stata creata (null se non c'è).
 */
function ntn_core_png_repair_quarantine_age_days() {
	$q = ntn_core_png_repair_get( 'quarantine' );
	if ( empty( $q['files'] ) || empty( $q['created'] ) ) {
		return null;
	}
	return ( time() - (int) $q['created'] ) / DAY_IN_SECONDS;
}

/**
 * Controlli comuni: nonce e permessi.
 */
function ntn_core_png_repair_guard() {
	check_ajax_referer( 'ntn_png_repair', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}
	if ( function_exists( 'wp_raise_memory_limit' ) ) {
		wp_raise_memory_limit( 'image' );
	}
}

/**
 * Passo 1: Controlla (solo lettura), a gruppi.
 */
function ntn_core_ajax_png_repair_scan() {
	ntn_core_png_repair_guard();
	global $wpdb;
	$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;
	$state  = 0 === $offset ? array( 'found' => array(), 'unreadable' => array() ) : ntn_core_png_repair_get( 'scan' );
	$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type = 'image/png'" );
	$ids    = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type = 'image/png' ORDER BY ID ASC LIMIT %d OFFSET %d", NTN_PNG_REPAIR_SCAN_BATCH, $offset ) );

	foreach ( $ids as $id ) {
		$id   = (int) $id;
		$jpgs = ntn_core_png_repair_jpg_sizes( $id );
		if ( empty( $jpgs ) ) {
			continue; // miniature già PNG o nessuna miniatura
		}
		$file = get_attached_file( $id );
		$name = $file ? basename( $file ) : '#' . $id;
		if ( ! $file || ! is_readable( $file ) || ! @getimagesize( $file ) ) {
			$state['unreadable'][ $id ] = $name;
			continue;
		}
		if ( ntn_core_png_has_transparency( $file ) ) {
			$state['found'][ $id ] = array( 'name' => $name, 'jpg' => count( $jpgs ) );
		}
	}

	$next          = $offset + NTN_PNG_REPAIR_SCAN_BATCH;
	$state['done'] = $next >= $total;
	$state['time'] = time();
	ntn_core_png_repair_set( 'scan', $state );

	wp_send_json_success( array(
		'total'      => $total,
		'next'       => $next,
		'done'       => $state['done'],
		'found'      => count( $state['found'] ),
		'unreadable' => count( $state['unreadable'] ),
		'list'       => $state['done'] ? array_values( wp_list_pluck( $state['found'], 'name' ) ) : array(),
		'skipped'    => $state['done'] ? array_values( $state['unreadable'] ) : array(),
	) );
}
add_action( 'wp_ajax_ntn_png_repair_scan', 'ntn_core_ajax_png_repair_scan' );

/**
 * Rifà le miniature di un allegato e registra i vecchi JPG. Restituisce lo stato per il registro.
 */
function ntn_core_png_repair_fix_one( $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	$name = $file ? basename( $file ) : '#' . $attachment_id;
	if ( ! $file || ! is_readable( $file ) ) {
		return array( 'name' => $name, 'stato' => 'saltato', 'motivo' => 'file originale non leggibile', 'vecchi_file' => array() );
	}
	if ( ! ntn_core_png_repair_memory_ok( $file ) ) {
		return array( 'name' => $name, 'stato' => 'saltato', 'motivo' => 'memoria del server insufficiente', 'vecchi_file' => array() );
	}
	$old = array_values( ntn_core_png_repair_jpg_sizes( $attachment_id ) );
	if ( empty( $old ) ) {
		return array( 'name' => $name, 'stato' => 'già a posto', 'motivo' => '', 'vecchi_file' => array() );
	}

	// I vecchi file si registrano prima di rifare, così il passo 3 li ritrova anche se qualcosa va storto
	$log                   = ntn_core_png_repair_get( 'log' );
	$log[ $attachment_id ] = array( 'name' => $name, 'stato' => 'in corso', 'motivo' => '', 'vecchi_file' => $old );
	ntn_core_png_repair_set( 'log', $log );

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$meta = wp_generate_attachment_metadata( $attachment_id, $file );
	if ( empty( $meta ) || ! is_array( $meta ) ) {
		return array( 'name' => $name, 'stato' => 'saltato', 'motivo' => 'WordPress non è riuscito a rifare le miniature', 'vecchi_file' => $old );
	}
	wp_update_attachment_metadata( $attachment_id, $meta );

	$still = count( ntn_core_png_repair_jpg_sizes( $attachment_id ) );
	return array(
		'name'        => $name,
		'stato'       => $still ? 'da ricontrollare' : 'riparato',
		'motivo'      => $still ? $still . ' miniature ancora JPG' : '',
		'vecchi_file' => $old,
	);
}

/**
 * Passo 2: Ripara, a gruppi di 5. Riprende da dove si era fermato.
 */
function ntn_core_ajax_png_repair_fix() {
	ntn_core_png_repair_guard();
	if ( ! ntn_core_png_repair_can_fix() ) {
		wp_send_json_error( 'La correzione delle miniature PNG (4.20.1) non è attiva: aggiorna plugin e temi alla stessa versione e riprova.' );
	}
	$scan = ntn_core_png_repair_get( 'scan' );
	if ( empty( $scan['done'] ) ) {
		wp_send_json_error( 'Prima esegui il passo 1 (Controlla) fino alla fine.' );
	}
	$log   = ntn_core_png_repair_get( 'log' );
	$todo  = array();
	foreach ( array_keys( $scan['found'] ) as $id ) {
		if ( ! isset( $log[ $id ] ) || 'in corso' === $log[ $id ]['stato'] ) {
			$todo[] = (int) $id;
		}
	}
	foreach ( array_slice( $todo, 0, NTN_PNG_REPAIR_FIX_BATCH ) as $id ) {
		$result     = ntn_core_png_repair_fix_one( $id );
		$log        = ntn_core_png_repair_get( 'log' );
		$log[ $id ] = $result;
		ntn_core_png_repair_set( 'log', $log );
	}
	$log     = ntn_core_png_repair_get( 'log' );
	$left    = max( 0, count( $todo ) - NTN_PNG_REPAIR_FIX_BATCH );
	$counts  = array_count_values( wp_list_pluck( array_intersect_key( $log, $scan['found'] ), 'stato' ) );
	$skipped = array();
	foreach ( array_intersect_key( $log, $scan['found'] ) as $row ) {
		if ( 'riparato' !== $row['stato'] && 'già a posto' !== $row['stato'] ) {
			$skipped[] = $row['name'] . ': ' . $row['motivo'];
		}
	}
	wp_send_json_success( array(
		'total'   => count( $scan['found'] ),
		'left'    => $left,
		'done'    => 0 === $left,
		'fixed'   => (int) ( $counts['riparato'] ?? 0 ),
		'skipped' => $skipped,
	) );
}
add_action( 'wp_ajax_ntn_png_repair_fix', 'ntn_core_ajax_png_repair_fix' );

/**
 * Elenco dei vecchi file registrati e non ancora spostati.
 *
 * @return string[] Percorsi relativi.
 */
function ntn_core_png_repair_pending_old_files() {
	$log   = ntn_core_png_repair_get( 'log' );
	$q     = ntn_core_png_repair_get( 'quarantine' );
	$moved = array();
	foreach ( (array) ( $q['files'] ?? array() ) as $row ) {
		$moved[ $row['from'] ] = true;
	}
	$out = array();
	foreach ( $log as $row ) {
		if ( 'riparato' !== ( $row['stato'] ?? '' ) ) {
			continue; // si spostano solo i vecchi file delle immagini riparate
		}
		foreach ( (array) ( $row['vecchi_file'] ?? array() ) as $rel ) {
			$path = ntn_core_png_repair_absolute( $rel );
			if ( ! isset( $moved[ $rel ] ) && '' !== $path && file_exists( $path ) ) {
				$out[] = $rel;
			}
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * Un vecchio file si può spostare? Restituisce '' se sì, altrimenti il motivo.
 */
function ntn_core_png_repair_move_blocker( $relative ) {
	$path = ntn_core_png_repair_absolute( $relative );
	if ( '' === $path || ! preg_match( '/\.jpe?g$/i', $path ) ) {
		return 'percorso non valido';
	}
	if ( ! file_exists( $path ) ) {
		return 'file già assente';
	}
	$places = ntn_core_png_repair_reference_places( basename( $path ) );
	return empty( $places ) ? '' : 'ancora usato in: ' . implode( ', ', $places );
}

/**
 * Passo 3a: sposta in quarantena i vecchi JPG che nessuno usa, a gruppi.
 */
function ntn_core_ajax_png_repair_quarantine() {
	ntn_core_png_repair_guard();
	$pending = ntn_core_png_repair_pending_old_files();
	$q       = ntn_core_png_repair_get( 'quarantine' );
	$q      += array( 'created' => 0, 'files' => array(), 'kept' => array() );
	$kept    = (array) $q['kept'];
	$pending = array_values( array_diff( $pending, array_keys( $kept ) ) );
	$dir     = ntn_core_png_repair_quarantine_dir();

	foreach ( array_slice( $pending, 0, NTN_PNG_REPAIR_MOVE_BATCH ) as $rel ) {
		$why = ntn_core_png_repair_move_blocker( $rel );
		if ( '' !== $why ) {
			$kept[ $rel ] = $why;
			continue;
		}
		$from = ntn_core_png_repair_absolute( $rel );
		$to   = $dir . '/' . $rel;
		wp_mkdir_p( dirname( $to ) );
		$size = (int) @filesize( $from );
		if ( @rename( $from, $to ) ) {
			$q['files'][] = array( 'from' => $rel, 'to' => NTN_PNG_REPAIR_QUARANTINE_DIR . '/' . $rel, 'size' => $size );
			$q['created'] = time(); // i 7 giorni contano dall'ultimo file spostato
		} else {
			$kept[ $rel ] = 'spostamento non riuscito';
		}
	}
	$q['kept'] = $kept;
	ntn_core_png_repair_set( 'quarantine', $q );

	$left = max( 0, count( $pending ) - NTN_PNG_REPAIR_MOVE_BATCH );
	wp_send_json_success( array(
		'left'  => $left,
		'done'  => 0 === $left,
		'moved' => count( $q['files'] ),
		'bytes' => array_sum( wp_list_pluck( $q['files'], 'size' ) ),
		'kept'  => 0 === $left ? array_map( static function ( $rel, $why ) { return $rel . ' (' . $why . ')'; }, array_keys( $kept ), $kept ) : array(),
	) );
}
add_action( 'wp_ajax_ntn_png_repair_quarantine', 'ntn_core_ajax_png_repair_quarantine' );

/**
 * Riporta i file della quarantena al loro posto. Restituisce [riportati, non riportati].
 */
function ntn_core_png_repair_restore_all() {
	$q        = ntn_core_png_repair_get( 'quarantine' );
	$restored = 0;
	$left     = array();
	foreach ( (array) ( $q['files'] ?? array() ) as $row ) {
		$from = ntn_core_png_repair_absolute( $row['to'] );
		$to   = ntn_core_png_repair_absolute( $row['from'] );
		if ( '' !== $from && '' !== $to && file_exists( $from ) && ! file_exists( $to ) && @rename( $from, $to ) ) {
			$restored++;
		} else {
			$left[] = $row;
		}
	}
	ntn_core_png_repair_set( 'quarantine', array( 'created' => $left ? (int) $q['created'] : 0, 'files' => $left, 'kept' => array() ) );
	ntn_core_png_repair_prune_dirs();
	return array( $restored, count( $left ) );
}

function ntn_core_ajax_png_repair_restore() {
	ntn_core_png_repair_guard();
	list( $restored, $left ) = ntn_core_png_repair_restore_all();
	wp_send_json_success( array( 'restored' => $restored, 'left' => $left ) );
}
add_action( 'wp_ajax_ntn_png_repair_restore', 'ntn_core_ajax_png_repair_restore' );

/**
 * Passo 3b: elimina definitivamente la quarantena. Solo con conferma del backup e dopo almeno 7 giorni.
 */
function ntn_core_png_repair_purge_blocker( $backup_confirmed ) {
	$age = ntn_core_png_repair_quarantine_age_days();
	if ( null === $age ) {
		return 'La quarantena è vuota.';
	}
	if ( $age < NTN_PNG_REPAIR_QUARANTINE_DAYS ) {
		return sprintf( 'La quarantena si può eliminare solo dopo %d giorni (ne sono passati %d).', NTN_PNG_REPAIR_QUARANTINE_DAYS, (int) floor( $age ) );
	}
	if ( ! $backup_confirmed ) {
		return 'Conferma di aver fatto un backup della cartella uploads.';
	}
	return '';
}

function ntn_core_ajax_png_repair_purge() {
	ntn_core_png_repair_guard();
	$why = ntn_core_png_repair_purge_blocker( ! empty( $_POST['backup'] ) && '1' === (string) $_POST['backup'] );
	if ( '' !== $why ) {
		wp_send_json_error( $why );
	}
	$q       = ntn_core_png_repair_get( 'quarantine' );
	$deleted = 0;
	$bytes   = 0;
	foreach ( (array) $q['files'] as $row ) {
		$path = ntn_core_png_repair_absolute( $row['to'] );
		if ( '' !== $path && 0 === strpos( ntn_core_png_repair_relative( $path ), NTN_PNG_REPAIR_QUARANTINE_DIR . '/' ) && file_exists( $path ) && @unlink( $path ) ) {
			$deleted++;
			$bytes += (int) $row['size'];
		}
	}
	ntn_core_png_repair_set( 'quarantine', array( 'created' => 0, 'files' => array(), 'kept' => array() ) );
	ntn_core_png_repair_prune_dirs();
	wp_send_json_success( array( 'deleted' => $deleted, 'bytes' => $bytes ) );
}
add_action( 'wp_ajax_ntn_png_repair_purge', 'ntn_core_ajax_png_repair_purge' );

/**
 * Riquadro della scheda Immagini.
 */
function ntn_core_png_repair_render_box() {
	$scan  = ntn_core_png_repair_get( 'scan' );
	$q     = ntn_core_png_repair_get( 'quarantine' );
	$age   = ntn_core_png_repair_quarantine_age_days();
	$files = count( (array) ( $q['files'] ?? array() ) );
	$bytes = array_sum( wp_list_pluck( (array) ( $q['files'] ?? array() ), 'size' ) );
	$nonce = wp_create_nonce( 'ntn_png_repair' );
	?>
	<hr style="margin: 30px 0;">
	<h2 id="ntn-png-repair">Miniature dei PNG trasparenti</h2>
	<div class="notice notice-info inline" style="margin:12px 0 16px;padding:12px 16px">
		<p style="margin-top:0">Fino alla versione 4.20.0 le miniature dei PNG trasparenti (loghi, badge, icone) venivano salvate in JPG, con lo sfondo pieno. Dalla 4.20.1 i nuovi caricamenti sono corretti; qui si sistemano le immagini già caricate. L'immagine originale non viene mai toccata.</p>
		<p style="margin-bottom:0"><strong>1. Controlla</strong> non cambia nulla. <strong>2. Ripara</strong> rifà le miniature in PNG. <strong>3. Pulizia</strong> sposta i vecchi JPG non più usati in una cartella di quarantena (si possono riportare indietro); dopo almeno <?php echo (int) NTN_PNG_REPAIR_QUARANTINE_DAYS; ?> giorni, e con un backup fatto, la quarantena si può eliminare.</p>
	</div>
	<div id="ntn-png-repair-box" data-nonce="<?php echo esc_attr( $nonce ); ?>">
		<p>
			<button type="button" class="button" data-step="scan">1. Controlla</button>
			<button type="button" class="button" data-step="fix" <?php disabled( empty( $scan['done'] ) || empty( $scan['found'] ) ); ?>>2. Ripara</button>
			<button type="button" class="button" data-step="quarantine">3a. Sposta in quarantena</button>
			<button type="button" class="button" data-step="restore" <?php disabled( 0 === $files ); ?>>Ripristina dalla quarantena</button>
			<button type="button" class="button" data-step="stop" disabled>Interrompi</button>
		</p>
		<p><progress id="ntn-png-repair-bar" max="100" value="0" style="width:100%;max-width:480px"></progress></p>
		<div id="ntn-png-repair-out" aria-live="polite">
			<?php if ( ! empty( $scan['done'] ) ) : ?>
				<p>Ultimo controllo: <?php echo (int) count( $scan['found'] ); ?> PNG trasparenti con miniature JPG<?php echo ! empty( $scan['unreadable'] ) ? ', ' . (int) count( $scan['unreadable'] ) . ' immagini non leggibili' : ''; ?>.</p>
			<?php endif; ?>
			<?php if ( $files ) : ?>
				<p>In quarantena: <?php echo (int) $files; ?> file (<?php echo esc_html( size_format( $bytes ) ); ?>), da <?php echo (int) floor( (float) $age ); ?> giorni.</p>
			<?php endif; ?>
		</div>
		<p style="margin-top:16px">
			<label><input type="checkbox" id="ntn-png-repair-backup" value="1"> Ho fatto un backup della cartella uploads</label><br>
			<button type="button" class="button button-link-delete" data-step="purge" <?php disabled( null === $age || $age < NTN_PNG_REPAIR_QUARANTINE_DAYS ); ?>>3b. Elimina definitivamente la quarantena</button>
			<?php if ( null !== $age && $age < NTN_PNG_REPAIR_QUARANTINE_DAYS ) : ?>
				<span class="description">Disponibile tra <?php echo (int) ceil( NTN_PNG_REPAIR_QUARANTINE_DAYS - $age ); ?> giorni.</span>
			<?php endif; ?>
		</p>
	</div>
	<script>
	( function () {
		var box = document.getElementById( 'ntn-png-repair-box' );
		if ( ! box ) { return; }
		var out = document.getElementById( 'ntn-png-repair-out' ), bar = document.getElementById( 'ntn-png-repair-bar' );
		var stop = false, busy = false;
		function esc( s ) { var d = document.createElement( 'div' ); d.textContent = String( s ); return d.innerHTML; }
		function say( html ) { out.innerHTML = html; }
		function list( title, items ) { return items && items.length ? '<p><strong>' + esc( title ) + '</strong></p><ul style="list-style:disc;margin-left:20px">' + items.map( function ( i ) { return '<li>' + esc( i ) + '</li>'; } ).join( '' ) + '</ul>' : ''; }
		function kb( n ) { return n > 1048576 ? ( n / 1048576 ).toFixed( 1 ) + ' MB' : Math.round( n / 1024 ) + ' KB'; }
		function call( action, data ) {
			var fd = new FormData();
			fd.append( 'action', 'ntn_png_repair_' + action );
			fd.append( '_ajax_nonce', box.dataset.nonce );
			Object.keys( data || {} ).forEach( function ( k ) { fd.append( k, data[ k ] ); } );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd } ).then( function ( r ) { return r.json(); } );
		}
		var purgeBtn = box.querySelector( '[data-step="purge"]' ), purgeOff = purgeBtn.disabled;
		function lock( on ) {
			busy = on;
			box.querySelectorAll( 'button' ).forEach( function ( b ) {
				if ( 'stop' === b.dataset.step ) { b.disabled = ! on; } else { b.disabled = on || ( b === purgeBtn && purgeOff ); }
			} );
		}
		function fail( r ) { say( '<p style="color:#b32d2e">' + esc( ( r && r.data ) || 'Errore di comunicazione.' ) + '</p>' ); lock( false ); }
		function scan( offset ) {
			if ( stop ) { say( '<p>Controllo interrotto.</p>' ); return lock( false ); }
			call( 'scan', { offset: offset } ).then( function ( r ) {
				if ( ! r.success ) { return fail( r ); }
				var d = r.data; bar.value = d.total ? Math.min( 100, d.next / d.total * 100 ) : 100;
				if ( ! d.done ) { say( '<p>Controllo… ' + Math.min( d.next, d.total ) + ' / ' + d.total + ' PNG</p>' ); return scan( d.next ); }
				say( '<p>' + d.found + ' PNG trasparenti con miniature JPG su ' + d.total + ' PNG.</p>' + list( 'Da riparare:', d.list ) + list( 'Non leggibili (saltati):', d.skipped ) );
				lock( false );
			} ).catch( fail );
		}
		function fix() {
			if ( stop ) { say( '<p>Riparazione interrotta: riprende da qui con "2. Ripara".</p>' ); return lock( false ); }
			call( 'fix' ).then( function ( r ) {
				if ( ! r.success ) { return fail( r ); }
				var d = r.data; bar.value = d.total ? ( d.total - d.left ) / d.total * 100 : 100;
				if ( ! d.done ) { say( '<p>Riparazione… ' + ( d.total - d.left ) + ' / ' + d.total + '</p>' ); return fix(); }
				say( '<p>Riparate: ' + d.fixed + ' immagini.</p>' + list( 'Saltate o da ricontrollare:', d.skipped ) );
				lock( false );
			} ).catch( fail );
		}
		function quarantine() {
			if ( stop ) { say( '<p>Spostamento interrotto: riprende da qui con "3a".</p>' ); return lock( false ); }
			call( 'quarantine' ).then( function ( r ) {
				if ( ! r.success ) { return fail( r ); }
				var d = r.data;
				if ( ! d.done ) { say( '<p>Spostamento… ancora ' + d.left + ' file da controllare</p>' ); return quarantine(); }
				bar.value = 100;
				say( '<p>In quarantena: ' + d.moved + ' file (' + kb( d.bytes ) + ').</p>' + list( 'Lasciati al loro posto:', d.kept ) );
				lock( false );
			} ).catch( fail );
		}
		box.addEventListener( 'click', function ( e ) {
			var step = e.target && e.target.dataset ? e.target.dataset.step : '';
			if ( ! step ) { return; }
			if ( 'stop' === step ) { stop = true; return; }
			if ( busy ) { return; }
			stop = false; bar.value = 0;
			if ( 'scan' === step ) { lock( true ); return scan( 0 ); }
			if ( 'fix' === step ) { lock( true ); return fix(); }
			if ( 'quarantine' === step ) { lock( true ); return quarantine(); }
			if ( 'restore' === step ) {
				if ( ! confirm( 'Riportare tutti i file della quarantena al loro posto?' ) ) { return; }
				lock( true );
				return call( 'restore' ).then( function ( r ) { if ( ! r.success ) { return fail( r ); } say( '<p>Riportati: ' + r.data.restored + ' file' + ( r.data.left ? ', non riportati: ' + r.data.left : '' ) + '.</p>' ); lock( false ); } ).catch( fail );
			}
			if ( 'purge' === step ) {
				var ok = document.getElementById( 'ntn-png-repair-backup' ).checked;
				if ( ! ok ) { return say( '<p style="color:#b32d2e">Conferma prima di aver fatto un backup.</p>' ); }
				if ( ! confirm( 'Eliminare definitivamente i file della quarantena? Non si potrà tornare indietro.' ) ) { return; }
				lock( true );
				return call( 'purge', { backup: '1' } ).then( function ( r ) { if ( ! r.success ) { return fail( r ); } say( '<p>Eliminati: ' + r.data.deleted + ' file (' + kb( r.data.bytes ) + ').</p>' ); lock( false ); } ).catch( fail );
			}
		} );
	} )();
	</script>
	<?php
}
