/**
 * Riquadro "Scansiona i tag senza gioco" della scheda IGDB: lista da confermare riga per riga o in massa.
 * Nomi di tag e giochi vengono da fuori: si scrivono sempre come testo (textContent), mai come codice.
 *
 * Dalla 4.28.0 il codice sta in questo file (prima era dentro views/igdb-tag-scanner.php).
 * I due codici di sicurezza (nonce) arrivano dal PHP con wp_localize_script, nell'oggetto ntnIgdbTagScanner:
 * - nonce         per la scansione, la conferma, "non è un gioco" e il ripristino (azione ntn_igdb_scan);
 * - searchNonce   per la ricerca dei giochi su IGDB (azione ntn_igdb_nonce).
 * Il resto del codice è identico a prima.
 */
document.addEventListener('DOMContentLoaded', function () {
	const nonce = ntnIgdbTagScanner.nonce;
	const searchNonce = ntnIgdbTagScanner.searchNonce;
	const startBtn = document.getElementById('ntn-scan-start');
	const allBtn = document.getElementById('ntn-scan-confirm-all');
	const resetBtn = document.getElementById('ntn-scan-reset');
	const statusEl = document.getElementById('ntn-scan-status');
	const listsEl = document.getElementById('ntn-scan-lists');
	const groups = [
		{ key: 'safe', title: 'Sicuri: un solo gioco, già selezionati', open: true },
		{ key: 'choose', title: 'Da scegliere: controlla il gioco', open: true },
		{ key: 'none', title: 'Nessun risultato: prova un nome diverso', open: false },
	];
	const containers = {};
	let busy = false;

	function post(action, fields) {
		const data = new URLSearchParams();
		data.append('action', action);
		data.append('_ajax_nonce', fields && fields._ajax_nonce ? fields._ajax_nonce : nonce);
		Object.keys(fields || {}).forEach(function (k) {
			if (k !== '_ajax_nonce') { data.append(k, fields[k]); }
		});
		return fetch(ajaxurl, { method: 'POST', body: data }).then(function (r) { return r.json(); });
	}

	function wait(ms) {
		return new Promise(function (resolve) { setTimeout(resolve, ms); });
	}

	function el(tag, text) {
		const node = document.createElement(tag);
		if (text !== undefined) { node.textContent = text; }
		return node;
	}

	function button(label, handler) {
		const b = el('button', label);
		b.type = 'button';
		b.className = 'button button-small';
		b.style.marginRight = '4px';
		b.addEventListener('click', handler);
		return b;
	}

	function fillSelect(select, games) {
		select.textContent = '';
		games.forEach(function (g) {
			const option = el('option', g.name + ' (' + (g.year || 'anno sconosciuto') + ', IGDB ' + g.id + ')');
			option.value = String(g.id);
			select.appendChild(option);
		});
	}

	function removeRow(row, note) {
		row.style.opacity = '0.6';
		row.dataset.done = '1';
		const check = row.querySelector('input[type="checkbox"]');
		if (check) { check.checked = false; check.disabled = true; }
		row.querySelectorAll('button, select, input[type="text"]').forEach(function (n) { n.disabled = true; });
		row.querySelector('.ntn-scan-note').textContent = note;
		updateCounts();
	}

	function confirmRow(row) {
		const select = row.querySelector('select');
		const igdbId = select ? select.value : '';
		if (!igdbId) {
			row.querySelector('.ntn-scan-note').textContent = 'Scegli prima un gioco.';
			return Promise.resolve(false);
		}
		return post('ntn_igdb_scan_confirm', { tag_id: row.dataset.tagId, igdb_id: igdbId }).then(function (res) {
			if (res.success) {
				removeRow(row, 'Collegato, Hub creata');
				return true;
			}
			row.querySelector('.ntn-scan-note').textContent = 'Errore: ' + String(res.data);
			return false;
		}).catch(function () {
			row.querySelector('.ntn-scan-note').textContent = 'Errore di comunicazione';
			return false;
		});
	}

	function buildRow(data) {
		const row = el('div');
		row.className = 'ntn-scan-row';
		row.dataset.tagId = String(data.tag_id);
		row.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px 12px; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--nadm-line);';

		const check = document.createElement('input');
		check.type = 'checkbox';
		check.checked = data.group === 'safe';
		check.setAttribute('aria-label', 'Seleziona ' + data.tag);
		row.appendChild(check);

		const info = el('div');
		info.style.cssText = 'flex: 1 1 260px; min-width: 0;';
		info.appendChild(el('strong', data.tag));
		info.appendChild(el('span', ' · articoli: ' + data.articles));
		const example = el('div', data.example);
		example.style.cssText = 'font-size: 12px; color: var(--nadm-muted);';
		info.appendChild(example);
		row.appendChild(info);

		const select = document.createElement('select');
		select.setAttribute('aria-label', 'Gioco per ' + data.tag);
		select.style.maxWidth = '100%';
		fillSelect(select, data.games);
		if (!data.games.length) { select.style.display = 'none'; }
		row.appendChild(select);

		const search = document.createElement('input');
		search.type = 'text';
		search.value = data.tag;
		search.setAttribute('aria-label', 'Cerca un altro nome per ' + data.tag);
		search.style.width = '180px';
		row.appendChild(search);
		row.appendChild(button('Cerca', function () {
			const note = row.querySelector('.ntn-scan-note');
			note.textContent = 'Cerco...';
			post('ntn_search_igdb', { _ajax_nonce: searchNonce, query: search.value }).then(function (res) {
				if (!res.success) {
					note.textContent = 'Errore: ' + String(res.data && res.data.message ? res.data.message : res.data);
					return;
				}
				const games = res.data.slice(0, 15).map(function (g) {
					return { id: g.id, name: g.name, year: (g.released || '').slice(0, 4) };
				});
				fillSelect(select, games);
				select.style.display = games.length ? '' : 'none';
				note.textContent = games.length ? 'Controlla il gioco nel menu' : 'Nessun risultato';
			}).catch(function () { note.textContent = 'Errore di comunicazione'; });
		}));

		row.appendChild(button('Conferma', function () { confirmRow(row); }));
		row.appendChild(button('Salta', function () { removeRow(row, 'Saltato per ora'); }));
		row.appendChild(button('Non è un gioco', function () {
			post('ntn_igdb_scan_not_game', { tag_id: data.tag_id }).then(function (res) {
				if (res.success) {
					removeRow(row, 'Escluso, non tornerà in lista');
				} else {
					row.querySelector('.ntn-scan-note').textContent = 'Errore: ' + String(res.data);
				}
			});
		}));

		const note = el('span');
		note.className = 'ntn-scan-note';
		note.style.cssText = 'flex-basis: 100%; font-size: 12px; font-weight: bold;';
		row.appendChild(note);
		return row;
	}

	function updateCounts() {
		groups.forEach(function (g) {
			const box = containers[g.key];
			const open = box.querySelectorAll('.ntn-scan-row:not([data-done="1"])').length;
			box.querySelector('summary').textContent = g.title + ' (' + open + ')';
		});
		const selected = listsEl.querySelectorAll('.ntn-scan-row:not([data-done="1"]) input[type="checkbox"]:checked').length;
		allBtn.textContent = 'Conferma selezionati (' + selected + ')';
		allBtn.style.display = listsEl.querySelector('.ntn-scan-row') ? '' : 'none';
	}

	function buildLists() {
		listsEl.textContent = '';
		groups.forEach(function (g) {
			const box = document.createElement('details');
			box.open = g.open;
			box.style.marginBottom = '12px';
			const summary = el('summary', g.title);
			summary.style.cssText = 'font-weight: bold; cursor: pointer;';
			box.appendChild(summary);
			containers[g.key] = box;
			listsEl.appendChild(box);
		});
	}

	listsEl.addEventListener('change', updateCounts);

	startBtn.addEventListener('click', async function () {
		if (busy) { return; }
		busy = true;
		startBtn.disabled = true;
		buildLists();
		let offset = 0;
		let total = 0;
		try {
			do {
				statusEl.textContent = total ? 'Scansione: ' + Math.min(offset, total) + ' di ' + total + ' tag...' : 'Scansione in corso...';
				const res = await post('ntn_igdb_scan_tags', { offset: offset });
				if (!res.success) { throw new Error(String(res.data)); }
				total = res.data.total;
				offset = res.data.next;
				res.data.rows.forEach(function (r) { containers[r.group].appendChild(buildRow(r)); });
				updateCounts();
			} while (offset < total);
			statusEl.textContent = total ? 'Scansione finita: ' + total + ' tag esaminati. Niente è stato modificato.' : 'Nessun tag da esaminare.';
		} catch (e) {
			statusEl.textContent = 'Scansione interrotta: ' + e.message;
		}
		startBtn.disabled = false;
		busy = false;
	});

	allBtn.addEventListener('click', async function () {
		if (busy) { return; }
		const rows = Array.from(listsEl.querySelectorAll('.ntn-scan-row:not([data-done="1"])')).filter(function (r) {
			return r.querySelector('input[type="checkbox"]').checked;
		});
		if (!rows.length || !window.confirm('Collegare ' + rows.length + ' tag a IGDB e creare le loro pagine Hub?')) { return; }
		busy = true;
		allBtn.disabled = true;
		let done = 0;
		let failed = 0;
		for (const row of rows) {
			statusEl.textContent = 'Conferma: ' + (done + failed + 1) + ' di ' + rows.length + '...';
			if (await confirmRow(row)) { done++; } else { failed++; }
			await wait(500);
		}
		statusEl.textContent = 'Collegati ' + done + (failed ? ', errori ' + failed : '') + '.';
		allBtn.disabled = false;
		busy = false;
	});

	resetBtn.addEventListener('click', function () {
		if (!window.confirm('Rimettere in lista tutti i tag segnati come "Non è un gioco"?')) { return; }
		post('ntn_igdb_scan_reset_excluded', {}).then(function (res) {
			statusEl.textContent = res.success ? 'Tag esclusi rimessi in lista: rilancia la scansione.' : 'Errore: ' + String(res.data);
		});
	});
});
