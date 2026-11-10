/**
 * Scheda "Backup" di NTN Journal Funzioni: esportazione e importazione dei dati Core (a lotti, con barra di avanzamento).
 *
 * Dalla 4.29.0 il codice sta in questo file (prima era dentro tab-backup.php).
 * Il PHP passa due valori con wp_localize_script, nell'oggetto ntnBackup:
 * - ajaxUrl   indirizzo di admin-ajax.php;
 * - nonce     codice di sicurezza delle richieste di backup (azione ntn_backup_nonce).
 * Il resto del codice è identico a prima.
 */
(function() {
	const ajaxUrl     = ntnBackup.ajaxUrl;
	const backupNonce = ntnBackup.nonce;

	// Elementi Esportazione
	const btnExport    = document.getElementById('ntn-btn-export-core');
	const expWrap      = document.getElementById('ntn-export-progress-wrap');
	const expText      = document.getElementById('ntn-export-status-text');
	const expPercent   = document.getElementById('ntn-export-percentage');
	const expBar       = document.getElementById('ntn-export-bar');
	const expResult    = document.getElementById('ntn-export-result');

	// Elementi Importazione
	const btnImport    = document.getElementById('ntn-btn-import-core');
	const fileInput    = document.getElementById('ntn-import-file-input');
	const impWrap      = document.getElementById('ntn-import-progress-wrap');
	const impText      = document.getElementById('ntn-import-status-text');
	const impCounter   = document.getElementById('ntn-import-counter');
	const impBar       = document.getElementById('ntn-import-bar');
	const impResult    = document.getElementById('ntn-import-result');

	/**
	 * Logica di Esportazione Core Unificata
	 */
	if (btnExport) {
		btnExport.addEventListener('click', async function() {
			btnExport.disabled = true;
			expWrap.style.display = 'block';
			expResult.style.display = 'none';
			expBar.style.width = '5%';
			expPercent.textContent = '5%';
			expText.textContent = 'Inizializzazione esportazione Core...';

			let allLines = [];
			let currentPhase = 'init';
			let currentOffset = 0;
			let totalPosts = 0;
			let totalTerms = 0;

			try {
				while (currentPhase !== 'done') {
					if (currentPhase === 'init') {
						expText.textContent = 'Inizializzazione opzioni Core...';
						expBar.style.width = '8%';
						expPercent.textContent = '8%';
					} else if (currentPhase === 'posts') {
						expText.textContent = 'Esportazione contenuti, CPT e recensioni: ' + currentOffset + ' / ' + totalPosts;
						const totalItems = totalPosts + totalTerms;
						const pct = totalItems > 0 ? Math.min(85, Math.round(10 + (currentOffset / totalItems) * 75)) : 50;
						expBar.style.width = pct + '%';
						expPercent.textContent = pct + '%';
					} else if (currentPhase === 'terms') {
						expText.textContent = 'Esportazione tassonomie e hub page: ' + currentOffset + ' / ' + totalTerms;
						const totalItems = totalPosts + totalTerms;
						const pct = totalItems > 0 ? Math.min(95, Math.round(10 + ((totalPosts + currentOffset) / totalItems) * 85)) : 80;
						expBar.style.width = pct + '%';
						expPercent.textContent = pct + '%';
					}

					const formData = new FormData();
					formData.append('action', 'ntn_backup_export');
					formData.append('_ajax_nonce', backupNonce);
					formData.append('phase', currentPhase);
					formData.append('offset', currentOffset);
					formData.append('batch_size', 100);

					const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
					const data = await res.json();

					if (!data.success) {
						throw new Error(data.data || 'Errore durante l\'esportazione.');
					}

					if (data.data.lines) {
						allLines.push(data.data.lines);
					}

					if (currentPhase === 'init') {
						totalPosts = data.data.total_posts || 0;
						totalTerms = data.data.total_terms || 0;
					}

					if (data.data.done) {
						currentPhase = 'done';
					} else {
						currentPhase = data.data.next_phase;
						currentOffset = data.data.offset;
					}
				}

				// Genera e scarica il file JSONL
				expBar.style.width = '100%';
				expPercent.textContent = '100%';
				expText.textContent = 'Generazione pacchetto di backup...';

				const fullContent = allLines.join('');
				const blob = new Blob([fullContent], { type: 'application/x-jsonlines;charset=utf-8' });
				const downloadUrl = URL.createObjectURL(blob);
				const a = document.createElement('a');
				const today = new Date().toISOString().slice(0, 10);
				a.href = downloadUrl;
				a.download = 'ntn-core-backup-' + today + '.jsonl';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				URL.revokeObjectURL(downloadUrl);

				expResult.className = 'notice notice-success inline';
				expResult.style.padding = '10px';
				expResult.style.display = 'block';
				expResult.innerHTML = '<p style="margin: 0;"><strong>Esportazione completata con successo!</strong> Il file <code>ntn-core-backup-' + today + '.jsonl</code> è stato generato e scaricato.</p>';
			} catch (err) {
				expResult.className = 'notice notice-error inline';
				expResult.style.padding = '10px';
				expResult.style.display = 'block';
				expResult.innerHTML = '<p style="margin: 0;"><strong>Errore:</strong> ' + (err.message || 'Si è verificato un errore.') + '</p>';
			} finally {
				btnExport.disabled = false;
			}
		});
	}

	/**
	 * Logica di Importazione Core Unificata
	 */
	if (btnImport && fileInput) {
		btnImport.addEventListener('click', async function() {
			if (!fileInput.files || fileInput.files.length === 0) {
				alert('Seleziona prima un file di backup (.jsonl o .json).');
				return;
			}

			if (!confirm('Sei sicuro di voler importare questo file? Le impostazioni e i dati Core verranno aggiornati con le informazioni contenute nel backup.')) {
				return;
			}

			const file = fileInput.files[0];
			btnImport.disabled = true;
			fileInput.disabled = true;
			impWrap.style.display = 'block';
			impResult.style.display = 'none';
			impBar.style.width = '10%';
			impText.textContent = 'Caricamento file iniziale...';
			impCounter.textContent = '0%';

			try {
				const formData = new FormData();
				formData.append('action', 'ntn_backup_import');
				formData.append('_ajax_nonce', backupNonce);
				formData.append('ntn_backup_file', file);
				formData.append('offset', 0);
				formData.append('batch_size', 50);

				let res = await fetch(ajaxUrl, { method: 'POST', body: formData });
				let data = await res.json();

				if (!data.success) {
					throw new Error(data.data || 'Errore durante il caricamento iniziale.');
				}

				let done          = data.data.done;
				let totalImported = data.data.imported || 0;
				let filePath      = data.data.file_path || '';
				let offset        = data.data.offset || 0;

				// Loop per il batch streaming JSONL
				while (!done) {
					impText.textContent = 'Elaborazione record Core in streaming...';
					impCounter.textContent = totalImported + ' record elaborati';
					impBar.style.width = Math.min(95, 20 + Math.round((offset / file.size) * 75)) + '%';

					const chunkForm = new FormData();
					chunkForm.append('action', 'ntn_backup_import');
					chunkForm.append('_ajax_nonce', backupNonce);
					chunkForm.append('file_path', filePath);
					chunkForm.append('offset', offset);
					chunkForm.append('batch_size', 50);

					const chunkRes  = await fetch(ajaxUrl, { method: 'POST', body: chunkForm });
					const chunkData = await chunkRes.json();

					if (!chunkData.success) {
						throw new Error(chunkData.data || 'Errore durante l\'elaborazione del batch.');
					}

					totalImported += chunkData.data.imported || 0;
					offset = chunkData.data.offset;
					done   = chunkData.data.done;
				}

				impBar.style.width = '100%';
				impText.textContent = 'Completato!';
				impCounter.textContent = totalImported + ' record totali';

				impResult.className = 'notice notice-success inline';
				impResult.style.padding = '10px';
				impResult.style.display = 'block';
				impResult.innerHTML = '<p style="margin: 0;"><strong>Ripristino completato con successo!</strong> ' + totalImported + ' record elaborati, opzioni salvate e cache invalidate.</p>';
			} catch (err) {
				impResult.className = 'notice notice-error inline';
				impResult.style.padding = '10px';
				impResult.style.display = 'block';
				impResult.innerHTML = '<p style="margin: 0;"><strong>Errore durante il ripristino:</strong> ' + (err.message || 'Errore sconosciuto.') + '</p>';
			} finally {
				btnImport.disabled = false;
				fileInput.disabled = false;
			}
		});
	}
})();
