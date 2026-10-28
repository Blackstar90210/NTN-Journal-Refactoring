/**
 * NTN Journal Funzioni (v3.18.0): comandi della schermata.
 * - Redazione: gruppi da riordinare (trascinandoli o con le frecce), rinominare, eliminare;
 *   persone da aggiungere, spostare e togliere. Prima di salvare, i campi nascosti prendono
 *   il nome del gruppo in cui si trovano, nell'ordine della pagina.
 * - Recensioni e Comunicati: righe che si aprono e si chiudono, anche da tastiera.
 */
(function () {
	'use strict';

	function initials(name) {
		return String(name || '').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0); }).join('').toUpperCase();
	}

	function move(el, dir) {
		if (!el || !el.parentNode) return;
		if (dir < 0 && el.previousElementSibling) el.parentNode.insertBefore(el, el.previousElementSibling);
		if (dir > 0 && el.nextElementSibling) el.parentNode.insertBefore(el.nextElementSibling, el);
	}

	/* ---------- Redazione ---------- */
	var form = document.getElementById('nadm-team-form');
	if (form) {
		var groups = document.getElementById('nadm-groups');
		var tplGroup = document.getElementById('nadm-tpl-group');
		var tplPerson = document.getElementById('nadm-tpl-person');
		var dragged = null;

		function bindDrag(grp) {
			grp.addEventListener('dragstart', function (e) {
				if (e.target !== grp) return;
				dragged = grp;
				grp.classList.add('is-dragging');
				if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
			});
			grp.addEventListener('dragend', function () {
				grp.classList.remove('is-dragging');
				dragged = null;
			});
			grp.addEventListener('dragover', function (e) {
				if (!dragged || dragged === grp) return;
				e.preventDefault();
				var r = grp.getBoundingClientRect();
				groups.insertBefore(dragged, (e.clientY - r.top) < r.height / 2 ? grp : grp.nextSibling);
			});
		}
		Array.prototype.forEach.call(groups.querySelectorAll('.nadm-grp'), bindDrag);

		// I campi di testo non devono far partire il trascinamento
		groups.addEventListener('mousedown', function (e) {
			var grp = e.target.closest('.nadm-grp');
			if (grp) grp.setAttribute('draggable', e.target.closest('input, select, button') ? 'false' : 'true');
		});

		function addPerson(grp, id, name, posts) {
			var existing = form.querySelector('.nadm-person[data-person="' + id + '"]');
			var row;
			if (existing) {
				row = existing; // Una persona sta in un solo gruppo: si sposta
			} else {
				var frag = tplPerson.content.cloneNode(true);
				row = frag.querySelector('.nadm-person');
				row.setAttribute('data-person', id);
				row.querySelector('b').textContent = name;
				var av = row.querySelector('.nadm-av');
				av.textContent = '';
				var span = document.createElement('span');
				span.textContent = initials(name);
				av.appendChild(span);
				row.querySelector('.nadm-m').textContent = posts + (posts === '1' ? ' articolo' : ' articoli');
				row.querySelector('input[type="hidden"]').value = id;
				row.querySelectorAll('[aria-label]').forEach(function (b) {
					b.setAttribute('aria-label', b.getAttribute('aria-label').replace('__NAME__', name));
				});
			}
			grp.querySelector('.nadm-people').appendChild(row);
		}

		form.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-nadm]');
			if (!btn) return;
			var action = btn.getAttribute('data-nadm');
			var grp = btn.closest('.nadm-grp');
			var person = btn.closest('.nadm-person');
			if (action === 'group-up' || action === 'group-down') { move(grp, action === 'group-up' ? -1 : 1); btn.focus(); }
			if (action === 'group-remove') {
				var name = grp.querySelector('input[type="text"]').value || 'questo gruppo';
				if (window.confirm('Eliminare il gruppo "' + name + '"? Le sue persone non compariranno più nella pagina finché non le aggiungi a un altro gruppo.')) grp.remove();
			}
			if (action === 'person-up' || action === 'person-down') { move(person, action === 'person-up' ? -1 : 1); btn.focus(); }
			if (action === 'person-remove') person.remove();
			if (action === 'person-add') {
				var sel = grp.querySelector('[data-nadm-add]');
				var opt = sel.options[sel.selectedIndex];
				if (opt && opt.value) addPerson(grp, opt.value, opt.textContent, opt.getAttribute('data-posts') || '0');
				sel.selectedIndex = 0;
			}
			if (action === 'group-add') {
				var input = document.getElementById('nadm-new-group');
				var title = input.value.trim();
				if (!title) { input.focus(); return; }
				var key = 'n' + Date.now();
				var html = tplGroup.innerHTML.split('__KEY__').join(key);
				var box = document.createElement('div');
				box.innerHTML = html;
				var block = box.firstElementChild;
				block.querySelector('input[type="text"]').value = title;
				groups.appendChild(block);
				bindDrag(block);
				input.value = '';
				var none = document.getElementById('nadm-no-groups');
				if (none) none.remove();
			}
		});

		form.addEventListener('submit', function () {
			Array.prototype.forEach.call(groups.querySelectorAll('.nadm-grp'), function (grp) {
				var key = grp.getAttribute('data-group');
				grp.querySelectorAll('[data-nadm-field="people"]').forEach(function (inp) {
					inp.name = 'ntn_team_people[' + key + '][]';
				});
			});
			// Segnala che le persone sono state lette dalla pagina: senza questo il server non tocca le persone
			var flag = document.createElement('input');
			flag.type = 'hidden';
			flag.name = 'ntn_team_js';
			flag.value = '1';
			form.appendChild(flag);
		});
	}

	/* ---------- Recensioni e Comunicati: righe apribili ---------- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-nadm-toggle]');
		if (!btn) return;
		var row = document.getElementById(btn.getAttribute('data-nadm-toggle'));
		if (!row) return;
		var open = row.hidden;
		row.hidden = !open;
		btn.setAttribute('aria-expanded', String(open));
		btn.textContent = open ? 'Chiudi' : 'Apri';
		btn.closest('tr').classList.toggle('is-open', open);
	});
})();
