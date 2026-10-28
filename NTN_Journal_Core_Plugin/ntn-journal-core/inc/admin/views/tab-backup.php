<?php
/**
 * Tab Backup / Ripristino Unificato NTN Journal Core
 *
 * @package NTN_Journal_Core
 * @version 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backup_nonce = wp_create_nonce( 'ntn_backup_nonce' );

// Statistiche rapide sui dati Core
$posts_with_core = get_transient( 'ntn_core_posts_count' );
if ( false === $posts_with_core ) {
	global $wpdb;
	$posts_with_core = (int) $wpdb->get_var( "
		SELECT COUNT(DISTINCT p.ID) 
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
		WHERE p.post_type IN ('videogioco', 'comunicato_stampa')
		   OR pm.meta_key LIKE '_ntn_%'
		   OR pm.meta_key LIKE 'ntn_%'
		   OR pm.meta_key IN ('rating', 'pros', 'cons', 'verdict', 'reviewed_item', 'ntn_review_source')
	" );
	set_transient( 'ntn_core_posts_count', $posts_with_core, 12 * HOUR_IN_SECONDS );
}

$terms_with_core = get_transient( 'ntn_core_terms_count' );
if ( false === $terms_with_core ) {
	global $wpdb;
	$terms_with_core = (int) $wpdb->get_var( "
		SELECT COUNT(DISTINCT t.term_id)
		FROM {$wpdb->terms} t
		INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
		LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
		WHERE tt.taxonomy IN ('game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'game_year')
		   OR tm.meta_key LIKE '_ntn_%'
		   OR tm.meta_key LIKE 'ntn_%'
	" );
	set_transient( 'ntn_core_terms_count', $terms_with_core, 12 * HOUR_IN_SECONDS );
}
?>
<!-- Backup Tab -->
<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%;">
	<h2>Backup / Ripristino Unificato NTN Journal Core</h2>
	<p>Strumento <strong>All-in-One</strong> per il salvataggio e il ripristino integrale dell'ecosistema <strong>NTN Journal Core</strong>: include tutte le impostazioni e opzioni (IGDB, Redirect 301/302, Breaking News, SEO Extras, AI Summaries), i Custom Post Types (<em>Videogiochi</em> e <em>Comunicati Stampa</em> con relative tassonomie), le schede recensione e i metadati delle Hub Page in formato JSONL in streaming ad alte prestazioni (con retrocompatibilità per vecchi file JSON).</p>
	
	<div class="notice notice-info inline" style="margin: 15px 0 20px 0; padding: 12px;">
		<p style="margin: 0;">
			<strong>Copertura Unificata Core:</strong> A differenza dell'esportatore XML standard di WordPress, questa procedura migra e ripristina la mappa di redirect, le API key, i metadati strutturati, le schede recensione e le relazioni tassonomiche del plugin senza dispersioni di dati.
		</p>
	</div>

	<!-- Indicatori Dati Presenti -->
	<div style="display: flex; gap: 20px; margin-bottom: 25px;">
		<div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; text-align: center;">
			<div style="font-size: 24px; font-weight: bold; color: #0f172a; font-family: monospace;"><?php echo esc_html( (string) $posts_with_core ); ?></div>
			<div style="font-size: 13px; color: #64748b; margin-top: 4px;">Post & CPT con Dati Core</div>
		</div>
		<div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; text-align: center;">
			<div style="font-size: 24px; font-weight: bold; color: #0f172a; font-family: monospace;"><?php echo esc_html( (string) $terms_with_core ); ?></div>
			<div style="font-size: 13px; color: #64748b; margin-top: 4px;">Termini, Tassonomie & Hub Page</div>
		</div>
	</div>

	<div style="display: flex; flex-wrap: wrap; gap: 30px;">
		
		<!-- Sezione Esportazione -->
		<div style="flex: 1; min-width: 320px; padding: 22px; background: #f0f6fc; border: 1px solid #c8d7e1; border-radius: 6px; display: flex; flex-direction: column;">
			<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-download" style="color: #0073aa;"></span>
				Esporta Pacchetto Core Unificato
			</h3>
			<p style="color: #475569; font-size: 13px; line-height: 1.5;">
				Genera un archivio JSONL contenente tutte le Opzioni Core, i CPT (videogiochi e comunicati), i metadati recensioni (voti, pro, contro), i collegamenti IGDB e le tassonomie personalizzate.
			</p>
			
			<div id="ntn-export-progress-wrap" style="display: none; margin-top: 15px; margin-bottom: 15px;">
				<div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; color: #334155;">
					<span id="ntn-export-status-text">Inizializzazione...</span>
					<span id="ntn-export-percentage">0%</span>
				</div>
				<div style="background: #e2e8f0; border-radius: 4px; height: 8px; overflow: hidden;">
					<div id="ntn-export-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
				</div>
			</div>

			<div id="ntn-export-result" style="display: none; margin-bottom: 15px;"></div>

			<div style="margin-top: auto; padding-top: 15px;">
				<button type="button" id="ntn-btn-export-core" class="button button-primary" style="display: inline-flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-database-export" style="font-size: 17px; line-height: 1.6;"></span>
					Esporta Dati Core (JSONL)
				</button>
			</div>
		</div>
		
		<!-- Sezione Importazione -->
		<div style="flex: 1; min-width: 320px; padding: 22px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; display: flex; flex-direction: column;">
			<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px; color: #9b2c2c;">
				<span class="dashicons dashicons-upload" style="color: #c53030;"></span>
				Ripristina Pacchetto Core Unificato
			</h3>
			<p style="color: #475569; font-size: 13px; line-height: 1.5;">
				Carica un file JSONL (o legacy JSON) per ripristinare opzioni, redirect, CPT, recensioni e metadati. I CPT mancanti vengono creati automaticamente in modo sicuro.
			</p>

			<div style="margin-bottom: 15px;">
				<input type="file" id="ntn-import-file-input" accept=".jsonl,.json" style="display: block; width: 100%; font-size: 13px;">
			</div>

			<div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 10px;">
				<p style="margin: 0; font-size: 12px; color: #742a2a;">
					<strong>Attenzione:</strong> Le opzioni del Core e i metadati corrispondenti verranno aggiornati con le informazioni contenute nel file.
				</p>
			</div>

			<div id="ntn-import-progress-wrap" style="display: none; margin-bottom: 15px;">
				<div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; color: #334155;">
					<span id="ntn-import-status-text">Importazione in corso...</span>
					<span id="ntn-import-counter">0 elementi</span>
				</div>
				<div style="background: #fed7d7; border-radius: 4px; height: 8px; overflow: hidden;">
					<div id="ntn-import-bar" style="background: #e53e3e; height: 100%; width: 30%; transition: width 0.3s;"></div>
				</div>
			</div>

			<div id="ntn-import-result" style="display: none; margin-bottom: 15px;"></div>

			<div style="margin-top: auto; padding-top: 15px;">
				<button type="button" id="ntn-btn-import-core" class="button button-primary" style="background: #c53030; border-color: #9b2c2c; display: inline-flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-database-import" style="font-size: 17px; line-height: 1.6;"></span>
					Importa Dati Core
				</button>
			</div>
		</div>

	</div>
</div>

<script>
(function() {
	const ajaxUrl     = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	const backupNonce = <?php echo wp_json_encode( $backup_nonce ); ?>;

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
</script>
