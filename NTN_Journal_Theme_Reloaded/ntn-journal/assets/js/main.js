(function() {
	function updateThemeText() {
		var themeTexts = document.querySelectorAll('.theme-text');
		if (themeTexts.length > 0) {
			var isDark = document.documentElement.classList.contains('dark');
			themeTexts.forEach(function(el) {
				el.textContent = isDark ? 'DARK' : 'LIGHT';
			});
		}
	}

	function initToggle() {
		var themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
		updateThemeText();

		themeToggleBtns.forEach(function(btn) {
			if (!btn.dataset.hasListener) {
				btn.dataset.hasListener = "true";
				btn.addEventListener('click', function() {
					var isDark = document.documentElement.classList.contains('dark');
					if (isDark) {
						document.documentElement.classList.remove('dark');
						localStorage.setItem('ntn_blog_theme', 'light');
					} else {
						document.documentElement.classList.add('dark');
						localStorage.setItem('ntn_blog_theme', 'dark');
					}
					updateThemeText();

					// Dispatch custom event so dynamic layouts like Slider dots update live
					var event = new CustomEvent('themeChanged', { detail: { isDark: !isDark } });
					document.dispatchEvent(event);
				});
			}
		});
	}

	function updateWpClock() {
		var clockEl = document.getElementById('wp-header-clock');
		if (clockEl) {
			var phpDate = clockEl.getAttribute('data-date') || '';
			try {
				var options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
				var formattedTime = new Date().toLocaleTimeString('it-IT', options);
				clockEl.textContent = formattedTime + ' | ' + phpDate;
			} catch (e) {
				var formattedTimeFallback = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
				clockEl.textContent = formattedTimeFallback + ' | ' + phpDate;
			}
		}
	}
	setInterval(updateWpClock, 1000);
	updateWpClock();

	// Run immediately
	initToggle();

	// Also listen on DOMContentLoaded
	document.addEventListener('DOMContentLoaded', initToggle);

	// Mobile Menus & Dropdowns Logic
	document.addEventListener('DOMContentLoaded', function() {
		// 1. Pannelli della testata (v3.12.0): ricerca dalla lente e menu da telefono.
		//    Uno chiude l'altro; Esc chiude quello aperto e riporta il focus sul pulsante.
		//    v3.19.0: la ricerca scende con un'animazione (classi is-open / is-done, vedi input.css)
		//    invece di comparire di scatto; "is-done" arriva a discesa finita e lascia uscire i suggerimenti.
		var stillMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
		var panels = [
			{ btn: document.querySelector('.ntn-search-toggle'), panel: document.getElementById('ntn-search-panel'), focus: '#search-input-wp', anim: true },
			{ btn: document.querySelector('.ntn-menu-toggle'), panel: document.getElementById('ntn-mobile-nav'), focus: 'a' }
		].filter(function(p) { return p.btn && p.panel; });

		panels.forEach(function(p) {
			if (p.anim) p.panel.hidden = false; // con JavaScript la nasconde la classe, non l'attributo
		});

		function isOpen(p) {
			return p.anim ? p.panel.classList.contains('is-open') : !p.panel.hidden;
		}

		function setPanel(p, open) {
			if (p.anim) {
				clearTimeout(p.timer);
				p.panel.classList.remove('is-done');
				p.panel.classList.toggle('is-open', open);
				if (open) {
					p.timer = setTimeout(function() {
						p.panel.classList.add('is-done');
						p.panel.dispatchEvent(new CustomEvent('ntn:search-ready'));
					}, stillMotion && stillMotion.matches ? 0 : 380);
				} else {
					p.panel.dispatchEvent(new CustomEvent('ntn:search-close'));
				}
				p.btn.setAttribute('aria-label', open ? 'Chiudi la ricerca' : 'Apri la ricerca');
			} else {
				p.panel.hidden = !open;
			}
			p.btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			p.btn.classList.toggle('is-open', open);
		}

		panels.forEach(function(p) {
			p.btn.addEventListener('click', function() {
				var open = !isOpen(p);
				panels.forEach(function(other) { if (other !== p) setPanel(other, false); });
				setPanel(p, open);
				if (open) {
					var target = p.panel.querySelector(p.focus);
					if (target) target.focus({ preventScroll: true });
				}
			});
		});

		// Esc: i suggerimenti aperti si chiudono prima (live-search.js ferma l'evento), poi il pannello
		document.addEventListener('keydown', function(e) {
			if (e.key !== 'Escape') return;
			panels.forEach(function(p) {
				if (isOpen(p)) {
					setPanel(p, false);
					p.btn.focus();
				}
			});
		});

		// Tendina "Piattaforme": al tocco si apre e si chiude con il pulsante
		document.querySelectorAll('.ntn-nav-drop').forEach(function(drop) {
			var btn = drop.querySelector('.ntn-nav-drop-btn');
			var dropPanel = drop.querySelector('.ntn-nav-drop-panel');
			if (!btn) return;

			// Il pannello a colonne non deve uscire dal bordo destro dello schermo (v3.13.1)
			function fitPanel() {
				// v3.22.0: il pannello largo come il contenuto non va spostato
				if (!dropPanel || drop.classList.contains('is-mega')) return;
				dropPanel.style.left = '';
				requestAnimationFrame(function() {
					var rect = dropPanel.getBoundingClientRect();
					var limit = document.documentElement.clientWidth - 16;
					if (rect.width && rect.right > limit) {
						dropPanel.style.left = Math.round(limit - rect.right) + 'px';
					}
				});
			}
			drop.addEventListener('mouseenter', fitPanel);
			drop.addEventListener('focusin', fitPanel);
			btn.addEventListener('click', function() {
				var open = !drop.classList.contains('is-open');
				drop.classList.toggle('is-open', open);
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
				if (open) fitPanel();
			});
			document.addEventListener('click', function(e) {
				if (drop.classList.contains('is-open') && !drop.contains(e.target)) {
					drop.classList.remove('is-open');
					btn.setAttribute('aria-expanded', 'false');
				}
			});
			// v3.22.0: Esc chiude la tendina aperta con il clic e riporta il focus sul pulsante
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && drop.classList.contains('is-open')) {
					drop.classList.remove('is-open');
					btn.setAttribute('aria-expanded', 'false');
					btn.focus();
				}
			});
		});

		// 2. Dropdown Menus Touch Support (per i sottomenu di WP)
		// Nel pannello da telefono i sottomenu sono sempre aperti: lì il tocco segue subito il link
		var menuItemsWithChildren = document.querySelectorAll('.ntn-nav .menu-item-has-children');
		menuItemsWithChildren.forEach(function(item) {
			var link = item.querySelector('a');
			var submenu = item.querySelector('.sub-menu');
			var indicator = link ? link.querySelector('svg') : null;
			
			if (link && submenu) {
				link.addEventListener('click', function(e) {
					// Se su mobile o touch device puro (senza puntatore mouse/hover)
					var isMobile = window.innerWidth < 1024;
					var isTouchOnly = window.matchMedia && window.matchMedia('(hover: none) and (pointer: coarse)').matches;
					if (isMobile || isTouchOnly) {
						// Se non è già aperto, previeni il link e aprilo
						if (!item.classList.contains('is-open')) {
							e.preventDefault();
							// Chiudi eventuali altri sottomenu aperti
							var siblings = item.parentNode.children;
							if(siblings) {
								for(var i=0; i<siblings.length; i++) {
									if(siblings[i] === item) continue;
									siblings[i].classList.remove('is-open');
									var sibSub = siblings[i].querySelector('.sub-menu');
									if(sibSub) {
										sibSub.classList.remove('!block');
										sibSub.classList.add('hidden');
									}
									var sibLinkSvg = siblings[i].querySelector('a svg');
									if(sibLinkSvg) sibLinkSvg.classList.remove('-rotate-180', 'text-red-500', 'opacity-100');
								}
							}
							// Apri questo
							item.classList.add('is-open');
							submenu.classList.remove('hidden');
							submenu.classList.add('!block');
							if (indicator) {
								indicator.classList.add('-rotate-180', 'text-red-500', 'opacity-100');
							}
						} else {
							// Se è già aperto, il secondo click lascia seguire il link predefinito
						}
					}
				});
			}
		});
		
		// Chiudi i dropdown quando si clicca fuori
		document.addEventListener('click', function(e) {
			if (window.innerWidth < 1024 || ('ontouchstart' in window)) {
				menuItemsWithChildren.forEach(function(item) {
					if (item.classList.contains('is-open') && !item.contains(e.target)) {
						item.classList.remove('is-open');
						var submenu = item.querySelector('.sub-menu');
						if (submenu) {
							submenu.classList.remove('!block');
							submenu.classList.add('hidden');
						}
						var linkSvg = item.querySelector('a svg');
						if (linkSvg) linkSvg.classList.remove('-rotate-180', 'text-red-500', 'opacity-100');
					}
				});
			}
		});
	});

	// v3.16.0: "Copia link" nel pannello "// Condividi"
	document.addEventListener('click', function(e) {
		var btn = e.target.closest ? e.target.closest('[data-ntn-copy]') : null;
		if (!btn) return;
		e.preventDefault();
		var url = btn.getAttribute('data-ntn-copy');
		var done = function() {
			var label = btn.textContent;
			btn.classList.add('is-copied');
			btn.textContent = 'OK';
			setTimeout(function() {
				btn.classList.remove('is-copied');
				btn.textContent = label;
			}, 1800);
		};
		var fallback = function() {
			var field = document.createElement('textarea');
			field.value = url;
			field.setAttribute('readonly', '');
			field.style.position = 'fixed';
			field.style.opacity = '0';
			document.body.appendChild(field);
			field.select();
			try { document.execCommand('copy'); done(); } catch (err) { window.location.href = url; }
			document.body.removeChild(field);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(done, fallback);
		} else {
			fallback();
		}
	});

	// v3.16.0: colonna "intelligente" dell'articolo. Se ci sta nello schermo resta ferma in alto;
	// se è più alta scorre con la pagina finché compare il suo fondo, poi si ferma; tornando su la segue.
	document.addEventListener('DOMContentLoaded', function() {
		var col = document.querySelector('.ntn-art-aside-sticky');
		var box = col ? col.parentElement : null;
		if (!col || !box) return;
		col.classList.add('is-smart');
		var offset = 0;
		var place = function() {
			if (!col.offsetParent) return; // colonna nascosta (telefono e schermi stretti)
			var gapTop = document.body.classList.contains('admin-bar') ? 56 : 24;
			var gapBottom = 24;
			var h = col.offsetHeight;
			var room = box.offsetHeight - h;
			if (room <= 0) { offset = 0; col.style.top = '0px'; return; }
			var boxTop = box.getBoundingClientRect().top;
			var low = Math.min(gapTop, window.innerHeight - h - gapBottom);
			var t = Math.max(low, Math.min(gapTop, boxTop + offset));
			offset = Math.max(0, Math.min(room, t - boxTop));
			col.style.top = offset + 'px';
		};
		// Il calcolo è leggero: si fa a ogni scorrimento, così la colonna non resta mai indietro
		window.addEventListener('scroll', place, { passive: true });
		window.addEventListener('resize', place);
		window.addEventListener('load', place);
		place();
	});

	// Scheda con più giochi: frecce, pallini e tasti portano alla pagina del gioco;
	// contatore, pallini e frecce seguono anche lo scorrimento fatto a mano.
	document.addEventListener('DOMContentLoaded', function() {
		var cards = document.querySelectorAll('[data-ntn-gcarousel]');
		var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		Array.prototype.forEach.call(cards, function(card) {
			var track = card.querySelector('.ntn-gtrack');
			var total = track ? track.querySelectorAll('.ntn-gslide').length : 0;
			if (total < 2) return;
			var counter = card.querySelector('.ntn-gcard-count');
			var arrows = card.querySelectorAll('.ntn-gnav button');
			var dots = track.querySelectorAll('.dots button');
			var current = 0;
			var timer = null;
			var pad = function(n) { return (n < 10 ? '0' : '') + n; };

			var show = function(index) {
				var changed = index !== current;
				current = index;
				if (counter) counter.textContent = pad(index + 1) + ' / ' + pad(total);
				Array.prototype.forEach.call(arrows, function(arrow, position) {
					var off = position === 0 ? index === 0 : index === total - 1;
					// Il tasto che si spegne non può tenere il focus: passa all'altra freccia
					if (off && arrow === document.activeElement && arrows[1 - position]) arrows[1 - position].focus();
					arrow.disabled = off;
				});
				Array.prototype.forEach.call(dots, function(dot) {
					dot.setAttribute('aria-current', parseInt(dot.getAttribute('data-i'), 10) === index ? 'true' : 'false');
				});
				// La colonna intelligente ricalcola la sua altezza
				if (changed) window.dispatchEvent(new Event('resize'));
			};

			var goTo = function(index) {
				index = Math.max(0, Math.min(total - 1, index));
				track.scrollTo({ left: index * track.clientWidth, behavior: reducedMotion ? 'auto' : 'smooth' });
				show(index);
			};

			card.addEventListener('click', function(e) {
				var button = e.target.closest ? e.target.closest('button') : null;
				if (!button) return;
				if (button.hasAttribute('data-step')) {
					goTo(current + parseInt(button.getAttribute('data-step'), 10));
				} else if (button.hasAttribute('data-i')) {
					goTo(parseInt(button.getAttribute('data-i'), 10));
				}
			});

			card.addEventListener('keydown', function(e) {
				if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
				e.preventDefault();
				goTo(current + (e.key === 'ArrowLeft' ? -1 : 1));
			});

			track.addEventListener('scroll', function() {
				clearTimeout(timer);
				timer = setTimeout(function() {
					if (!track.clientWidth) return;
					show(Math.max(0, Math.min(total - 1, Math.round(track.scrollLeft / track.clientWidth))));
				}, 80);
			}, { passive: true });

			card.classList.add('is-ready');
		});
	});

	// Scheda da telefono con più giochi: le linguette cambiano pannello e riga della recensione;
	// frecce, Home e Fine passano da una linguetta all'altra.
	document.addEventListener('DOMContentLoaded', function() {
		var cards = document.querySelectorAll('[data-ntn-rmob]');
		var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		Array.prototype.forEach.call(cards, function(card) {
			var list = card.querySelector('.ntn-gnames');
			var tabs = card.querySelectorAll('.ntn-gname');
			var parts = card.querySelectorAll('.ntn-rmob, .ntn-rmob-rev');
			if (!list || tabs.length < 2) return;

			var show = function(index, withFocus) {
				Array.prototype.forEach.call(tabs, function(tab, position) {
					tab.setAttribute('aria-selected', position === index ? 'true' : 'false');
					tab.setAttribute('tabindex', position === index ? '0' : '-1');
				});
				Array.prototype.forEach.call(parts, function(part) {
					part.hidden = parseInt(part.getAttribute('data-i'), 10) !== index;
				});

				// Solo la fila delle linguette scorre, mai la pagina
				var listBox = list.getBoundingClientRect();
				var tabBox = tabs[index].getBoundingClientRect();
				var shift = tabBox.left < listBox.left ? tabBox.left - listBox.left : Math.max(0, tabBox.right - listBox.right);
				if (shift) list.scrollBy({ left: shift, behavior: reducedMotion ? 'auto' : 'smooth' });

				if (withFocus) tabs[index].focus({ preventScroll: true });
			};

			var indexOf = function(tab) {
				return Array.prototype.indexOf.call(tabs, tab);
			};

			// Tutti i pannelli alti come il più alto: cambiando gioco la pagina non salta
			var panels = card.querySelectorAll('.ntn-rmob');
			var equalize = function() {
				if (!card.offsetWidth) return;
				var tallest = 0;
				Array.prototype.forEach.call(panels, function(panel) {
					var wasHidden = panel.hidden;
					panel.hidden = false;
					panel.style.minHeight = '';
					tallest = Math.max(tallest, panel.offsetHeight);
					panel.hidden = wasHidden;
				});
				Array.prototype.forEach.call(panels, function(panel) {
					panel.style.minHeight = tallest + 'px';
				});
			};
			equalize();
			window.addEventListener('load', equalize);
			window.addEventListener('resize', equalize);

			list.addEventListener('click', function(e) {
				var tab = e.target.closest ? e.target.closest('.ntn-gname') : null;
				if (tab) show(indexOf(tab), false);
			});

			list.addEventListener('keydown', function(e) {
				var tab = e.target.closest ? e.target.closest('.ntn-gname') : null;
				if (!tab) return;
				var current = indexOf(tab);
				var next = -1;
				if (e.key === 'ArrowLeft') next = (current - 1 + tabs.length) % tabs.length;
				else if (e.key === 'ArrowRight') next = (current + 1) % tabs.length;
				else if (e.key === 'Home') next = 0;
				else if (e.key === 'End') next = tabs.length - 1;
				if (next < 0) return;
				e.preventDefault();
				show(next, true);
			});
		});
	});

	// v3.16.0: nell'indice la sezione che si sta leggendo diventa rossa
	document.addEventListener('DOMContentLoaded', function() {
		var links = document.querySelectorAll('.ntn-toc .ntn-toc-item a[href^="#"]');
		if (!links.length) return;
		var byId = {};
		var headings = [];
		Array.prototype.forEach.call(links, function(link) {
			var id = decodeURIComponent(link.getAttribute('href').slice(1));
			var target = document.getElementById(id);
			if (!target) return;
			(byId[id] = byId[id] || []).push(link);
			if (headings.indexOf(target) === -1) headings.push(target);
		});
		var setCurrent = function(id) {
			Array.prototype.forEach.call(links, function(link) {
				link.classList.remove('is-current');
				link.removeAttribute('aria-current');
			});
			(byId[id] || []).forEach(function(link) {
				link.classList.add('is-current');
				link.setAttribute('aria-current', 'location');
			});
		};
		var update = function() {
			// La sezione corrente è l'ultimo titolo già salito sopra il primo terzo dello schermo
			var current = null;
			headings.forEach(function(h) {
				if (h.getBoundingClientRect().top <= window.innerHeight / 3) current = h;
			});
			setCurrent(current ? current.id : null);
		};
		// Un solo controllo per fotogramma mentre si scorre
		var ticking = false;
		window.addEventListener('scroll', function() {
			if (ticking) return;
			ticking = true;
			window.requestAnimationFrame(function() {
				ticking = false;
				update();
			});
		}, { passive: true });
		update();
	});
})();
