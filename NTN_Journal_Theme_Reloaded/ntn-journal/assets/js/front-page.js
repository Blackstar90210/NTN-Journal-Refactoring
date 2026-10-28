/**
 * Home (v3.4.0): cartina dell'Italia a matrice di punti nella fascia dell'Hub di sezione.
 * Il colore dei puntini arriva dalla variabile --ntn-band-map, quindi segue il tema chiaro o scuro.
 */
document.addEventListener('DOMContentLoaded', function () {
	var canvas = document.getElementById('ntn-italy');
	if (!canvas || !canvas.getContext) {
		return;
	}

	// Contorni in coordinate reali (longitudine, latitudine)
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

	function draw() {
		var r = canvas.getBoundingClientRect();
		if (!r.width || !r.height) {
			return;
		}
		var dpr = window.devicePixelRatio || 1;
		canvas.width = r.width * dpr;
		canvas.height = r.height * dpr;
		var c = canvas.getContext('2d');
		c.scale(dpr, dpr);
		var ratio = ((LON1 - LON0) * K) / (LAT1 - LAT0);
		var h = r.height, w = h * ratio;
		if (w > r.width) { w = r.width; h = w / ratio; }
		var ox = (r.width - w) / 2, oy = (r.height - h) / 2;
		var step = Math.max(4, Math.round(h / 60)), rad = step * 0.3;
		c.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--ntn-band-map').trim() || 'rgba(9,9,11,.5)';
		for (var y = step / 2; y < h; y += step) {
			for (var x = step / 2; x < w; x += step) {
				var lon = LON0 + (x / w) * (LON1 - LON0), lat = LAT1 - (y / h) * (LAT1 - LAT0);
				if (!AREE.some(function (p) { return inside(p, lon, lat); })) {
					continue;
				}
				c.beginPath();
				c.arc(ox + x, oy + y, rad, 0, Math.PI * 2);
				c.fill();
			}
		}
	}

	draw();
	window.addEventListener('resize', draw);
	// Il pulsante chiaro/scuro cambia la classe "dark" sulla pagina: la cartina si ridisegna con i colori giusti
	if (window.MutationObserver) {
		new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
	}
});
