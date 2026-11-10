<?php
/**
 * NTN Journal Core - Rapporto di sola lettura sulle immagini (v4.23.0)
 *
 * Nella scheda Immagini (dalla 4.24.0; prima in Extra), due controlli che NON cambiano, NON spostano e NON eliminano nulla:
 * 1. Analizza: peso delle immagini, immagini troppo pesanti, senza testo alternativo, file mancanti.
 * 2. Cerca immagini non usate: immagini che non risultano da nessuna parte (prudente: nel dubbio "è usata").
 * Il risultato resta salvato nell'opzione ntn_image_report e si rilegge senza rifare il controllo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NTN_IMAGE_REPORT_SCAN_BATCH', 25 );
define( 'NTN_IMAGE_REPORT_UNUSED_BATCH', 10 );
define( 'NTN_IMAGE_REPORT_HEAVY_BYTES', 1572864 ); // 1,5 MB per il file originale
define( 'NTN_IMAGE_REPORT_LIST_MAX', 200 );        // quante voci per elenco restano salvate

/**
 * Rapporto salvato.
 *
 * @return array
 */
function ntn_core_image_report_get() {
	$report = get_option( 'ntn_image_report', array() );
	return is_array( $report ) ? $report : array();
}

function ntn_core_image_report_set( array $report ) {
	update_option( 'ntn_image_report', $report, false );
}

/**
 * Controllo comune: nonce e permessi.
 */
function ntn_core_image_report_guard() {
	check_ajax_referer( 'ntn_image_report', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permesso negato.', 403 );
	}
}

/**
 * Prossimo gruppo di immagini dopo un certo ID (sola lettura).
 *
 * @param int $after_id Ultimo ID già controllato.
 * @param int $limit    Quante.
 * @return int[]
 */
function ntn_core_image_report_next_ids( $after_id, $limit ) {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE %s AND ID > %d ORDER BY ID ASC LIMIT %d",
			'image/%',
			(int) $after_id,
			(int) $limit
		)
	);
	return array_map( 'intval', (array) $ids );
}

function ntn_core_image_report_total() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE %s", 'image/%' ) );
}

/**
 * Peso su disco di un'immagine: originale e tutte le miniature elencate nei metadati.
 *
 * @param int $attachment_id Allegato.
 * @return array{exists:bool,original:int,total:int}
 */
function ntn_core_image_report_sizes( $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
		return array(
			'exists'   => false,
			'original' => 0,
			'total'    => 0,
		);
	}
	$original = (int) @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- il file può mancare: in quel caso la dimensione vale 0 e non serve un avviso PHP.
	$total    = $original;
	$meta     = wp_get_attachment_metadata( $attachment_id );
	$dir      = trailingslashit( dirname( $file ) );
	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		$seen = array();
		foreach ( $meta['sizes'] as $size ) {
			$name = isset( $size['file'] ) ? (string) $size['file'] : '';
			if ( '' === $name || isset( $seen[ $name ] ) || basename( $file ) === $name ) {
				continue;
			}
			$seen[ $name ] = true;
			if ( file_exists( $dir . $name ) ) {
				$total += (int) @filesize( $dir . $name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- il file può mancare: in quel caso la dimensione vale 0 e non serve un avviso PHP.
			}
		}
	}
	return array(
		'exists'   => true,
		'original' => $original,
		'total'    => $total,
	);
}

/**
 * Aggiunge una voce a un elenco con tetto, tenendo i più pesanti.
 */
function ntn_core_image_report_push( array &$list, $id, $bytes ) {
	$list[] = array( (int) $id, (int) $bytes );
	if ( count( $list ) > NTN_IMAGE_REPORT_LIST_MAX * 2 ) {
		usort(
			$list,
			static function ( $a, $b ) {
				return $b[1] <=> $a[1];
			}
		);
		$list = array_slice( $list, 0, NTN_IMAGE_REPORT_LIST_MAX );
	}
}

/**
 * Le voci di un elenco, più pesanti per prime, pronte per la pagina.
 *
 * @param array $list  Voci [id, byte].
 * @param int   $limit Quante.
 * @return array[]
 */
function ntn_core_image_report_items( array $list, $limit = 30 ) {
	usort(
		$list,
		static function ( $a, $b ) {
			return $b[1] <=> $a[1];
		}
	);
	$out = array();
	foreach ( array_slice( $list, 0, $limit ) as $row ) {
		$out[] = array(
			'name'  => (string) wp_basename( (string) get_attached_file( $row[0] ) ),
			'bytes' => (int) $row[1],
			'edit'  => (string) get_edit_post_link( $row[0], 'raw' ),
		);
	}
	return $out;
}

/**
 * Passo 1: Analizza.
 */
function ntn_core_ajax_image_report_scan() {
	ntn_core_image_report_guard();
	$last  = isset( $_POST['last_id'] ) ? absint( wp_unslash( $_POST['last_id'] ) ) : 0;
	$new   = 0 === $last;
	$scan  = $new ? array() : (array) ( ntn_core_image_report_get()['scan'] ?? array() );
	$scan += array(
		'total'           => 0,
		'checked'         => 0,
		'bytes_all'       => 0,
		'bytes_originals' => 0,
		'heavy'           => array(),
		'heavy_count'     => 0,
		'no_alt'          => array(),
		'no_alt_count'    => 0,
		'missing'         => array(),
		'missing_count'   => 0,
		'done'            => false,
		'at'              => 0,
	);
	if ( $new ) {
		$scan['total'] = ntn_core_image_report_total();
	}
	$ids = ntn_core_image_report_next_ids( $last, NTN_IMAGE_REPORT_SCAN_BATCH );
	foreach ( $ids as $id ) {
		$sizes = ntn_core_image_report_sizes( $id );
		++$scan['checked'];
		if ( ! $sizes['exists'] ) {
			++$scan['missing_count'];
			ntn_core_image_report_push( $scan['missing'], $id, 0 );
		} else {
			$scan['bytes_all']       += $sizes['total'];
			$scan['bytes_originals'] += $sizes['original'];
			if ( $sizes['original'] > NTN_IMAGE_REPORT_HEAVY_BYTES ) {
				++$scan['heavy_count'];
				ntn_core_image_report_push( $scan['heavy'], $id, $sizes['original'] );
			}
		}
		if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
			++$scan['no_alt_count'];
			ntn_core_image_report_push( $scan['no_alt'], $id, $sizes['original'] );
		}
		$last = $id;
	}
	$done = count( $ids ) < NTN_IMAGE_REPORT_SCAN_BATCH;
	if ( $done ) {
		$scan['done'] = true;
		$scan['at']   = time();
	}
	$report         = ntn_core_image_report_get();
	$report['scan'] = $scan;
	ntn_core_image_report_set( $report );

	wp_send_json_success(
		array_merge(
			ntn_core_image_report_scan_view( $scan ),
			array(
				'last_id' => $last,
				'done'    => $done,
			)
		)
	);
}
add_action( 'wp_ajax_ntn_image_report_scan', 'ntn_core_ajax_image_report_scan' );

/**
 * I numeri e gli elenchi del passo 1 pronti per la pagina.
 */
function ntn_core_image_report_scan_view( array $scan ) {
	return array(
		'total'           => (int) ( $scan['total'] ?? 0 ),
		'checked'         => (int) ( $scan['checked'] ?? 0 ),
		'bytes_all'       => (int) ( $scan['bytes_all'] ?? 0 ),
		'bytes_originals' => (int) ( $scan['bytes_originals'] ?? 0 ),
		'heavy_count'     => (int) ( $scan['heavy_count'] ?? 0 ),
		'no_alt_count'    => (int) ( $scan['no_alt_count'] ?? 0 ),
		'missing_count'   => (int) ( $scan['missing_count'] ?? 0 ),
		'heavy'           => ntn_core_image_report_items( (array) ( $scan['heavy'] ?? array() ) ),
		'no_alt'          => ntn_core_image_report_items( (array) ( $scan['no_alt'] ?? array() ) ),
		'missing'         => ntn_core_image_report_items( (array) ( $scan['missing'] ?? array() ) ),
	);
}

/**
 * Dove risulta usata un'immagine. Lista vuota = non risulta usata da nessuna parte.
 * Prudente: basta il nome del file senza estensione dentro un testo qualsiasi per considerarla usata,
 * e se una domanda al database non riesce l'immagine conta come usata.
 *
 * @param int $attachment_id Allegato.
 * @return string[] Luoghi in cui risulta usata.
 */
function ntn_core_image_report_usage( $attachment_id ) {
	global $wpdb;
	$attachment_id = (int) $attachment_id;
	$places        = array();

	$parent = (int) get_post_field( 'post_parent', $attachment_id );
	if ( $parent > 0 && get_post( $parent ) ) {
		$places[] = 'allegata a un contenuto';
	}
	$thumb = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d", $attachment_id ) );
	if ( null === $thumb || (int) $thumb > 0 ) {
		$places[] = 'immagine in evidenza';
	}

	$file = get_attached_file( $attachment_id );
	$stem = is_string( $file ) ? (string) preg_replace( '/-scaled$/', '', pathinfo( $file, PATHINFO_FILENAME ) ) : '';
	if ( '' === $stem ) {
		$places[] = 'nome del file non leggibile';
		return $places;
	}
	$like    = '%' . $wpdb->esc_like( $stem ) . '%';
	$id_like = '%' . $wpdb->esc_like( 'wp-image-' . $attachment_id ) . '%';
	$checks  = array(
		'articoli e pagine' => array(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment','revision') AND (post_content LIKE %s OR post_excerpt LIKE %s OR post_content LIKE %s)",
			array( $like, $like, $id_like ),
		),
		'metadati'          => array(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_value LIKE %s AND meta_key NOT IN ('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_backup_sizes','_thumbnail_id')",
			array( $like ),
		),
		'impostazioni'      => array(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_value LIKE %s AND option_name NOT LIKE %s",
			array( $like, $wpdb->esc_like( 'ntn_image_report' ) . '%' ),
		),
		'termini'           => array( "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_value LIKE %s", array( $like ) ),
		'utenti'            => array( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_value LIKE %s", array( $like ) ),
	);
	foreach ( $checks as $label => $check ) {
		$count = $wpdb->get_var( $wpdb->prepare( $check[0], $check[1] ) );
		if ( null === $count || (int) $count > 0 ) {
			$places[] = $label;
		}
	}
	return $places;
}

/**
 * Passo 2: Cerca immagini non usate.
 */
function ntn_core_ajax_image_report_unused() {
	ntn_core_image_report_guard();
	$last    = isset( $_POST['last_id'] ) ? absint( wp_unslash( $_POST['last_id'] ) ) : 0;
	$new     = 0 === $last;
	$unused  = $new ? array() : (array) ( ntn_core_image_report_get()['unused'] ?? array() );
	$unused += array(
		'total'   => 0,
		'checked' => 0,
		'count'   => 0,
		'bytes'   => 0,
		'list'    => array(),
		'done'    => false,
		'at'      => 0,
	);
	if ( $new ) {
		$unused['total'] = ntn_core_image_report_total();
	}
	$ids = ntn_core_image_report_next_ids( $last, NTN_IMAGE_REPORT_UNUSED_BATCH );
	foreach ( $ids as $id ) {
		++$unused['checked'];
		if ( empty( ntn_core_image_report_usage( $id ) ) ) {
			$sizes = ntn_core_image_report_sizes( $id );
			++$unused['count'];
			$unused['bytes'] += $sizes['total'];
			ntn_core_image_report_push( $unused['list'], $id, $sizes['total'] );
		}
		$last = $id;
	}
	$done = count( $ids ) < NTN_IMAGE_REPORT_UNUSED_BATCH;
	if ( $done ) {
		$unused['done'] = true;
		$unused['at']   = time();
	}
	$report           = ntn_core_image_report_get();
	$report['unused'] = $unused;
	ntn_core_image_report_set( $report );

	wp_send_json_success(
		array_merge(
			ntn_core_image_report_unused_view( $unused ),
			array(
				'last_id' => $last,
				'done'    => $done,
			)
		)
	);
}
add_action( 'wp_ajax_ntn_image_report_unused', 'ntn_core_ajax_image_report_unused' );

function ntn_core_image_report_unused_view( array $unused ) {
	return array(
		'total'   => (int) ( $unused['total'] ?? 0 ),
		'checked' => (int) ( $unused['checked'] ?? 0 ),
		'count'   => (int) ( $unused['count'] ?? 0 ),
		'bytes'   => (int) ( $unused['bytes'] ?? 0 ),
		'list'    => ntn_core_image_report_items( (array) ( $unused['list'] ?? array() ) ),
	);
}

/**
 * Riepilogo per la Dashboard (v4.25.0). Legge SOLO i risultati già salvati: nessun controllo pesante,
 * nessuna ricerca nei file o nel database.
 *
 * @return array{count:string,lines:string[]} Numero grande e righe di testo.
 */
function ntn_core_image_report_dashboard_summary() {
	$report = ntn_core_image_report_get();
	$scan   = ! empty( $report['scan']['done'] ) ? $report['scan'] : array();
	$unused = ! empty( $report['unused']['done'] ) ? $report['unused'] : array();
	$lines  = array();

	if ( $scan ) {
		$lines[] = sprintf(
			'Su disco %1$s · %2$s originali sopra %3$s · %4$s senza testo alternativo',
			size_format( (int) $scan['bytes_all'], 0 ),
			number_format_i18n( (int) $scan['heavy_count'] ),
			size_format( NTN_IMAGE_REPORT_HEAVY_BYTES, 1 ),
			number_format_i18n( (int) $scan['no_alt_count'] )
		);
		if ( ! empty( $scan['missing_count'] ) ) {
			$lines[] = sprintf( 'File mancanti sul disco: %s', number_format_i18n( (int) $scan['missing_count'] ) );
		}
	} else {
		$lines[] = 'Nessuna analisi ancora fatta';
	}
	if ( $unused ) {
		$lines[] = sprintf( 'Non risultano usate: %1$s (%2$s)', number_format_i18n( (int) $unused['count'] ), size_format( (int) $unused['bytes'], 0 ) );
	}

	if ( function_exists( 'ntn_core_png_repair_get' ) ) {
		$png_scan   = ntn_core_png_repair_get( 'scan' );
		$quarantine = ntn_core_png_repair_get( 'quarantine' );
		$q_files    = count( (array) ( $quarantine['files'] ?? array() ) );
		if ( ! empty( $png_scan['done'] ) ) {
			$lines[] = sprintf( 'Miniature dei PNG trasparenti da riparare: %s', number_format_i18n( count( (array) ( $png_scan['found'] ?? array() ) ) ) );
		}
		if ( $q_files > 0 ) {
			$lines[] = sprintf( 'In quarantena: %s file', number_format_i18n( $q_files ) );
		}
	}

	return array(
		'count' => $scan ? number_format_i18n( (int) $scan['checked'] ) : '—',
		'lines' => $lines,
	);
}

/**
 * Riquadro della scheda Immagini.
 */
function ntn_core_image_report_render_box() {
	$report = ntn_core_image_report_get();
	$saved  = array(
		'scan'   => ! empty( $report['scan']['done'] ) ? array_merge( ntn_core_image_report_scan_view( $report['scan'] ), array( 'at' => (int) $report['scan']['at'] ) ) : null,
		'unused' => ! empty( $report['unused']['done'] ) ? array_merge( ntn_core_image_report_unused_view( $report['unused'] ), array( 'at' => (int) $report['unused']['at'] ) ) : null,
	);
	$nonce  = wp_create_nonce( 'ntn_image_report' );
	?>
	<hr style="margin: 30px 0;">
	<h2 id="ntn-image-report">Rapporto sulle immagini</h2>
	<div class="notice notice-info inline" style="margin:12px 0 16px;padding:12px 16px">
		<p style="margin:0">Sola lettura: questi controlli <strong>non cambiano, non spostano e non eliminano nulla</strong>. Servono a capire quali immagini pesano di più, quali non hanno il testo alternativo e quali non risultano usate. «Non usata» è un'indicazione prudente: prima di eliminare un'immagine controllala sempre a mano e fai un backup. Il controllo delle immagini non usate è più lento e può richiedere alcuni minuti.</p>
	</div>
	<div id="ntn-image-report-box" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-heavy="<?php echo esc_attr( size_format( NTN_IMAGE_REPORT_HEAVY_BYTES, 1 ) ); ?>">
		<p>
			<button type="button" class="button" data-step="scan">1. Analizza</button>
			<button type="button" class="button" data-step="unused">2. Cerca immagini non usate</button>
			<button type="button" class="button" data-step="stop" disabled>Interrompi</button>
		</p>
		<p><progress id="ntn-image-report-bar" max="100" value="0" style="width:100%;max-width:480px"></progress></p>
		<div id="ntn-image-report-out" aria-live="polite"></div>
	</div>
	<script>
	( function () {
		var box = document.getElementById( 'ntn-image-report-box' );
		if ( ! box ) { return; }
		var out = document.getElementById( 'ntn-image-report-out' ), bar = document.getElementById( 'ntn-image-report-bar' );
		var saved = <?php echo wp_json_encode( $saved ); ?>;
		var stop = false, busy = false, heavy = box.dataset.heavy;
		function esc( s ) { var d = document.createElement( 'div' ); d.textContent = String( s ); return d.innerHTML; }
		function kb( n ) { return n > 1048576 ? ( n / 1048576 ).toFixed( 1 ) + ' MB' : Math.round( n / 1024 ) + ' KB'; }
		function when( t ) { return t ? new Date( t * 1000 ).toLocaleString( 'it-IT' ) : ''; }
		function safeUrl( u ) { return /^https?:\/\//.test( u ) ? u : ''; }
		function list( title, items, showBytes ) {
			if ( ! items || ! items.length ) { return ''; }
			return '<p><strong>' + esc( title ) + '</strong></p><ul style="list-style:disc;margin-left:20px">' + items.map( function ( i ) {
				var name = esc( i.name || '(senza nome)' ), url = safeUrl( i.edit || '' );
				return '<li>' + ( url ? '<a href="' + esc( url ) + '" target="_blank" rel="noopener noreferrer">' + name + '</a>' : name ) + ( showBytes ? ' (' + kb( i.bytes ) + ')' : '' ) + '</li>';
			} ).join( '' ) + '</ul>';
		}
		function scanHtml( d, final ) {
			if ( ! final ) { return '<p>Analisi… ' + d.checked + ' / ' + d.total + ' immagini</p>'; }
			return '<h3>Analisi' + ( d.at ? ' del ' + esc( when( d.at ) ) : '' ) + '</h3>' +
				'<p>' + d.checked + ' immagini. Peso totale su disco con le miniature: <strong>' + kb( d.bytes_all ) + '</strong> (solo gli originali: ' + kb( d.bytes_originals ) + ').</p>' +
				'<ul style="list-style:disc;margin-left:20px"><li>Originali sopra ' + esc( heavy ) + ': <strong>' + d.heavy_count + '</strong></li><li>Senza testo alternativo: <strong>' + d.no_alt_count + '</strong> (alcune immagini decorative possono non averlo)</li><li>File mancanti sul disco: <strong>' + d.missing_count + '</strong></li></ul>' +
				list( 'Le più pesanti (prime 30):', d.heavy, true ) + list( 'Senza testo alternativo (le più pesanti, prime 30):', d.no_alt, true ) + list( 'File mancanti (prime 30):', d.missing, false );
		}
		function unusedHtml( d, final ) {
			if ( ! final ) { return '<p>Ricerca… ' + d.checked + ' / ' + d.total + ' immagini</p>'; }
			return '<h3>Immagini non usate' + ( d.at ? ' (ricerca del ' + esc( when( d.at ) ) + ')' : '' ) + '</h3>' +
				'<p>Su ' + d.checked + ' immagini, <strong>' + d.count + '</strong> non risultano usate da nessuna parte, per <strong>' + kb( d.bytes ) + '</strong> su disco. Ricontrolla a mano prima di eliminare.</p>' +
				list( 'Le più pesanti (prime 30):', d.list, true );
		}
		function show() {
			out.innerHTML = ( saved.scan ? scanHtml( saved.scan, true ) : '' ) + ( saved.unused ? unusedHtml( saved.unused, true ) : '' );
		}
		function call( action, data ) {
			var fd = new FormData();
			fd.append( 'action', 'ntn_image_report_' + action );
			fd.append( '_ajax_nonce', box.dataset.nonce );
			Object.keys( data || {} ).forEach( function ( k ) { fd.append( k, data[ k ] ); } );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd } ).then( function ( r ) { return r.json(); } );
		}
		function lock( on ) {
			busy = on;
			box.querySelectorAll( 'button' ).forEach( function ( b ) { b.disabled = 'stop' === b.dataset.step ? ! on : on; } );
		}
		function fail( r ) { out.innerHTML = '<p style="color:#b32d2e">' + esc( ( r && r.data ) || 'Errore di comunicazione.' ) + '</p>'; lock( false ); }
		function run( kind, last ) {
			if ( stop ) { out.innerHTML = '<p>Interrotto. Il rapporto precedente resta salvato.</p>'; return lock( false ); }
			call( kind, { last_id: last } ).then( function ( r ) {
				if ( ! r.success ) { return fail( r ); }
				var d = r.data;
				bar.value = d.total ? Math.min( 100, d.checked / d.total * 100 ) : 100;
				if ( ! d.done ) { out.innerHTML = ( 'scan' === kind ? scanHtml( d, false ) : unusedHtml( d, false ) ); return run( kind, d.last_id ); }
				d.at = Math.floor( Date.now() / 1000 );
				saved[ 'scan' === kind ? 'scan' : 'unused' ] = d;
				show();
				lock( false );
			} ).catch( fail );
		}
		box.addEventListener( 'click', function ( e ) {
			var step = e.target && e.target.dataset ? e.target.dataset.step : '';
			if ( ! step ) { return; }
			if ( 'stop' === step ) { stop = true; return; }
			if ( busy ) { return; }
			stop = false; bar.value = 0; lock( true );
			run( step, 0 );
		} );
		show();
	} )();
	</script>
	<?php
}
