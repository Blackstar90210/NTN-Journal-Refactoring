/**
 * Pagina "Migrazione IGDB" (Strumenti): migrazione dei tag con dati IGDB nelle schede Videogioco e sincronizzazione dei Franchise.
 *
 * Dalla 4.32.0 il codice sta in questo file (prima era dentro migrazione-igdb.php, tra i tag di script).
 * Il PHP passa con wp_localize_script, nell'oggetto ntnIgdbMigration, il codice di sicurezza:
 * - nonce   per le richieste ntn_migrate_tags_to_cpt e ntn_sync_franchises (azione ntn_igdb_migrate_nonce).
 * Il resto del codice è identico a prima.
 */
jQuery(document).ready(function($) {
	$('#ntn-run-migration').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		
		if ( ! confirm("Sei sicuro di voler avviare la migrazione? Potrebbe richiedere qualche secondo.") ) {
			return;
		}
		
		btn.prop('disabled', true).text('Migrazione in corso...');
		$('#ntn-migration-results').show().html('<p>Elaborazione in corso, attendere...</p>');

		$.post(ajaxurl, {
			action: 'ntn_migrate_tags_to_cpt',
			_ajax_nonce: ntnIgdbMigration.nonce
		}, function(response) {
			if ( response.success ) {
				$('#ntn-migration-results').html('<p style="color:green;"><strong>' + response.data.message + '</strong><br>Giochi migrati: ' + response.data.count + '</p>');
			} else {
				$('#ntn-migration-results').html('<p style="color:red;"><strong>Errore:</strong> ' + response.data + '</p>');
			}
			btn.prop('disabled', false).text('Esegui Migrazione Ora');
		}).fail(function() {
			$('#ntn-migration-results').html('<p style="color:red;"><strong>Errore di rete o timeout.</strong></p>');
			btn.prop('disabled', false).text('Esegui Migrazione Ora');
		});
	});

	function runFranchiseSync( offset ) {
		$.post(ajaxurl, {
			action: 'ntn_sync_franchises',
			offset: offset,
			_ajax_nonce: ntnIgdbMigration.nonce
		}, function(response) {
			if ( response.success ) {
				if ( response.data.done ) {
					$('#ntn-migration-results').append('<p style="color:green;"><strong>' + response.data.message + '</strong></p>');
					$('#ntn-run-franchise-sync').prop('disabled', false).text('Sincronizza Franchise Mancanti');
				} else {
					$('#ntn-migration-results').append('<p>Elaborati ' + response.data.processed + ' giochi. Procedo con i successivi...</p>');
					runFranchiseSync( response.data.next_offset );
				}
			} else {
				$('#ntn-migration-results').append('<p style="color:red;"><strong>Errore:</strong> ' + response.data + '</p>');
				$('#ntn-run-franchise-sync').prop('disabled', false).text('Sincronizza Franchise Mancanti');
			}
		}).fail(function() {
			$('#ntn-migration-results').append('<p style="color:red;"><strong>Errore di rete o timeout.</strong></p>');
			$('#ntn-run-franchise-sync').prop('disabled', false).text('Sincronizza Franchise Mancanti');
		});
	}

	$('#ntn-run-franchise-sync').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		btn.prop('disabled', true).text('Sincronizzazione in corso...');
		$('#ntn-migration-results').show().html('<p>Sincronizzazione Franchise avviata...</p>');
		runFranchiseSync(0);
	});
});
