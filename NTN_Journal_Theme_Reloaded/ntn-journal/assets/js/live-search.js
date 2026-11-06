/**
 * Suggerimenti della ricerca (v3.19.0): mentre si scrive, dalla seconda lettera, il plugin restituisce
 * fino a 4 articoli e 3 giochi del catalogo. Gruppi "// Articoli" e "// Giochi", poi "Tutti i risultati".
 * Frecce ↑ ↓ per scegliere, Invio per aprire, Esc per chiudere i suggerimenti (al secondo Esc, la barra).
 * La barra si apre e si chiude in main.js del tema padre.
 */
document.addEventListener('DOMContentLoaded', function() {
	const searchInput = document.getElementById('search-input-wp');
	if (!searchInput) return;

	const form = searchInput.form;
	const panel = document.getElementById('ntn-search-panel');
	const box = searchInput.closest('.ntn-search-box') || form;
	const clearBtn = form ? form.querySelector('.ntn-search-clear') : null;

	const resultsContainer = document.createElement('div');
	resultsContainer.id = 'ntn-live-search-results';
	resultsContainer.className = 'ntn-sdrop';
	resultsContainer.setAttribute('role', 'listbox');
	resultsContainer.setAttribute('aria-label', 'Suggerimenti');
	resultsContainer.hidden = true;
	box.appendChild(resultsContainer);

	searchInput.setAttribute('role', 'combobox');
	searchInput.setAttribute('aria-autocomplete', 'list');
	searchInput.setAttribute('aria-controls', resultsContainer.id);
	searchInput.setAttribute('aria-expanded', 'false');

	let searchTimeout;
	let controller = null;
	let selected = -1;
	let lastQuery = '';

	function escapeHTML(str) {
		return String(str).replace(/[&<>'"]/g,
			tag => ({
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				"'": '&#39;',
				'"': '&quot;'
			}[tag])
		);
	}

	// La parola cercata, sottolineata in rosso (il resto del titolo resta testo)
	function highlight(title, query) {
		const i = title.toLowerCase().indexOf(query.toLowerCase());
		if (i < 0) return escapeHTML(title);
		return escapeHTML(title.slice(0, i)) + '<mark>' + escapeHTML(title.slice(i, i + query.length)) + '</mark>' + escapeHTML(title.slice(i + query.length));
	}

	function safeUrl(url) {
		return /^https?:\/\//i.test(String(url || '')) ? encodeURI(String(url)) : '';
	}

	function options() {
		return resultsContainer.querySelectorAll('[role="option"]');
	}

	function select(i) {
		const list = options();
		selected = list.length ? (i + list.length) % list.length : -1;
		list.forEach((o, k) => o.setAttribute('aria-selected', k === selected ? 'true' : 'false'));
		if (selected > -1) {
			searchInput.setAttribute('aria-activedescendant', list[selected].id);
			list[selected].scrollIntoView({ block: 'nearest' });
		} else {
			searchInput.removeAttribute('aria-activedescendant');
		}
	}

	function closeResults() {
		resultsContainer.hidden = true;
		searchInput.setAttribute('aria-expanded', 'false');
		searchInput.removeAttribute('aria-activedescendant');
		selected = -1;
	}

	function show(html) {
		resultsContainer.innerHTML = html;
		Array.prototype.forEach.call(resultsContainer.children, (c, k) => c.style.setProperty('--i', k));
		selected = -1;
		// Esce dalla barra solo quando la barra ha finito di scendere
		if (!panel || panel.classList.contains('is-done')) {
			resultsContainer.hidden = false;
			searchInput.setAttribute('aria-expanded', 'true');
		}
	}

	function searchAllUrl(query) {
		const base = form ? form.getAttribute('action') : '/';
		return base + (base.indexOf('?') > -1 ? '&' : '?') + 's=' + encodeURIComponent(query);
	}

	function render(query, data) {
		const articles = Array.isArray(data.articles) ? data.articles : [];
		const games = Array.isArray(data.results) ? data.results : [];
		let html = '';
		let n = 0;

		if (articles.length) {
			html += '<div class="ntn-sgrp-h" aria-hidden="true"><span>// Articoli</span><span>' + escapeHTML(data.total_articles || articles.length) + '</span></div>';
			articles.forEach(item => {
				const url = safeUrl(item.url);
				const thumb = safeUrl(item.thumb);
				const meta = [item.kind ? '<b>' + escapeHTML(item.kind) + '</b>' : '', escapeHTML(item.date || '')].filter(Boolean).join(' · ');
				html += '<a class="ntn-sitem" role="option" id="ntn-so-' + (n++) + '" href="' + url + '">'
					+ '<span class="ntn-sitem-img">' + (thumb ? '<img src="' + thumb + '" alt="" width="56" height="35" loading="lazy" />' : '') + '</span>'
					+ '<span><span class="ntn-sitem-t">' + highlight(item.title || '', query) + '</span><span class="ntn-sitem-m">' + meta + '</span></span>'
					+ '<span class="ntn-sitem-go" aria-hidden="true">→</span></a>';
			});
		}

		if (games.length) {
			html += '<div class="ntn-sgrp-h" aria-hidden="true"><span>// Giochi</span><span>' + escapeHTML(data.total_games || games.length) + '</span></div>';
			games.forEach(item => {
				const url = safeUrl(item.url);
				const cover = safeUrl(item.cover);
				const meta = ['<b>Hub del gioco</b>', item.developer ? escapeHTML(item.developer) : '', item.articles ? escapeHTML(item.articles) + (Number(item.articles) === 1 ? ' articolo' : ' articoli') : ''].filter(Boolean).join(' · ');
				html += '<a class="ntn-sitem is-game" role="option" id="ntn-so-' + (n++) + '" href="' + url + '">'
					+ '<span class="ntn-sitem-img">' + (cover ? '<img src="' + cover + '" alt="" width="40" height="56" loading="lazy" />' : '') + '</span>'
					+ '<span><span class="ntn-sitem-t">' + highlight(item.title || '', query) + '</span><span class="ntn-sitem-m">' + meta + '</span></span>'
					+ '<span class="ntn-sitem-go" aria-hidden="true">→</span></a>';
			});
		}

		if (!n) {
			html += '<div class="ntn-snone">Nessun risultato per «' + escapeHTML(query) + '»</div>'
				+ '<p class="ntn-shelp">Prova con il nome del gioco o dello studio, oppure con una parola sola.</p>';
		}
		html += '<a class="ntn-sall" role="option" id="ntn-so-' + n + '" href="' + escapeHTML(searchAllUrl(query)) + '"><span>Tutti i risultati per «' + escapeHTML(query) + '»</span><span aria-hidden="true">→</span></a>';
		show(html);
	}

	function fetchLiveResults(query) {
		if (typeof ntn_catalog_vars === 'undefined' || !ntn_catalog_vars.rest_url) {
			return;
		}
		if (controller) controller.abort();
		controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

		const fetchUrl = ntn_catalog_vars.rest_url.replace('/games', '/live-search') + '?query=' + encodeURIComponent(query);

		fetch(fetchUrl, {
			method: 'GET',
			headers: { 'X-WP-Nonce': ntn_catalog_vars.rest_nonce || '' },
			signal: controller ? controller.signal : undefined
		})
		.then(response => response.json())
		.then(res => {
			if (query !== lastQuery) return; // è arrivata una risposta vecchia
			if (res && res.success && res.data) render(query, res.data);
		})
		.catch(() => {
			// Rete assente o richiesta annullata: niente suggerimenti, la ricerca completa funziona comunque
		});
	}

	function update() {
		const query = searchInput.value.trim();
		if (clearBtn) clearBtn.hidden = searchInput.value === '';
		clearTimeout(searchTimeout);

		if (query.length < 2) {
			lastQuery = '';
			closeResults();
			resultsContainer.innerHTML = '';
			return;
		}
		if (query === lastQuery && resultsContainer.innerHTML !== '') {
			show(resultsContainer.innerHTML);
			return;
		}
		lastQuery = query;
		searchTimeout = setTimeout(() => fetchLiveResults(query), 250);
	}

	searchInput.addEventListener('input', update);

	searchInput.addEventListener('focus', function() {
		if (resultsContainer.innerHTML.trim() !== '' && searchInput.value.trim().length > 1) {
			show(resultsContainer.innerHTML);
		}
	});

	searchInput.addEventListener('keydown', function(e) {
		if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
			if (resultsContainer.hidden) return;
			e.preventDefault();
			select(selected + (e.key === 'ArrowDown' ? 1 : -1));
		} else if (e.key === 'Escape' && !resultsContainer.hidden) {
			e.preventDefault();
			e.stopPropagation(); // il primo Esc chiude i suggerimenti, non la barra
			closeResults();
		}
	});

	if (form) {
		form.addEventListener('submit', function(e) {
			const list = options();
			if (!resultsContainer.hidden && selected > -1 && list[selected]) {
				e.preventDefault();
				window.location.href = list[selected].getAttribute('href');
			} else if (searchInput.value.trim() === '') {
				e.preventDefault();
			}
		});
	}

	if (clearBtn) {
		clearBtn.addEventListener('click', function() {
			searchInput.value = '';
			update();
			searchInput.focus();
		});
	}

	// Il clic dentro i suggerimenti non toglie il focus al campo
	resultsContainer.addEventListener('mousedown', e => e.preventDefault());

	document.addEventListener('click', function(e) {
		if (!box.contains(e.target)) closeResults();
	});

	if (panel) {
		panel.addEventListener('ntn:search-ready', function() {
			if (searchInput.value.trim().length > 1) update();
		});
		panel.addEventListener('ntn:search-close', closeResults);
	}
});
