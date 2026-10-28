jQuery(document).ready(function($) {
	// v3.17.1: i dati che arrivano da IGDB si mostrano sempre come testo, mai come codice
	function ntnEsc(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function ntnSafeUrl(value) {
		var url = String(value || '');
		return /^(https?:)?\/\//i.test(url) ? ntnEsc(url) : '';
	}
	var $searchInput = $('#ntn_igdb_search_input');
	var $searchBtn = $('#ntn_igdb_search_btn');
	var $spinner = $('#ntn_igdb_spinner');
	var $resultsContainer = $('#ntn_igdb_results');

	$searchBtn.on('click', function(e) {
		e.preventDefault();
		var query = $searchInput.val().trim();
		if (query === '') return;

		$spinner.addClass('is-active');
		$resultsContainer.empty().hide();

		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'ntn_search_igdb',
				query: query,
				_ajax_nonce: ntn_igdb_vars.nonce
			},
			success: function(response) {
				$spinner.removeClass('is-active');
				if (response.success && response.data.length > 0) {
					var html = '<ul style="list-style:none; margin:0; padding:0;">';
					$.each(response.data, function(index, game) {
						var year = game.released ? game.released.substring(0,4) : 'N/A';
						html += '<li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; cursor: pointer;" class="ntn-igdb-result-item" data-id="' + ntnEsc(parseInt(game.id, 10) || 0) + '">';
						if (ntnSafeUrl(game.background_image)) {
							html += '<img src="' + ntnSafeUrl(game.background_image) + '" alt="" style="width: 50px; height: 50px; object-fit: cover;" />';
						} else {
							html += '<div style="width: 50px; height: 50px; background: #ddd;"></div>';
						}
						html += '<div><strong>' + ntnEsc(game.name) + '</strong> (' + ntnEsc(year) + ')</div>';
						html += '<button type="button" class="button button-small" style="margin-left: auto;">Seleziona</button>';
						html += '</li>';
					});
					html += '</ul>';
					$resultsContainer.html(html).show();
				} else if (!response.success && response.data && response.data.message) {
					$resultsContainer.html('<p style="padding:10px; color:#d63638;">Errore API: ' + ntnEsc(response.data.message) + '</p>').show();
				} else {
					$resultsContainer.html('<p style="padding:10px;">Nessun risultato trovato o chiavi API non configurate.</p>').show();
				}
			},
			error: function() {
				$spinner.removeClass('is-active');
				$resultsContainer.html('<p style="padding:10px; color:red;">Errore di connessione.</p>').show();
			}
		});
	});

	$(document).on('click', '.ntn-igdb-result-item', function(e) {
		e.preventDefault();
		var gameId = $(this).data('id');
		
		$spinner.addClass('is-active');
		$resultsContainer.hide();

		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'ntn_get_igdb_details',
				game_id: gameId,
				_ajax_nonce: ntn_igdb_vars.nonce
			},
			success: function(response) {
				$spinner.removeClass('is-active');
				if (response.success) {
					var data = response.data;
					$('#ntn_game_title').val(data.title);
					$('#ntn_game_cover').val(data.cover);
					$('#ntn_game_developer').val(data.developer);
					$('#ntn_game_publisher').val(data.publisher);
					$('#ntn_game_platforms').val(data.platforms);
					$('#ntn_game_release').val(data.release);
					$('#ntn_game_genres').val(data.genres);
					$('#ntn_game_engine').val(data.engine);
					$('#ntn_game_url').val(data.url);
					$('#ntn_game_video').val(data.video);
					$('#ntn_game_screenshots').val(data.screenshots);
					
					if (data.cover) {
						$('#ntn_game_cover_preview').attr('src', data.cover).show();
					} else {
						$('#ntn_game_cover_preview').hide();
					}
					
					alert('Dati di ' + data.title + ' importati con successo!');
				} else {
					alert('Errore: ' + (response.data.message || 'Errore sconosciuto'));
				}
			},
			error: function() {
				$spinner.removeClass('is-active');
				alert('Errore di connessione a IGDB.');
			}
		});
	});
});
