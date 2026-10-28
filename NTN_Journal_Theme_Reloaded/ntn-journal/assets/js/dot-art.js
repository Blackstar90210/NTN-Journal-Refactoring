/**
 * Hub di categoria (v3.14.0): disegni a matrice di punti e filtro A–Z.
 *
 * - <canvas data-ntn-dots="italia">                  cartina dell'Italia (Hub Studi)
 * - <canvas data-ntn-dots="joycon,dualsense,xbox">   uno o più controller affiancati (Hub Console)
 * - <canvas data-ntn-dots="pc"> / "android,ios"       postazione PC e telefoni (Hub PC Gaming e Mobile, v3.22.0)
 * - [data-ntn-az]                                     filtro A–Z dell'indice degli studi
 *
 * I colori arrivano dalle variabili --ntn-band-map e --ntn-band-accent, quindi seguono il tema.
 * I controller sono sagome riconoscibili disegnate a mano, non i loghi registrati (scelta del CEO).
 */
document.addEventListener('DOMContentLoaded', function () {
	var canvases = Array.prototype.slice.call(document.querySelectorAll('canvas[data-ntn-dots]'));

	/* ---------- Italia (contorni in coordinate reali, come nella Home) ---------- */
	var ITALIA = [[7.53,43.79],[7.0,44.15],[6.85,44.5],[7.0,44.85],[6.63,45.1],[7.1,45.25],[6.95,45.65],[6.87,45.83],[7.17,45.87],[7.55,45.98],[7.87,45.92],[8.15,46.25],[8.44,46.46],[8.6,46.12],[8.85,46.0],[9.02,45.83],[9.1,46.05],[9.28,46.3],[9.45,46.5],[9.85,46.37],[10.1,46.25],[10.15,46.62],[10.47,46.86],[11.15,46.97],[11.75,46.97],[12.2,47.08],[12.45,46.7],[13.0,46.6],[13.7,46.52],[13.65,46.3],[13.48,46.2],[13.65,45.98],[13.6,45.82],[13.92,45.64],[13.77,45.6],[13.5,45.75],[13.1,45.7],[12.6,45.52],[12.33,45.4],[12.35,45.15],[12.55,44.95],[12.28,44.6],[12.4,44.2],[12.65,44.0],[13.2,43.8],[13.62,43.55],[13.85,43.1],[14.1,42.6],[14.5,42.25],[14.9,42.0],[15.3,41.92],[15.75,41.92],[16.18,41.88],[16.05,41.7],[15.9,41.55],[16.3,41.32],[16.87,41.12],[17.4,40.88],[17.95,40.65],[18.3,40.3],[18.52,40.13],[18.37,39.8],[18.05,40.0],[17.95,40.25],[17.4,40.4],[17.2,40.47],[16.85,40.4],[16.6,40.1],[16.5,39.75],[17.0,39.45],[17.15,39.05],[16.7,38.75],[16.55,38.4],[16.15,38.0],[16.06,37.92],[15.68,37.95],[15.65,38.25],[15.85,38.65],[16.2,38.85],[16.05,39.3],[15.8,39.65],[15.65,40.05],[15.3,40.0],[14.95,40.2],[15.0,40.4],[14.75,40.66],[14.4,40.6],[14.3,40.72],[14.25,40.83],[14.05,40.82],[13.75,41.25],[13.05,41.23],[12.62,41.45],[12.25,41.75],[11.8,42.1],[11.4,42.38],[11.1,42.42],[10.75,42.75],[10.5,42.95],[10.55,43.25],[10.3,43.55],[10.25,43.9],[9.85,44.07],[9.5,44.2],[8.93,44.41],[8.48,44.3],[8.15,43.95],[7.53,43.79]];
	var SICILIA = [[12.43,37.8],[12.55,38.1],[12.95,38.05],[13.35,38.2],[13.7,37.98],[14.0,38.04],[14.6,38.05],[15.1,38.15],[15.65,38.27],[15.5,38.0],[15.25,37.8],[15.1,37.5],[15.2,37.25],[15.3,37.05],[15.1,36.68],[14.65,36.75],[14.35,36.95],[14.0,37.1],[13.6,37.25],[13.0,37.5],[12.6,37.62]];
	var SARDEGNA = [[9.18,41.25],[9.5,41.05],[9.65,40.9],[9.82,40.5],[9.65,40.2],[9.7,39.9],[9.65,39.5],[9.58,39.15],[9.15,39.2],[8.95,38.95],[8.65,38.87],[8.38,39.2],[8.5,39.7],[8.4,40.0],[8.47,40.3],[8.32,40.56],[8.15,40.65],[8.22,40.94],[8.4,40.84],[8.7,40.92],[8.95,41.12]];
	var ELBA = [[10.1,42.82],[10.43,42.83],[10.42,42.72],[10.1,42.73]];
	var AREE = [ITALIA, SICILIA, SARDEGNA, ELBA];
	var LON0 = 6.5, LON1 = 18.7, LAT0 = 36.5, LAT1 = 47.2, K = Math.cos(42 * Math.PI / 180);

	function inside(poly, x, y) {
		var out = false;
		for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
			var xi = poly[i][0], yi = poly[i][1], xj = poly[j][0], yj = poly[j][1];
			if (((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi)) {
				out = !out;
			}
		}
		return out;
	}

	/* ---------- Disegni a punti (v3.22.0): griglia fissa, un punto ogni U unità ----------
	 * Ogni profilo sta in un riquadro alto 260 (largo 400, o "w" se più stretto). I centri dei punti cadono sui
	 * multipli di U e ogni forma si aggancia alla griglia: tasti, frecce e fori sono simmetrici, senza punti
	 * mancanti. "accent" sono i tasti rossi, fatti di punti della stessa griglia. Sagome generiche, non loghi. */
	var U = 5;
	function gx(v) { return Math.round(v / U) * U; }
	function circ(c, x, y, r) { c.beginPath(); c.arc(gx(x), gx(y), r + 0.5, 0, Math.PI * 2); c.fill(); }
	function rr(c, x, y, w, h, r) {
		var x0 = (Math.ceil(x / U - 1e-6) - 0.5) * U, x1 = (Math.floor((x + w) / U + 1e-6) + 0.5) * U;
		var y0 = (Math.ceil(y / U - 1e-6) - 0.5) * U, y1 = (Math.floor((y + h) / U + 1e-6) + 0.5) * U;
		c.beginPath();
		if (c.roundRect) { c.roundRect(x0, y0, Math.max(U, x1 - x0), Math.max(U, y1 - y0), r || 0); } else { c.rect(x0, y0, Math.max(U, x1 - x0), Math.max(U, y1 - y0)); }
		c.fill();
	}
	function plus(c, x, y, len, th) { x = gx(x); y = gx(y); rr(c, x - th / 2, y - len / 2, th, len, 0); rr(c, x - len / 2, y - th / 2, len, th, 0); }
	function ring(c, outer, inner) { // bordo dello schermo: toglie il contorno e rimette l'interno
		rr(c, outer[0], outer[1], outer[2], outer[3], outer[4]);
		c.globalCompositeOperation = 'source-over'; rr(c, inner[0], inner[1], inner[2], inner[3], inner[4]); c.globalCompositeOperation = 'destination-out';
	}

	var PADS = {
		// Joy-Con: due pezzi alti, lato esterno molto arrotondato, lato interno dritto
		joycon: {
			body: function (c) { rr(c, 78, 6, 112, 248, [56, 6, 6, 56]); rr(c, 210, 6, 112, 248, [6, 56, 56, 6]); },
			cut: function (c) {
				circ(c, 134, 80, 27);
				[[134, 140], [158, 164], [134, 188], [110, 164]].forEach(function (q) { circ(c, q[0], q[1], 11); });
				rr(c, 162, 30, 18, 6, 2);
				rr(c, 140, 214, 16, 16, 3);
				[[266, 56], [290, 80], [266, 104], [242, 80]].forEach(function (q) { circ(c, q[0], q[1], 12); });
				circ(c, 266, 168, 27);
				plus(c, 228, 33, 18, 6);
				circ(c, 246, 222, 9);
			},
			accent: [{ c: [266, 56, 12] }, { c: [290, 80, 12] }, { c: [266, 104, 12] }, { c: [242, 80, 12] }]
		},
		// DualSense: spalle ai lati del touchpad, impugnature lunghe e svasate, arco centrale
		dualsense: {
			body: 'M200 26 C230 24 255 24 270 30 L300 30 C320 22 345 24 360 42 C380 64 392 100 396 140 C400 180 395 222 372 240 C352 256 322 250 310 228 C298 210 288 198 264 196 C242 194 226 204 200 204 C174 204 158 194 136 196 C112 198 102 210 90 228 C78 250 48 256 28 240 C5 222 0 180 4 140 C8 100 20 64 40 42 C55 24 80 22 100 30 L130 30 C145 24 170 24 200 26 Z',
			cut: function (c) {
				rr(c, 140, 40, 120, 68, 12);
				circ(c, 140, 142, 25); circ(c, 260, 142, 25);
				circ(c, 200, 160, 8);
				// croce: centro in (80, 90), quattro bracci uguali da 3 × 5 punti
				rr(c, 75, 60, 10, 20, 0); rr(c, 75, 100, 10, 20, 0);
				rr(c, 50, 85, 20, 10, 0); rr(c, 90, 85, 20, 10, 0);
				[[322, 60], [350, 90], [322, 120], [294, 90]].forEach(function (q) { circ(c, q[0], q[1], 13); });
				rr(c, 118, 40, 10, 22, 4); rr(c, 272, 40, 10, 22, 4);
			},
			accent: [{ c: [322, 60, 12] }, { c: [350, 90, 12] }, { c: [322, 120, 12] }, { c: [294, 90, 12] }]
		},
		// Controller Xbox: corpo compatto, stick asimmetrici, tasto Xbox in alto al centro
		xbox: {
			body: 'M200 34 C240 34 262 30 286 26 C318 20 350 30 366 56 C384 86 394 128 396 168 C398 206 388 236 362 244 C338 250 322 236 312 216 C298 198 284 190 258 190 C236 190 222 196 200 196 C178 196 164 190 142 190 C116 190 102 198 88 216 C78 236 62 250 38 244 C12 236 2 206 4 168 C6 128 16 86 34 56 C50 30 82 20 114 26 C138 30 160 34 200 34 Z',
			cut: function (c) {
				circ(c, 104, 98, 28);
				circ(c, 246, 140, 24);
				plus(c, 154, 140, 48, 15);
				circ(c, 200, 64, 16);
				circ(c, 172, 102, 7); circ(c, 228, 102, 7);
				rr(c, 194, 98, 12, 9, 3);
				[[300, 70], [328, 98], [300, 126], [272, 98]].forEach(function (q) { circ(c, q[0], q[1], 13); });
			},
			accent: [{ c: [300, 70, 12] }, { c: [328, 98, 12] }, { c: [300, 126, 12] }, { c: [272, 98, 12] }]
		},
		// Postazione PC: monitor sul piede, tastiera a griglia con la barra spaziatrice e mouse; W A S D in rosso
		pc: {
			body: function (c) {
				rr(c, 80, 0, 240, 150, 10); rr(c, 185, 150, 30, 20, 0); rr(c, 140, 170, 120, 10, 5);
				rr(c, 20, 195, 290, 65, 6); rr(c, 330, 195, 50, 65, 25);
			},
			cut: function (c) {
				rr(c, 90, 130, 220, 0, 0);
				for (var row = 0; row < 3; row++) {
					for (var col = 0; col < 13; col++) { rr(c, 30 + col * 20, 205 + row * 15, 10, 5, 0); }
				}
				rr(c, 90, 250, 150, 0, 0);
				rr(c, 355, 200, 0, 20, 0);
			},
			accent: [{ r: [70, 205, 10, 5] }, { r: [50, 220, 10, 5] }, { r: [70, 220, 10, 5] }, { r: [90, 220, 10, 5] }]
		},
		// Telefono Android: schermo con il bordo, foro della fotocamera, gioco con i comandi touch
		android: {
			w: 160,
			body: function (c) { rr(c, 15, 0, 130, 260, 20); rr(c, 150, 60, 0, 40, 0); rr(c, 150, 115, 0, 20, 0); },
			cut: function (c) {
				ring(c, [25, 10, 110, 240, 14], [30, 15, 100, 230, 10]);
				circ(c, 80, 30, 5);
				plus(c, 50, 200, 30, 10);
				circ(c, 105, 190, 8); circ(c, 120, 210, 8);
				rr(c, 60, 235, 40, 0, 0);
			},
			accent: [{ c: [105, 190, 8] }, { c: [120, 210, 8] }]
		},
		// iPhone: angoli più tondi, isola in alto, tasti sui due lati, stesso gioco con i comandi touch
		ios: {
			w: 160,
			body: function (c) { rr(c, 15, 0, 130, 260, 32); rr(c, 10, 55, 0, 20, 0); rr(c, 10, 85, 0, 35, 0); rr(c, 150, 75, 0, 45, 0); },
			cut: function (c) {
				ring(c, [25, 10, 110, 240, 26], [30, 15, 100, 230, 22]);
				rr(c, 60, 25, 40, 10, 6);
				plus(c, 50, 200, 30, 10);
				circ(c, 105, 190, 8); circ(c, 120, 210, 8);
				rr(c, 60, 235, 40, 0, 0);
			},
			accent: [{ c: [105, 190, 8] }, { c: [120, 210, 8] }]
		}
	};

	function colors() {
		var css = getComputedStyle(document.documentElement);
		return {
			dot: css.getPropertyValue('--ntn-band-map').trim() || 'rgba(9,9,11,.5)',
			accent: css.getPropertyValue('--ntn-band-accent').trim() || '#b91c1c'
		};
	}

	// Disegna un profilo nel riquadro (ox, oy, w, h): la forma si campiona esattamente sui centri della griglia
	function drawPad(c, kind, ox, oy, w, h, col) {
		var pd = PADS[kind], W = pd.w || 400, H = 260, sc = w / W;
		function mask(fn) {
			var m = document.createElement('canvas');
			m.width = W + 2; m.height = H + 2;
			var o = m.getContext('2d');
			o.translate(0.5, 0.5); o.fillStyle = '#000';
			fn(o);
			return o.getImageData(0, 0, m.width, m.height).data;
		}
		var shape = mask(function (o) {
			if (typeof pd.body === 'string' && window.Path2D) { o.fill(new Path2D(pd.body)); } else if (typeof pd.body === 'function') { pd.body(o); }
			o.globalCompositeOperation = 'destination-out';
			pd.cut(o);
		});
		var acc = mask(function (o) {
			(pd.accent || []).forEach(function (q) {
				if (q.c) { circ(o, q.c[0], q.c[1], q.c[2]); } else if (q.r) { rr(o, q.r[0], q.r[1], q.r[2], q.r[3], 0); }
			});
		});
		var rad = Math.max(0.6, U * sc * 0.34);
		for (var py = 0; py <= H; py += U) {
			for (var px = 0; px <= W; px += U) {
				var i = (py * (W + 2) + px) * 4 + 3;
				var red = acc[i] >= 128;
				if (!red && shape[i] < 128) { continue; }
				c.fillStyle = red ? col.accent : col.dot;
				c.beginPath(); c.arc(ox + px * sc, oy + py * sc, red ? rad * 1.25 : rad, 0, Math.PI * 2); c.fill();
			}
		}
	}

	function drawItaly(c, r, col) {
		var pad = 16, ratio = ((LON1 - LON0) * K) / (LAT1 - LAT0);
		var h = r.height - pad * 2, w = h * ratio;
		if (w > r.width - pad * 2) { w = r.width - pad * 2; h = w / ratio; }
		var ox = r.width - w - pad, oy = (r.height - h) / 2;
		var step = Math.max(5, Math.round(h / 70)), rad = step * 0.3;
		c.fillStyle = col.dot;
		for (var y = step / 2; y < h; y += step) {
			for (var x = step / 2; x < w; x += step) {
				var lon = LON0 + (x / w) * (LON1 - LON0), lat = LAT1 - (y / h) * (LAT1 - LAT0);
				if (!AREE.some(function (p) { return inside(p, lon, lat); })) { continue; }
				c.beginPath(); c.arc(ox + x, oy + y, rad, 0, Math.PI * 2); c.fill();
			}
		}
	}

	function draw(cv) {
		var r = cv.getBoundingClientRect();
		if (!r.width || !r.height) { return; }
		var dpr = window.devicePixelRatio || 1;
		cv.width = r.width * dpr; cv.height = r.height * dpr;
		var c = cv.getContext('2d');
		c.scale(dpr, dpr);
		var col = colors();
		var kinds = (cv.getAttribute('data-ntn-dots') || '').split(',').filter(function (k) { return k === 'italia' || PADS[k]; });
		if (kinds[0] === 'italia') { drawItaly(c, r, col); return; }
		kinds = kinds.filter(function (k) { return PADS[k]; });
		if (!kinds.length) { return; }
		// Uno o più disegni allineati a destra: ognuno con la sua larghezza su un'altezza comune;
		// con più disegni ("Tutte") stanno affiancati e più piccoli
		var pad = 24, n = kinds.length, gap = n > 1 ? 18 : 0;
		var units = kinds.map(function (k) { return (PADS[k].w || 400) / 260; });
		var sum = units.reduce(function (s, u) { return s + u; }, 0);
		var h = r.height - pad * 2;
		if (n > 1) { h = Math.min(h, r.height * (sum > 2 ? 0.42 : 0.48)); }
		var total = h * sum + gap * (n - 1);
		if (total > r.width - pad * 2) { h = (r.width - pad * 2 - gap * (n - 1)) / sum; total = h * sum + gap * (n - 1); }
		var x = r.width - total - pad, oy = (r.height - h) / 2;
		kinds.forEach(function (k, i) { var w = h * units[i]; drawPad(c, k, x, oy, w, h, col); x += w + gap; });
	}

	function drawAll() { canvases.forEach(draw); }
	if (canvases.length) {
		drawAll();
		window.addEventListener('resize', drawAll);
		// Il pulsante chiaro/scuro cambia la classe "dark" sulla pagina: i disegni si ridisegnano con i colori giusti
		if (window.MutationObserver) {
			new MutationObserver(drawAll).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
		}
	}

	/* ---------- Filtro A–Z dell'indice degli studi (senza JavaScript restano visibili tutti) ---------- */
	Array.prototype.slice.call(document.querySelectorAll('[data-ntn-az]')).forEach(function (bar) {
		var section = bar.closest('section');
		var cards = section ? Array.prototype.slice.call(section.querySelectorAll('.ntn-chub-card[data-letter]')) : [];
		bar.hidden = false;
		bar.addEventListener('click', function (e) {
			var btn = e.target.closest('button[data-letter]');
			if (!btn || btn.disabled) { return; }
			var letter = btn.getAttribute('data-letter');
			Array.prototype.slice.call(bar.querySelectorAll('button')).forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
			cards.forEach(function (card) { card.hidden = !!letter && card.getAttribute('data-letter') !== letter; });
		});
	});
});
