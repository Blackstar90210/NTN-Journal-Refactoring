<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Scanner IGDB Tab (v3.19.0: riquadri senza colori, solo nero, bianco e rosso; link al Catalogo) -->
<?php $ntn_catalog_url = get_post_type_archive_link( 'videogioco' ); ?>
<div class="card" style="padding: 20px; box-sizing: border-box; max-width: 100%;">
	<div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px 16px;">
		<h2>Scanner Automazioni IGDB</h2>
		<?php if ( is_string( $ntn_catalog_url ) && '' !== $ntn_catalog_url ) : ?>
			<a class="nadm-btn is-sm" href="<?php echo esc_url( $ntn_catalog_url ); ?>" target="_blank" rel="noopener noreferrer">Apri il Catalogo ↗</a>
		<?php endif; ?>
	</div>
	<p>Associa manualmente tag ai database IGDB per importare copertine, sviluppatori, generi e date di uscita.</p>

	<?php
	$scanner_active = get_option( 'ntn_igdb_scanner_active', 'yes' );
	?>
	<div style="background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong); padding: 15px; margin-bottom: 20px;">
		<form method="post" action="">
			<?php wp_nonce_field( 'ntn_save_igdb_scanner_settings', 'ntn_igdb_scanner_nonce' ); ?>
			<label style="font-weight: bold; display: flex; align-items: center; gap: 10px;">
				<input type="checkbox" name="ntn_igdb_scanner_active" value="yes" <?php checked( $scanner_active, 'yes' ); ?>>
				Attiva lo Scanner IGDB
			</label>
			<p class="description" style="margin-top: 5px; margin-bottom: 10px;">Utilizza questo interruttore per abilitare o disabilitare la visibilità e le funzionalità dello Scanner IGDB.</p>
			<input type="submit" class="button button-secondary" value="Salva Impostazione">
		</form>
	</div>

	<?php if ( $scanner_active !== 'yes' ) : ?>
		<div class="notice notice-warning" style="margin-left: 0;"><p>Lo Scanner IGDB è attualmente <strong>disattivato</strong>. Spunta la casella sopra e salva per riattivarlo.</p></div>
</div>
		<?php
		return;
endif;
	?>

	<?php
	$client_id     = get_option( 'ntn_igdb_client_id', '' );
	$client_secret = get_option( 'ntn_igdb_client_secret', '' );
	if ( empty( $client_id ) || empty( $client_secret ) ) :
		?>
		<div class="notice notice-error" style="margin-left:0; margin-bottom: 20px;"><p><strong>Attenzione:</strong> Devi configurare Client ID e Client Secret di IGDB nella tab "Servizi API" per poter usare questa funzione.</p></div>
	<?php endif; ?>

	<?php
	// Ottieni numero totale di articoli pubblicati
	$total_posts = wp_count_posts( 'post' )->publish;

	// Transients per query pesanti SQL
	global $wpdb;

	$posts_with_igdb = get_transient( 'ntn_igdb_post_count' );
	if ( false === $posts_with_igdb ) {
		// La query diretta è usata per ragioni di performance assolute, ma wrappata in un transitorio.
		$posts_with_igdb = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_ntn_game_title' AND meta_value != ''" );
		if ( ! $posts_with_igdb ) {
			$posts_with_igdb = 0;
		}
		set_transient( 'ntn_igdb_post_count', $posts_with_igdb, 12 * HOUR_IN_SECONDS );
	}

	$tags_with_igdb = get_transient( 'ntn_igdb_tag_count' );
	if ( false === $tags_with_igdb ) {
		$tags_with_igdb = $wpdb->get_var( "SELECT COUNT(DISTINCT term_id) FROM {$wpdb->termmeta} WHERE meta_key = '_ntn_game_title' AND meta_value != ''" );
		if ( ! $tags_with_igdb ) {
			$tags_with_igdb = 0;
		}
		set_transient( 'ntn_igdb_tag_count', $tags_with_igdb, 12 * HOUR_IN_SECONDS );
	}
	?>
	<div style="background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong); padding: 15px; margin-bottom: 25px;">
		<h3 style="margin-top:0;">Riepilogo Database</h3>
		<ul style="margin:0; padding-left: 20px;">
			<li><strong>Articoli Totali Pubblicati:</strong> <?php echo esc_html( $total_posts ); ?></li>
			<li><strong>Articoli Collegati a IGDB:</strong> <?php echo esc_html( $posts_with_igdb ); ?></li>
			<li><strong>Tag di Gioco Collegati a IGDB:</strong> <?php echo esc_html( $tags_with_igdb ); ?></li>
		</ul>
		<div style="margin-top: 15px;">
			<button id="ntn-refresh-cache-btn" class="button button-secondary">Ricalcola Cache Dati</button>
			<button id="ntn-clear-cache-btn" class="button button-secondary">Svuota Cache Dati</button>
			<button id="ntn-repair-reconnect-btn" class="button button-primary" style="margin-left: 10px;">Ripristina & Riconnetti Metadati Orfani</button>
			<span id="ntn-cache-status" style="margin-left: 10px; font-weight: bold;"></span>
		</div>
		<div style="margin-top: 15px;">
			<button id="ntn-load-connections" class="button button-secondary" style="margin-right: 10px;">Visualizza Articoli Collegati</button>
			<button id="ntn-load-tag-connections" class="button button-secondary" style="margin-right: 10px;">Visualizza Tag Collegati</button>
			<button id="ntn-reset-scanner" class="button nadm-btn is-danger">Scollega tutti i dati IGDB (Reset)</button>
		</div>
	</div>

	<?php require __DIR__ . '/igdb-tag-scanner.php'; // v4.15.0: lista dei tag da confermare ?>

	<!-- Container per gli articoli collegati -->
	<div id="ntn-connections-container" style="display:none; margin-bottom: 30px;">
		<h3 style="margin-top: 0; display:flex; align-items:center; justify-content:space-between;">
			<span>Articoli con Scheda Gioco</span>
			<button onclick="document.getElementById('ntn-connections-container').style.display='none'; document.getElementById('ntn-load-connections').disabled=false;" class="button">Chiudi Tabella</button>
		</h3>
		<div id="ntn-connections-loading" style="padding: 10px; background: var(--nadm-surface); text-align:center;">Caricamento in corso...</div>
		<div id="ntn-connections-table-wrapper"></div>
	</div>
	
	<!-- Container per i tag collegati -->
	<div id="ntn-tag-connections-container" style="display:none; margin-bottom: 30px;">
		<h3 style="margin-top: 0; display:flex; align-items:center; justify-content:space-between;">
			<span>Tag collegati a IGDB</span>
			<div>
				<button id="ntn-update-all-tags" class="button button-primary" style="display:none; margin-right:5px;">Aggiorna tutti</button>
				<button id="ntn-unlink-all-tags" class="button nadm-btn is-danger" style="display:none; margin-right:10px;">Scollega tutti</button>
				<button onclick="document.getElementById('ntn-tag-connections-container').style.display='none'; document.getElementById('ntn-load-tag-connections').disabled=false;" class="button">Chiudi Tabella</button>
			</div>
		</h3>
		<div id="ntn-update-progress-container" style="display:none; margin-top: 10px; width: 100%;">
			<div style="background: var(--nadm-line); height: 20px; width: 100%; overflow: hidden;">
				<div id="ntn-update-progress-bar" style="background: var(--nadm-fg); height: 100%; width: 0%; transition: width 0.3s;"></div>
			</div>
			<div id="ntn-update-progress-text" style="text-align: center; font-size: 12px; margin-top: 5px; font-weight: bold;">0%</div>
		</div>
		<div id="ntn-tag-connections-loading" style="padding: 10px; background: var(--nadm-surface); text-align:center;">Caricamento in corso...</div>
		<div id="ntn-tag-connections-table-wrapper"></div>
	</div>

	<hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--nadm-line);">

	<div style="display: flex; flex-wrap: wrap; gap: 30px;">
		
		<!-- STEP 1: Trova Tag -->
		<div style="flex: 1; min-width: 300px; padding: 20px; background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong);">
			<h3 style="margin-top: 0;">1. Seleziona Tag WP</h3>
			<p>Cerca il tag a cui associare i dati IGDB (deve già esistere in WordPress).</p>
			<input type="text" id="ntn-tag-search-input" placeholder="Es: The Last of Us..." class="regular-text" style="width: 100%; margin-bottom: 10px;">
			<div id="ntn-tag-search-results" style="max-height: 200px; overflow-y: auto; background: var(--nadm-bg); border: 1px solid var(--nadm-line-strong); display: none;"></div>
			
			<div id="ntn-selected-tag-display" style="margin-top: 15px; padding: 10px; background: var(--nadm-bg); color: var(--nadm-fg); border: 1px solid var(--nadm-fg); box-shadow: inset 2px 0 0 var(--nadm-accent); display: none;">
				Nessun tag selezionato
			</div>
			<input type="hidden" id="ntn-selected-tag-id" value="">
		</div>

		<!-- STEP 2: Trova IGDB -->
		<div style="flex: 1; min-width: 300px; padding: 20px; background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong);">
			<h3 style="margin-top: 0;">2. Cerca nel Database IGDB</h3>
			<p>Cerca il videogioco nel database ufficiale.</p>
			<div style="display: flex; gap: 10px; margin-bottom: 10px;">
				<input type="text" id="ntn-igdb-search-input" placeholder="Es: The Last of Us Part II" class="regular-text" style="flex: 1;">
				<button id="ntn-igdb-search-btn" class="button button-primary">Cerca</button>
			</div>
			<div id="ntn-igdb-search-results" style="max-height: 250px; overflow-y: auto; background: var(--nadm-bg); border: 1px solid var(--nadm-line-strong); padding: 10px; margin-bottom: 10px;">
				I risultati appariranno qui...
			</div>
			<div id="ntn-igdb-selected-preview" style="padding: 10px; background: var(--nadm-bg); color: var(--nadm-fg); border: 1px solid var(--nadm-fg); box-shadow: inset 2px 0 0 var(--nadm-accent); display: none;">
			</div>
		</div>

		<!-- STEP 3: Salva Associazione -->
		<div style="flex: 1; min-width: 300px; padding: 20px; background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong);">
			<h3 style="margin-top: 0;">3. Articolo in evidenza (Opzionale)</h3>
			<p>Inserisci il link per un articolo che vuoi mettere in evidenza all'interno della Hub Page.</p>
			
			<div>
				<input type="url" id="ntn-review-url-input" placeholder="https://..." class="regular-text" style="width: 100%;">
			</div>
		</div>
	</div>

	<div style="margin-top: 30px;">
		<button id="ntn-save-tag-assoc-btn" class="button button-primary button-hero" disabled style="width: 100%; text-align: center;">Salva Associazione</button>
		<div id="ntn-tag-assoc-status" style="margin-top: 15px; font-weight: bold; text-align: center;"></div>
	</div>
</div>

<script>
	// v3.17.1: i nomi di tag e giochi si mostrano sempre come testo, mai come codice
	function ntnEsc(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	document.addEventListener("DOMContentLoaded", function() {
		const resetBtn = document.getElementById('ntn-reset-scanner');
		const loadBtn  = document.getElementById('ntn-load-connections');
		const loadTagsBtn = document.getElementById('ntn-load-tag-connections');
		
		// --- Tag Association Logic ---
		const tagInput = document.getElementById('ntn-tag-search-input');
		const tagResults = document.getElementById('ntn-tag-search-results');
		const tagSelectedId = document.getElementById('ntn-selected-tag-id');
		const tagDisplay = document.getElementById('ntn-selected-tag-display');
		
		const igdbInput = document.getElementById('ntn-igdb-search-input');
		const igdbBtn = document.getElementById('ntn-igdb-search-btn');
		const igdbResults = document.getElementById('ntn-igdb-search-results');
		const igdbPreview = document.getElementById('ntn-igdb-selected-preview');
		
		const saveAssocBtn = document.getElementById('ntn-save-tag-assoc-btn');
		const assocStatus = document.getElementById('ntn-tag-assoc-status');
		
		let selectedTag = null;
		let selectedIgdbGameId = null;
		
		function updateSaveBtn() {
			if (selectedTag && selectedIgdbGameId) {
				saveAssocBtn.disabled = false;
			} else {
				saveAssocBtn.disabled = true;
			}
		}
		
		// Tag Search
		let tagTimeout = null;
		tagInput.addEventListener('input', function() {
			clearTimeout(tagTimeout);
			const val = this.value.trim();
			if (val.length < 2) {
				tagResults.style.display = 'none';
				return;
			}
			
			tagTimeout = setTimeout(() => {
				tagResults.innerHTML = '<div style="padding: 5px;">Cerco...</div>';
				tagResults.style.display = 'block';
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_search_wp_tags');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_igdb_nonce' ); ?>');
				data.append('query', val);
				
				fetch(ajaxurl, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success && res.data.length > 0) {
						let html = '';
						res.data.forEach(t => {
							html += `<div class="ntn-tag-item" data-id="${ntnEsc(parseInt(t.term_id, 10) || 0)}" data-name="${ntnEsc(t.name)}" data-slug="${ntnEsc(t.slug || '')}" style="padding: 6px 8px; cursor: pointer; border-bottom: 1px solid var(--nadm-line); display: flex; justify-content: space-between; align-items: center;"><span><strong>${ntnEsc(t.name)}</strong> <small style="color: var(--nadm-muted);">(Slug: ${ntnEsc(t.slug || '')})</small></span><span style="font-size: 11px; color: var(--nadm-faint);">ID: ${ntnEsc(parseInt(t.term_id, 10) || 0)}</span></div>`;
						});
						tagResults.innerHTML = html;
						
						document.querySelectorAll('.ntn-tag-item').forEach(el => {
							el.addEventListener('click', function() {
								selectedTag = this.getAttribute('data-id');
								tagSelectedId.value = selectedTag;
								tagInput.value = this.getAttribute('data-name');
								tagResults.style.display = 'none';
								tagDisplay.innerHTML = "<strong>Tag selezionato:</strong> " + ntnEsc(this.getAttribute('data-name')) + (this.getAttribute('data-slug') ? " (Slug: <code>" + ntnEsc(this.getAttribute('data-slug')) + "</code>)" : "");
								tagDisplay.style.display = 'block';
								updateSaveBtn();
							});
						});
					} else {
						tagResults.innerHTML = '<div style="padding: 5px;">Nessun tag trovato.</div>';
					}
				});
			}, 500);
		});
		
		// IGDB Search
		igdbBtn.addEventListener('click', function(e) {
			e.preventDefault();
			const val = igdbInput.value.trim();
			if (!val) return;
			
			igdbBtn.disabled = true;
			igdbBtn.innerText = 'Cerco...';
			igdbResults.innerHTML = '<p>Ricerca su IGDB...</p>';
			igdbPreview.style.display = 'none';
			
			const data = new URLSearchParams();
			data.append('action', 'ntn_search_igdb');
			data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_igdb_nonce' ); ?>');
			data.append('query', val);
			
			fetch(ajaxurl, { method: 'POST', body: data })
			.then(r => r.json())
			.then(res => {
				igdbBtn.disabled = false;
				igdbBtn.innerText = 'Cerca';
				if (res.success && res.data.length > 0) {
					let html = '<ul style="list-style:none; padding:0; margin:0;">';
					res.data.forEach(game => {
						const year = game.released ? game.released.substring(0,4) : 'N/A';
						html += `<li style="padding:10px; border-bottom:1px solid var(--nadm-line); display:flex; align-items:center; justify-content:space-between;">
							<div>
								<strong>${ntnEsc(game.name)}</strong> (${ntnEsc(year)})
							</div>
							<button class="button ntn-select-game" data-id="${ntnEsc(parseInt(game.id, 10) || 0)}">Seleziona</button>
						</li>`;
					});
					html += '</ul>';
					igdbResults.innerHTML = html;
					
					// Add click event to igdb results
					const btns = igdbResults.querySelectorAll('.ntn-select-game');
					btns.forEach(b => {
						b.addEventListener('click', function(ev) {
							ev.preventDefault();
							const gameId = this.getAttribute('data-id');
							const gameName = this.closest('li').querySelector('strong').innerText;
							selectedIgdbGameId = gameId;
							igdbResults.innerHTML = ''; // clear results
							igdbPreview.innerHTML = `<strong>Gioco selezionato:</strong> ${ntnEsc(gameName)} (ID: ${ntnEsc(gameId)})`;
							igdbPreview.style.display = 'block';
							updateSaveBtn();
						});
					});
				} else {
					igdbResults.innerHTML = '<p style="color: var(--nadm-accent);">Nessun risultato trovato o errore API.</p>';
				}
			})
			.catch(err => {
				igdbBtn.disabled = false;
				igdbBtn.innerText = 'Cerca';
				igdbResults.innerHTML = '<p style="color: var(--nadm-accent);">Errore di connessione.</p>';
			});
		});
		
		// Save Association
		saveAssocBtn.addEventListener('click', function(e) {
			e.preventDefault();
			if (!selectedTag || !selectedIgdbGameId) return;
			
			saveAssocBtn.disabled = true;
			saveAssocBtn.innerText = 'Salvataggio...';
			assocStatus.innerText = 'Recupero dettagli IGDB e salvataggio sul Tag...';
			
			const data = new URLSearchParams();
			data.append('action', 'ntn_save_tag_igdb_assoc');
			data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_igdb_nonce' ); ?>');
			data.append('tag_id', selectedTag);
			data.append('igdb_id', selectedIgdbGameId);
			
			const reviewUrl = document.getElementById('ntn-review-url-input').value.trim();
			if ( reviewUrl ) {
				data.append('review_url', reviewUrl);
			}
			
			fetch(ajaxurl, { method: 'POST', body: data })
			.then(r => r.json())
			.then(res => {
				saveAssocBtn.disabled = false;
				saveAssocBtn.innerText = 'Salva Associazione';
				if (res.success) {
					assocStatus.style.color = '';
					assocStatus.innerText = 'Associazione salvata con successo!';
					
					// Reset form
					selectedTag = null;
					selectedIgdbGameId = null;
					tagSelectedId.value = '';
					tagInput.value = '';
					tagDisplay.style.display = 'none';
					igdbInput.value = '';
					igdbPreview.style.display = 'none';
					document.getElementById('ntn-review-url-input').value = '';
					updateSaveBtn();
					
					// Reload tag connections if open
					if (loadTagsBtn && loadTagsBtn.disabled) {
						loadTagsBtn.disabled = false;
						loadTagsBtn.click();
					}
				} else {
					assocStatus.style.color = 'var(--nadm-accent)';
					assocStatus.innerText = 'Errore: ' + (res.data || 'Sconosciuto');
				}
			})
			.catch(err => {
				console.error("Errore salvataggio tag assoc:", err);
				saveAssocBtn.disabled = false;
				saveAssocBtn.innerText = 'Salva Associazione';
				assocStatus.style.color = 'var(--nadm-accent)';
				assocStatus.innerText = 'Errore di connessione.';
			});
		});
		
		// --- Reset Data ---
		if (resetBtn) {
			resetBtn.addEventListener('click', function(e) {
				e.preventDefault();
				
				if (!confirm("ATTENZIONE! Questo scollegherà TUTTI gli articoli dai dati IGDB, cancellando copertine, generi e sviluppatori. Sei sicuro di voler procedere con il reset di emergenza?")) {
					return;
				}
				
				resetBtn.disabled = true;
				resetBtn.innerText = "Cancellazione in corso...";
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_reset_igdb_data');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, {
					method: 'POST',
					body: data
				})
				.then(response => response.json())
				.then(res => {
					if (res.success) {
						alert("Reset completato con successo!");
						location.reload();
					} else {
						alert("Errore durante il reset: " + (res.data || 'Sconosciuto'));
						resetBtn.disabled = false;
						resetBtn.innerText = "Scollega tutti i dati IGDB (Reset)";
					}
				})
				.catch(err => {
					alert("Errore di connessione.");
					console.error(err);
					resetBtn.disabled = false;
					resetBtn.innerText = "Scollega tutti i dati IGDB (Reset)";
				});
			});
		}
		
		// --- Load Connections ---
		if (loadBtn) {
			loadBtn.addEventListener('click', function(e) {
				e.preventDefault();
				document.getElementById('ntn-connections-container').style.display = 'block';
				loadBtn.disabled = true;
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_get_igdb_connections');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, {
					method: 'POST',
					body: data
				})
				.then(response => response.json())
				.then(res => {
					document.getElementById('ntn-connections-loading').style.display = 'none';
					if (res.success) {
						document.getElementById('ntn-connections-table-wrapper').innerHTML = res.data;
					} else {
						document.getElementById('ntn-connections-table-wrapper').innerHTML = '<p style="color: var(--nadm-accent);">Errore nel caricamento.</p>';
						loadBtn.disabled = false;
					}
				})
				.catch(err => {
					document.getElementById('ntn-connections-loading').style.display = 'none';
					document.getElementById('ntn-connections-table-wrapper').innerHTML = '<p style="color: var(--nadm-accent);">Errore di connessione.</p>';
					loadBtn.disabled = false;
				});
			});
			
			// Event delegation for single disconnect
			document.getElementById('ntn-connections-table-wrapper').addEventListener('click', function(e) {
				if (e.target.classList.contains('ntn-remove-single-igdb')) {
					e.preventDefault();
					const btn = e.target;
					const postId = btn.getAttribute('data-id');
					if (!confirm('Scollegare IGDB da questo articolo?')) return;
					
					btn.disabled = true;
					btn.innerText = 'Scollegamento...';
					
					const data = new URLSearchParams();
					data.append('action', 'ntn_reset_single_igdb');
					data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
					data.append('post_id', postId);
					
					fetch(ajaxurl, {
						method: 'POST',
						body: data
					})
					.then(response => response.json())
					.then(res => {
						if (res.success) {
							btn.closest('tr').style.opacity = '0.5';
							btn.innerText = 'Scollegato';
						} else {
							alert("Errore: " + (res.data || 'Sconosciuto'));
							btn.disabled = false;
							btn.innerText = 'Scollega';
						}
					})
					.catch(err => {
						alert("Errore di connessione.");
						btn.disabled = false;
						btn.innerText = 'Scollega';
					});
				}
			});
		}
		
		// --- Load Tag Connections ---
		if (loadTagsBtn) {
			loadTagsBtn.addEventListener('click', function(e) {
				e.preventDefault();
				document.getElementById('ntn-tag-connections-container').style.display = 'block';
				document.getElementById('ntn-tag-connections-table-wrapper').removeAttribute('data-live-checked');
				loadTagsBtn.disabled = true;
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_get_igdb_tag_connections');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, { method: 'POST', body: data })
				.then(response => response.json())
				.then(res => {
					document.getElementById('ntn-tag-connections-loading').style.display = 'none';
					if (res.success) {
						document.getElementById('ntn-tag-connections-table-wrapper').innerHTML = res.data;
					} else {
						document.getElementById('ntn-tag-connections-table-wrapper').innerHTML = '<p style="color: var(--nadm-accent);">Errore nel caricamento.</p>';
						loadTagsBtn.disabled = false;
					}
				})
				.catch(err => {
					document.getElementById('ntn-tag-connections-loading').style.display = 'none';
					document.getElementById('ntn-tag-connections-table-wrapper').innerHTML = '<p style="color: var(--nadm-accent);">Errore di connessione.</p>';
					loadTagsBtn.disabled = false;
				});
			});
			
			// Event delegation for single tag disconnect
			document.getElementById('ntn-tag-connections-table-wrapper').addEventListener('click', function(e) {
				if (e.target.classList.contains('ntn-remove-tag-igdb')) {
					e.preventDefault();
					const btn = e.target;
					const termId = btn.getAttribute('data-id');
					if (!confirm('Scollegare IGDB da questo Tag? Tutti gli articoli con questo tag perderanno la scheda se non hanno un collegamento diretto.')) return;
					
					btn.disabled = true;
					btn.innerText = 'Scollegamento...';
					
					const data = new URLSearchParams();
					data.append('action', 'ntn_reset_tag_igdb');
					data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
					data.append('tag_id', termId);
					
					fetch(ajaxurl, { method: 'POST', body: data })
					.then(response => response.json())
					.then(res => {
						if (res.success) {
							btn.closest('tr').style.opacity = '0.5';
							btn.innerText = 'Scollegato';
						} else {
							alert("Errore: " + (res.data || 'Sconosciuto'));
							btn.disabled = false;
							btn.innerText = 'Scollega';
						}
					})
					.catch(err => {
						alert("Errore di connessione.");
						btn.disabled = false;
						btn.innerText = 'Scollega';
					});
				}
			});

			// Event delegation for single tag update
			document.getElementById('ntn-tag-connections-table-wrapper').addEventListener('click', function(e) {
				if (e.target.classList.contains('ntn-update-tag-igdb')) {
					e.preventDefault();
					const btn = e.target;
					const termId = btn.getAttribute('data-id');
					const igdbId = btn.getAttribute('data-igdb-id');
					
					if (!igdbId) {
						alert('ID IGDB mancante per questo tag. Impossibile aggiornare.');
						return;
					}
					
					btn.disabled = true;
					btn.innerText = 'Aggiornamento...';
					
					const data = new URLSearchParams();
					data.append('action', 'ntn_update_single_tag_igdb');
					data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
					data.append('tag_id', termId);
					data.append('igdb_id', igdbId);
					
					fetch(ajaxurl, { method: 'POST', body: data })
					.then(response => response.json())
					.then(res => {
						if (res.success) {
							btn.innerText = 'Aggiornato';
						} else {
							alert("Errore: " + (res.data || 'Sconosciuto'));
							btn.disabled = false;
							btn.innerText = 'Aggiorna';
						}
					})
					.catch(err => {
						alert("Errore di connessione.");
						btn.disabled = false;
						btn.innerText = 'Aggiorna';
					});
				}
			});
			
			// Show buttons when table is loaded and do Live Check
			const loadTagsBtnObs = new MutationObserver(function(mutations) {
				const wrapper = document.getElementById('ntn-tag-connections-table-wrapper');
				if (wrapper.innerHTML.trim() !== '' && !wrapper.hasAttribute('data-live-checked')) {
					wrapper.setAttribute('data-live-checked', 'true');
					document.getElementById('ntn-update-all-tags').style.display = 'inline-block';
					document.getElementById('ntn-unlink-all-tags').style.display = 'inline-block';
					
					// Live Check
					const updateBtns = wrapper.querySelectorAll('.ntn-update-tag-igdb');
					let igdbIds = [];
					let btnMap = {};
					updateBtns.forEach(b => {
						const igdbId = b.getAttribute('data-igdb-id');
						if (igdbId) {
							igdbIds.push(igdbId);
							btnMap[igdbId] = b;
						}
					});
					
					if (igdbIds.length > 0) {
						const data = new URLSearchParams();
						data.append('action', 'ntn_check_igdb_updates');
						data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
						igdbIds.forEach(id => data.append('igdb_ids[]', id));
						
						fetch(ajaxurl, { method: 'POST', body: data })
						.then(r => r.json())
						.then(res => {
							if (res.success) {
								const updates = res.data;
								Object.keys(updates).forEach(id => {
									const btn = btnMap[id];
									const lastSync = parseInt(btn.getAttribute('data-last-sync') || '0', 10);
									const igdbUpdated = parseInt(updates[id] || '0', 10);
									
									if (igdbUpdated > lastSync || lastSync === 0) {
										// Show badge
										let badge = document.createElement('span');
										badge.textContent = '■ Nuovo';
										badge.style.color = 'var(--nadm-accent)';
										badge.style.fontFamily = 'var(--nadm-mono)';
										badge.style.fontSize = '10px';
										badge.style.textTransform = 'uppercase';
										badge.style.fontWeight = 'bold';
										badge.style.marginLeft = '5px';
										btn.parentNode.insertBefore(badge, btn.nextSibling);
									}
								});
							}
						}).catch(err => console.log('Errore live check IGDB', err));
					}
				}
			});
			loadTagsBtnObs.observe(document.getElementById('ntn-tag-connections-table-wrapper'), { childList: true });
			
			// Unlink all tags
			document.getElementById('ntn-unlink-all-tags').addEventListener('click', function(e) {
				e.preventDefault();
				if (!confirm('ATTENZIONE! Questo scollegherà TUTTI i tag dai dati IGDB. Sei sicuro?')) return;
				
				const btn = this;
				btn.disabled = true;
				btn.innerText = 'Scollegamento...';
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_unlink_all_tags_igdb');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, { method: 'POST', body: data })
				.then(response => response.json())
				.then(res => {
					if (res.success) {
						alert('Tutti i tag sono stati scollegati.');
						document.getElementById('ntn-load-tag-connections').click();
						btn.innerText = 'Scollega tutti';
						btn.disabled = false;
					} else {
						alert("Errore: " + (res.data || 'Sconosciuto'));
						btn.disabled = false;
						btn.innerText = 'Scollega tutti';
					}
				});
			});
			
			// Update all tags
			document.getElementById('ntn-update-all-tags').addEventListener('click', async function(e) {
				e.preventDefault();
				if (!confirm('Questo aggiornerà tutti i tag visibili recuperando i nuovi campi (trailer, screenshot). Potrebbe richiedere del tempo. Procedere?')) return;
				
				const btn = this;
				btn.disabled = true;
				
				const progressContainer = document.getElementById('ntn-update-progress-container');
				const progressBar = document.getElementById('ntn-update-progress-bar');
				const progressText = document.getElementById('ntn-update-progress-text');
				progressContainer.style.display = 'block';
				
				const updateBtns = document.querySelectorAll('.ntn-update-tag-igdb:not(:disabled)');
				
				for (let i = 0; i < updateBtns.length; i++) {
					const percent = Math.round(((i + 1) / updateBtns.length) * 100);
					progressBar.style.width = percent + '%';
					progressText.innerText = `Aggiornamento (${i+1}/${updateBtns.length}) - ${percent}%`;
					
					updateBtns[i].click();
					// Wait a bit to avoid hammering the server
					await new Promise(r => setTimeout(r, 1000));
				}
				
				progressText.innerText = 'Aggiornamento completato!';
				setTimeout(() => { 
					btn.disabled = false; 
					progressContainer.style.display = 'none';
					progressBar.style.width = '0%';
					progressText.innerText = '0%';
				}, 3000);
			});
		}
		
		// --- Cache Management ---
		const refreshCacheBtn = document.getElementById('ntn-refresh-cache-btn');
		const clearCacheBtn = document.getElementById('ntn-clear-cache-btn');
		const cacheStatus = document.getElementById('ntn-cache-status');
		
		if (refreshCacheBtn) {
			refreshCacheBtn.addEventListener('click', function(e) {
				e.preventDefault();
				refreshCacheBtn.disabled = true;
				cacheStatus.innerText = 'Calcolo in corso...';
				cacheStatus.style.color = '';
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_igdb_refresh_cache');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						cacheStatus.style.color = '';
						cacheStatus.innerText = 'Ricalcolato con successo! Aggiornamento...';
						setTimeout(() => location.reload(), 1000);
					} else {
						cacheStatus.style.color = 'var(--nadm-accent)';
						cacheStatus.innerText = 'Errore durante il calcolo.';
						refreshCacheBtn.disabled = false;
					}
				}).catch(err => {
					cacheStatus.style.color = 'var(--nadm-accent)';
					cacheStatus.innerText = 'Errore di rete.';
					refreshCacheBtn.disabled = false;
				});
			});
		}

		if (clearCacheBtn) {
			clearCacheBtn.addEventListener('click', function(e) {
				e.preventDefault();
				clearCacheBtn.disabled = true;
				cacheStatus.innerText = 'Svuotamento...';
				cacheStatus.style.color = '';
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_igdb_clear_cache');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						cacheStatus.style.color = '';
						cacheStatus.innerText = 'Cache svuotata! Aggiornamento...';
						setTimeout(() => location.reload(), 1000);
					} else {
						cacheStatus.style.color = 'var(--nadm-accent)';
						cacheStatus.innerText = 'Errore durante lo svuotamento.';
						clearCacheBtn.disabled = false;
					}
				}).catch(err => {
					cacheStatus.style.color = 'var(--nadm-accent)';
					cacheStatus.innerText = 'Errore di rete.';
					clearCacheBtn.disabled = false;
				});
			});
		}

		const repairReconnectBtn = document.getElementById('ntn-repair-reconnect-btn');
		if (repairReconnectBtn) {
			repairReconnectBtn.addEventListener('click', function(e) {
				e.preventDefault();
				if (!confirm('Eseguire la riconnessione automatica dei metadati IGDB orfani e l\'unificazione dei tag duplicati?')) return;
				
				repairReconnectBtn.disabled = true;
				cacheStatus.innerText = 'Riconnessione in corso...';
				cacheStatus.style.color = '';
				
				const data = new URLSearchParams();
				data.append('action', 'ntn_repair_reconnect_igdb_tags');
				data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ); ?>');
				
				fetch(ajaxurl, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						cacheStatus.style.color = '';
						cacheStatus.innerText = res.data.message || 'Riconnessione completata!';
						alert(res.data.message || 'Riconnessione completata con successo!');
						setTimeout(() => location.reload(), 1000);
					} else {
						cacheStatus.style.color = 'var(--nadm-accent)';
						cacheStatus.innerText = 'Errore durante la riconnessione.';
						alert('Errore: ' + (res.data || 'Impossibile completare la riconnessione.'));
						repairReconnectBtn.disabled = false;
					}
				}).catch(err => {
					cacheStatus.style.color = 'var(--nadm-accent)';
					cacheStatus.innerText = 'Errore di rete.';
					repairReconnectBtn.disabled = false;
				});
			});
		}

	});
</script>
