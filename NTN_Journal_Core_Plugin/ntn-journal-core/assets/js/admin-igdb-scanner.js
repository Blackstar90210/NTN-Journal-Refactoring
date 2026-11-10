/**
 * Scheda "IGDB" di NTN Journal Funzioni: associazione tag - gioco IGDB, controllo e aggiornamento dei dati.
 *
 * Dalla 4.27.0 il codice sta in questo file (prima era dentro tab-igdb.php, tra <script> e </script>).
 * I due codici di sicurezza (nonce) arrivano dal PHP con wp_localize_script, nell'oggetto ntnIgdbScanner:
 * - nonce        per le richieste ntn_search_wp_tags, ntn_search_igdb, ntn_save_tag_igdb_assoc;
 * - batchNonce   per le richieste del controllo e dell'aggiornamento dei giochi (ntn_batch_scan_igdb_nonce).
 * Il resto del codice è identico a prima.
 */
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
				data.append('_ajax_nonce', ntnIgdbScanner.nonce);
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
			data.append('_ajax_nonce', ntnIgdbScanner.nonce);
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
			data.append('_ajax_nonce', ntnIgdbScanner.nonce);
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
					data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
					data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
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
					data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
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
						data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
				data.append('_ajax_nonce', ntnIgdbScanner.batchNonce);
				
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
