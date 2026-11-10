/**
 * Riquadro "Miniature dei PNG trasparenti" della scheda Immagini: controllo, riparazione, quarantena, ripristino, eliminazione.
 *
 * Dalla 4.31.0 il codice sta in questo file (prima era dentro png-thumbnail-repair.php, tra i tag di script).
 * Il codice di sicurezza (nonce) arriva dall'attributo data-nonce del riquadro, come prima: il PHP non passa altro.
 * Il resto del codice è identico a prima.
 */
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
