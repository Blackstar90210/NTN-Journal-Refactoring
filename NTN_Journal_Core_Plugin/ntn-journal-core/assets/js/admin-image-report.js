/**
 * Riquadro "Rapporto sulle immagini" della scheda Immagini: analisi e ricerca delle immagini non usate (sola lettura).
 *
 * Dalla 4.31.0 il codice sta in questo file (prima era dentro image-report.php, tra i tag di script).
 * Il PHP passa con wp_localize_script, nell'oggetto ntnImageReport, il rapporto salvato (saved: ultima analisi e
 * ultima ricerca). Il nonce e la soglia "pesante" arrivano dagli attributi data-nonce e data-heavy del riquadro, come prima.
 * Il resto del codice è identico a prima.
 */
( function () {
	var box = document.getElementById( 'ntn-image-report-box' );
	if ( ! box ) { return; }
	var out = document.getElementById( 'ntn-image-report-out' ), bar = document.getElementById( 'ntn-image-report-bar' );
	var saved = ntnImageReport.saved;
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
	// v4.34.3: immagini in evidenza più strette di 1200 px negli articoli pubblicati (Google Discover).
	function smallHtml( d ) {
		var min = d.discover_width || 1200;
		var html = '<h3>Immagini in evidenza sotto ' + esc( min ) + ' px (Google Discover)</h3>';
		if ( null === d.small_featured_count || undefined === d.small_featured_count ) {
			return html + '<p>Questa analisi è precedente al controllo: premi «1. Analizza» per rifarla.</p>';
		}
		html += '<p>Google Discover vuole immagini in evidenza larghe almeno ' + esc( min ) + ' px: queste vanno sostituite con una versione più grande. Articoli trovati: <strong>' + esc( d.small_featured_count ) + '</strong>.</p>';
		if ( ! d.small_featured || ! d.small_featured.length ) { return html; }
		return html + '<p><strong>Le più strette (prime 30):</strong></p><ul style="list-style:disc;margin-left:20px">' + d.small_featured.map( function ( i ) {
			var title = esc( i.title || '(senza titolo)' ), url = safeUrl( i.edit || '' );
			return '<li>' + ( url ? '<a href="' + esc( url ) + '" target="_blank" rel="noopener noreferrer">' + title + '</a>' : title ) + ' — ' + esc( i.size || '' ) + ' — ' + esc( i.name || '' ) + '</li>';
		} ).join( '' ) + '</ul>';
	}
	function scanHtml( d, final ) {
		if ( ! final ) { return '<p>Analisi… ' + d.checked + ' / ' + d.total + ' immagini</p>'; }
		return '<h3>Analisi' + ( d.at ? ' del ' + esc( when( d.at ) ) : '' ) + '</h3>' +
			'<p>' + d.checked + ' immagini. Peso totale su disco con le miniature: <strong>' + kb( d.bytes_all ) + '</strong> (solo gli originali: ' + kb( d.bytes_originals ) + ').</p>' +
			'<ul style="list-style:disc;margin-left:20px"><li>Originali sopra ' + esc( heavy ) + ': <strong>' + d.heavy_count + '</strong></li><li>Senza testo alternativo: <strong>' + d.no_alt_count + '</strong> (alcune immagini decorative possono non averlo)</li><li>File mancanti sul disco: <strong>' + d.missing_count + '</strong></li></ul>' +
			list( 'Le più pesanti (prime 30):', d.heavy, true ) + list( 'Senza testo alternativo (le più pesanti, prime 30):', d.no_alt, true ) + list( 'File mancanti (prime 30):', d.missing, false ) + smallHtml( d );
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
