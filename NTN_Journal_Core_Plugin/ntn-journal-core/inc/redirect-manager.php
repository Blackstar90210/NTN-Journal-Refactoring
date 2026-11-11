<?php
/**
 * NTN Journal - Gestore Redirect Interno
 *
 * Gestisce redirect 301/302 ad alte prestazioni senza uso di tabelle custom,
 * utilizzando un array serializzato su wp_options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// CORE LOGIC: Intercettazione del redirect ad altissime prestazioni
// -----------------------------------------------------------------------------

function ntn_get_redirects_map() {
	static $redirects_map = null;
	if ( null !== $redirects_map ) {
		return $redirects_map;
	}

	$redirects_map = get_option( 'ntn_redirects_map', array() );
	if ( ! is_array( $redirects_map ) ) {
		$redirects_map = array();
	}

	return $redirects_map;
}

function ntn_journal_process_redirects() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- solo confrontata con i redirect salvati, mai stampata.
	// Eseguiamo solo lato frontend, non in admin, ajax, cron o wp-login
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( '' !== $request_uri && strpos( $request_uri, 'wp-login' ) !== false ) ) {
		return;
	}

	$redirects_map = ntn_get_redirects_map();
	if ( empty( $redirects_map ) ) {
		return;
	}

	// Otteniamo la URI richiesta ignorando i query params per il match di base
	$parsed_url  = wp_parse_url( $request_uri );
	$path        = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

	// Normalizziamo il path (aggiungiamo/togliamo slash per maggiore flessibilità se serve)
	$paths_to_check = array(
		$path,
		untrailingslashit( $path ),
		trailingslashit( $path ),
	);
	$paths_to_check = array_unique( $paths_to_check );

	foreach ( $paths_to_check as $check_path ) {
		$hash = md5( $check_path );
		if ( isset( $redirects_map[ $hash ] ) && $redirects_map[ $hash ]['source'] === $check_path ) {
			$redirect = $redirects_map[ $hash ];
			$target   = $redirect['target'];
			$code     = isset( $redirect['code'] ) ? intval( $redirect['code'] ) : 301;

			// Incrementa i colpi (usando un transient per non intasare le scritture su DB)
			// Oppure usiamo un batch counter come prima ma per il singolo redirect.
			// Per un singolo redirect, salvare in DB ogni volta può essere oneroso, usiamo un option per batch
			$batch_key = 'ntn_redir_batch_' . $hash;
			$hits      = get_option( $batch_key, 0 );
			++$hits;
			if ( $hits >= 10 ) {
				$redirect['hits']          = ( isset( $redirect['hits'] ) ? $redirect['hits'] : 0 ) + $hits;
				$redirect['last_accessed'] = current_time( 'mysql' );
				$redirects_map[ $hash ]    = $redirect;
				update_option( 'ntn_redirects_map', $redirects_map, 'yes' );
				delete_option( $batch_key );
			} else {
				update_option( $batch_key, $hits, false );
			}

			wp_safe_redirect( $target, $code );
			exit;
		}
	}
}
// Hook priorità 1 per scattare prima di qualsiasi query inutile di WP
add_action( 'template_redirect', 'ntn_journal_process_redirects', 1 );


// -----------------------------------------------------------------------------
// MIGRAZIONE ONE-TIME
// -----------------------------------------------------------------------------
function ntn_journal_migrate_redirects() {
	if ( get_option( 'ntn_redirects_migrated' ) ) {
		return;
	}

	global $wpdb;
	$like_redir = $wpdb->esc_like( 'ntn_redir_' ) . '%';
	$like_batch = $wpdb->esc_like( 'ntn_redir_batch_' ) . '%';
	$results    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s",
			$like_redir,
			$like_batch
		)
	);

	$redirects_map = get_option( 'ntn_redirects_map', array() );
	if ( ! is_array( $redirects_map ) ) {
		$redirects_map = array();
	}

	if ( $results ) {
		foreach ( $results as $row ) {
			$val = maybe_unserialize( $row->option_value );
			if ( is_array( $val ) && isset( $val['source'] ) ) {
				$hash = str_replace( 'ntn_redir_', '', $row->option_name );
				if ( ! isset( $redirects_map[ $hash ] ) ) {
					$redirects_map[ $hash ] = $val;
				}
				delete_option( $row->option_name );
				delete_option( 'ntn_redir_batch_' . $hash );
			}
		}
		update_option( 'ntn_redirects_map', $redirects_map, 'yes' );
	}

	update_option( 'ntn_redirects_migrated', '1' );
}
add_action( 'admin_init', 'ntn_journal_migrate_redirects' );


// -----------------------------------------------------------------------------
// ADMIN INTERFACE: Rendering della Tab
// -----------------------------------------------------------------------------
function ntn_journal_render_redirects_ui() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$redirects_map = get_option( 'ntn_redirects_map', array() );
	if ( ! is_array( $redirects_map ) ) {
		$redirects_map = array();
	}

	// Salvataggio nuovo redirect
	if ( isset( $_POST['ntn_save_redirect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ntn_save_redirect_nonce'] ) ), 'ntn_save_redirect' ) ) {
		$source = isset( $_POST['redirect_source'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_source'] ) ) : '';
		$target = isset( $_POST['redirect_target'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_target'] ) ) : '';
		$code   = isset( $_POST['redirect_code'] ) ? intval( wp_unslash( $_POST['redirect_code'] ) ) : 0;

		if ( $source !== '' && $target !== '' ) {
			// Prevenzione Loop basica
			if ( $source === $target || trailingslashit( $source ) === trailingslashit( $target ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>Errore: URL di origine e destinazione non possono essere identici.</p></div>';
			} else {
				// Assicuriamoci che l'origine sia un path relativo partendo da /
				if ( strpos( $source, 'http' ) === 0 ) {
					$parsed = wp_parse_url( $source );
					$source = isset( $parsed['path'] ) ? $parsed['path'] : '/';
				}
				if ( substr( $source, 0, 1 ) !== '/' ) {
					$source = '/' . $source;
				}

				$hash     = md5( $source );
				$existing = isset( $redirects_map[ $hash ] ) ? $redirects_map[ $hash ] : false;

				$redirect_data = array(
					'source'        => $source,
					'target'        => $target,
					'code'          => $code,
					'hits'          => $existing ? ( isset( $existing['hits'] ) ? $existing['hits'] : 0 ) : 0,
					'created_at'    => $existing ? $existing['created_at'] : current_time( 'mysql' ),
					'last_accessed' => $existing ? $existing['last_accessed'] : '',
				);

				$redirects_map[ $hash ] = $redirect_data;
				update_option( 'ntn_redirects_map', $redirects_map, 'yes' );
				echo '<div class="notice notice-success is-dismissible"><p>Redirect salvato con successo.</p></div>';
			}
		}
	}

	// Eliminazione redirect (via POST hidden o GET con nonce)
	if ( isset( $_POST['ntn_delete_redirect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ntn_delete_redirect_nonce'] ) ), 'ntn_delete_redirect' ) ) {
		$del_hash = isset( $_POST['delete_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_hash'] ) ) : '';
		if ( ! empty( $del_hash ) && isset( $redirects_map[ $del_hash ] ) ) {
			unset( $redirects_map[ $del_hash ] );
			update_option( 'ntn_redirects_map', $redirects_map, 'yes' );
			delete_option( 'ntn_redir_batch_' . $del_hash );
			echo '<div class="notice notice-success is-dismissible"><p>Redirect rimosso con successo.</p></div>';
		}
	}

	$redirects = array();
	foreach ( $redirects_map as $hash => $val ) {
		$val['hash'] = $hash;
		$redirects[] = $val;
	}
	?>
	<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%;">
		<h2>Gestore Redirect 301/302</h2>
		<p>Aggiungi reindirizzamenti per mantenere intatta la SEO quando cambi gli URL dei tuoi articoli o pagine.</p>
		
		<hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
		
		<h3>Aggiungi Nuovo Redirect</h3>
		<form method="post" action="" style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 4px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
			<?php wp_nonce_field( 'ntn_save_redirect', 'ntn_save_redirect_nonce' ); ?>
			
			<div style="flex: 1; min-width: 250px;">
				<label for="redirect_source" style="display:block; margin-bottom: 5px;"><strong>URL di Origine</strong> (Path relativo, es. /vecchio-url/)</label>
				<input type="text" id="redirect_source" name="redirect_source" class="regular-text" style="width: 100%;" placeholder="/vecchio-articolo/" required>
			</div>
			
			<div style="flex: 1; min-width: 250px;">
				<label for="redirect_target" style="display:block; margin-bottom: 5px;"><strong>URL di Destinazione</strong> (Assoluto o Relativo)</label>
				<input type="text" id="redirect_target" name="redirect_target" class="regular-text" style="width: 100%;" placeholder="https://..." required>
			</div>
			
			<div>
				<label for="redirect_code" style="display:block; margin-bottom: 5px;"><strong>Tipo</strong></label>
				<select id="redirect_code" name="redirect_code">
					<option value="301">301 - Permanente</option>
					<option value="302">302 - Temporaneo</option>
				</select>
			</div>
			
			<div>
				<input type="submit" class="button button-primary" value="Aggiungi Redirect">
			</div>
		</form>
		
		<hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">
		
		<h3>Redirect Attivi (<?php echo count( $redirects ); ?>)</h3>
		
		<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
			<thead>
				<tr>
					<th>URL Origine</th>
					<th>URL Destinazione</th>
					<th style="width: 80px;">Codice</th>
					<th style="width: 80px;">Visite</th>
					<th style="width: 150px;">Ultimo Utilizzo</th>
					<th style="width: 100px;">Azioni</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $redirects ) ) : ?>
				<tr>
					<td colspan="6">Nessun redirect configurato al momento.</td>
				</tr>
				<?php else : ?>
					<?php
					// Ordiniamo per data creazione decrescente (ultimi aggiunti in cima)
					usort(
						$redirects,
						function ( $a, $b ) {
							$ta = isset( $a['created_at'] ) ? strtotime( $a['created_at'] ) : 0;
							$tb = isset( $b['created_at'] ) ? strtotime( $b['created_at'] ) : 0;
							return $tb - $ta; // discendente
						}
					);

					foreach ( $redirects as $r ) :
						?>
					<tr>
						<td><strong><?php echo esc_html( $r['source'] ); ?></strong></td>
						<td><a href="<?php echo esc_url( $r['target'] ); ?>" target="_blank"><?php echo esc_html( $r['target'] ); ?></a></td>
						<td><?php echo esc_html( $r['code'] ); ?></td>
						<td><?php echo isset( $r['hits'] ) ? esc_html( $r['hits'] ) : 0; ?></td>
						<td>
							<?php
							echo ! empty( $r['last_accessed'] ) ? esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $r['last_accessed'] ) ) ) : 'Mai';
							?>
						</td>
						<td>
							<form method="post" action="" onsubmit="return confirm('Sei sicuro di voler eliminare questo redirect?');">
								<?php wp_nonce_field( 'ntn_delete_redirect', 'ntn_delete_redirect_nonce' ); ?>
								<input type="hidden" name="delete_hash" value="<?php echo esc_attr( $r['hash'] ); ?>">
								<input type="submit" class="button button-link-delete" style="color: #d63638;" value="Elimina">
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
add_action( 'ntn_journal_render_redirects_tab', 'ntn_journal_render_redirects_ui' );


// -----------------------------------------------------------------------------
// REDIRECT PER CARTELLA (v4.0.0)
// Ogni indirizzo che inizia con la cartella di origine va nella cartella di destinazione,
// con il resto dell'indirizzo invariato (/category/console/ → /sezione/console/).
// I redirect singoli scattano prima (priorità 1) e quindi hanno la precedenza.
// -----------------------------------------------------------------------------

/**
 * Regole per cartella salvate (hash della cartella di origine => regola).
 *
 * @return array
 */
function ntn_get_folder_rules(): array {
	$rules = get_option( 'ntn_redirect_folder_rules', array() );
	return is_array( $rules ) ? $rules : array();
}

/**
 * Porta una cartella scritta a mano nella forma /cartella/ (solo il percorso, barre comprese).
 * Accetta anche l'indirizzo completo, ma solo del sito stesso. '' se non è valida.
 *
 * @param string $raw  Testo inserito.
 * @param string $host Dominio del sito ('' = non controllare).
 * @return string
 */
function ntn_redirect_folder_normalize( string $raw, string $host = '' ): string {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $raw ) ) {
		$parts = wp_parse_url( $raw );
		if ( ! is_array( $parts ) || ( '' !== $host && strtolower( (string) ( $parts['host'] ?? '' ) ) !== strtolower( $host ) ) ) {
			return '';
		}
		$raw = (string) ( $parts['path'] ?? '/' );
	}
	$path = '/' . trim( $raw, '/' );
	$path = '/' === $path ? '/' : $path . '/';
	if ( preg_match( '#[?\#\s\\\\<>"\']|\.\.|//#', $path ) ) {
		return '';
	}
	return $path;
}

/**
 * Nuovo percorso per la richiesta, se una regola attiva la copre (vince la cartella più lunga).
 *
 * @param string $path  Percorso richiesto (es. /category/console/page/2/).
 * @param array  $rules Regole per cartella.
 * @return array{hash:string, path:string}|null
 */
function ntn_redirect_folder_match( string $path, array $rules ): ?array {
	$best = null;
	foreach ( $rules as $hash => $rule ) {
		if ( ! is_array( $rule ) || empty( $rule['enabled'] ) || empty( $rule['source'] ) || empty( $rule['target'] ) ) {
			continue;
		}
		$source = (string) $rule['source'];
		$len    = strlen( $source );
		// Dentro la cartella (/category/x/) oppure la cartella senza barra finale (/category)
		if ( 0 !== strncmp( $path, $source, $len ) && rtrim( $source, '/' ) !== $path ) {
			continue;
		}
		if ( null === $best || $len > $best['len'] ) {
			$best = array(
				'hash' => (string) $hash,
				'len'  => $len,
				'path' => (string) $rule['target'] . substr( $path, $len ),
			);
		}
	}
	return null === $best ? null : array(
		'hash' => $best['hash'],
		'path' => $best['path'],
	);
}

/**
 * Le regole si rimandano all'infinito? (/a/ → /b/ con /b/ → /a/, oppure /a/ → /a/b/)
 *
 * @param array $rules Regole per cartella.
 * @return bool
 */
function ntn_redirect_folder_has_loop( array $rules ): bool {
	foreach ( $rules as $rule ) {
		if ( ! is_array( $rule ) || empty( $rule['enabled'] ) || empty( $rule['source'] ) ) {
			continue;
		}
		$path = (string) $rule['source'] . 'ntn-prova/';
		$seen = array( $path => true );
		for ( $step = 0; $step < 10; $step++ ) {
			$next = ntn_redirect_folder_match( $path, $rules );
			if ( null === $next ) {
				break;
			}
			$path = $next['path'];
			if ( isset( $seen[ $path ] ) ) {
				return true;
			}
			$seen[ $path ] = true;
		}
		if ( 10 === $step ) {
			return true;
		}
	}
	return false;
}

/**
 * Nuova regola attiva, senza visite.
 *
 * @param string $source Cartella di origine.
 * @param string $target Cartella di destinazione.
 * @return array
 */
function ntn_redirect_folder_new_rule( string $source, string $target ): array {
	return array(
		'source'        => $source,
		'target'        => $target,
		'enabled'       => true,
		'hits'          => 0,
		'created_at'    => current_time( 'mysql' ),
		'last_accessed' => '',
	);
}

/**
 * Controlla una nuova regola: il messaggio d'errore, '' se va bene.
 *
 * @param string $source Cartella di origine (già normalizzata).
 * @param string $target Cartella di destinazione (già normalizzata).
 * @param array  $rules  Regole già salvate.
 * @return string
 */
function ntn_redirect_folder_validate( string $source, string $target, array $rules ): string {
	if ( '' === $source || '' === $target ) {
		return 'Scrivi due cartelle valide di questo sito, per esempio /category/ e /sezione/ (niente indirizzi di altri siti).';
	}
	if ( '/' === $source ) {
		return 'La cartella di origine non può essere tutto il sito (/).';
	}
	if ( 0 === strpos( $source, '/wp-' ) ) {
		return 'Le cartelle di sistema di WordPress (/wp-…) non si possono deviare.';
	}
	if ( 0 === strpos( $target, $source ) ) {
		return 'La destinazione non può stare dentro la cartella di origine: il visitatore verrebbe deviato all\'infinito.';
	}
	$rules[ md5( $source ) ] = ntn_redirect_folder_new_rule( $source, $target );
	if ( ntn_redirect_folder_has_loop( $rules ) ) {
		return 'Questa regola e un\'altra già attiva si rimandano a vicenda all\'infinito: controlla le regole qui sotto.';
	}
	return '';
}

/**
 * Regole suggerite: le vecchie cartelle /category/ e /tag/ quando il sito usa basi diverse
 * (su Q-Gin /sezione/ e /argomento/) e per loro non c'è ancora una regola.
 *
 * @param array  $rules         Regole già salvate.
 * @param string $category_base Base delle categorie (Impostazioni → Permalink).
 * @param string $tag_base      Base dei tag.
 * @param string $home_path     Percorso della home ('' se il sito è nella radice del dominio).
 * @return array<int, array{source:string, target:string}>
 */
function ntn_redirect_folder_suggestions( array $rules, string $category_base, string $tag_base, string $home_path = '' ): array {
	$sources = array();
	foreach ( $rules as $rule ) {
		if ( is_array( $rule ) && isset( $rule['source'] ) ) {
			$sources[] = (string) $rule['source'];
		}
	}
	$out = array();
	foreach ( array(
		'category' => $category_base,
		'tag'      => $tag_base,
	) as $old => $base ) {
		$base   = trim( $base, '/' );
		$source = $home_path . '/' . $old . '/';
		if ( '' === $base || $old === $base || in_array( $source, $sources, true ) ) {
			continue;
		}
		$out[] = array(
			'source' => $source,
			'target' => $home_path . '/' . $base . '/',
		);
	}
	return $out;
}

/**
 * Indirizzo completo di un percorso del sito (stesso protocollo, dominio e porta della home).
 *
 * @param string $path Percorso che inizia con /.
 * @return string
 */
function ntn_redirect_site_url( string $path ): string {
	$home   = wp_parse_url( home_url() );
	$origin = ( $home['scheme'] ?? 'https' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );
	return $origin . $path;
}

/**
 * Conta una visita della regola, salvando la regola solo ogni 10 visite (come i redirect singoli).
 *
 * @param string $hash Chiave della regola.
 * @return void
 */
function ntn_redirect_folder_count_hit( string $hash ): void {
	$batch_key = 'ntn_folder_redir_batch_' . $hash;
	$hits      = (int) get_option( $batch_key, 0 ) + 1;
	if ( $hits < 10 ) {
		update_option( $batch_key, $hits, false );
		return;
	}
	$rules = ntn_get_folder_rules();
	if ( isset( $rules[ $hash ] ) ) {
		$rules[ $hash ]['hits']          = (int) ( $rules[ $hash ]['hits'] ?? 0 ) + $hits;
		$rules[ $hash ]['last_accessed'] = current_time( 'mysql' );
		update_option( 'ntn_redirect_folder_rules', $rules, true );
	}
	delete_option( $batch_key );
}

/**
 * Percorso della richiesta in corso e sua query string.
 *
 * @return array{path:string, query:string}
 */
function ntn_redirect_current_request(): array {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- confrontato con le regole, poi ripulito da wp_safe_redirect().
	$parsed      = wp_parse_url( $request_uri );
	return array(
		'path'  => is_array( $parsed ) && isset( $parsed['path'] ) ? (string) $parsed['path'] : '',
		'query' => is_array( $parsed ) && isset( $parsed['query'] ) ? (string) $parsed['query'] : '',
	);
}

/**
 * Applica le regole per cartella (dopo i redirect singoli), con la query string invariata.
 *
 * @return void
 */
function ntn_journal_process_folder_redirects() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	$rules = ntn_get_folder_rules();
	if ( empty( $rules ) ) {
		return;
	}
	$request = ntn_redirect_current_request();
	$match   = '' !== $request['path'] ? ntn_redirect_folder_match( $request['path'], $rules ) : null;
	if ( null === $match ) {
		return;
	}
	ntn_redirect_folder_count_hit( $match['hash'] );
	wp_safe_redirect( ntn_redirect_site_url( $match['path'] ) . ( '' !== $request['query'] ? '?' . $request['query'] : '' ), 301 );
	exit;
}
add_action( 'template_redirect', 'ntn_journal_process_folder_redirects', 2 );

/**
 * Toglie /page/N/ dalla fine del percorso.
 *
 * @param string $path Percorso richiesto.
 * @param string $base Parola delle pagine numerate (di solito "page").
 * @return string
 */
function ntn_redirect_strip_paged( string $path, string $base = 'page' ): string {
	$stripped = preg_replace( '#/' . preg_quote( $base, '#' ) . '/[0-9]+/?$#', '/', $path );
	return is_string( $stripped ) ? $stripped : $path;
}

/**
 * Ultima parte di un percorso di termini ("genitore/figlia" → "figlia").
 *
 * @param string $path Percorso o slug del termine.
 * @return string
 */
function ntn_core_redirect_leaf_slug( string $path ): string {
	$parts = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	return empty( $parts ) ? '' : sanitize_title( (string) end( $parts ) );
}

/**
 * L'archivio indicato dalla richiesta (senza il numero di pagina) esiste?
 *
 * @param array $qv Variabili della richiesta riconosciute da WordPress ($wp->query_vars).
 * @return bool
 */
function ntn_redirect_archive_exists( array $qv ): bool {
	if ( ! empty( $qv['category_name'] ) ) {
		// Come fa WordPress: basta l'ultima parte del percorso, così valgono anche le sottocategorie
		// raggiunte con un indirizzo corto (es. /sezione/microsoft/ per una figlia di "Piattaforme").
		return get_term_by( 'slug', ntn_core_redirect_leaf_slug( (string) $qv['category_name'] ), 'category' ) instanceof WP_Term;
	}
	if ( ! empty( $qv['tag'] ) ) {
		return get_term_by( 'slug', (string) $qv['tag'], 'post_tag' ) instanceof WP_Term;
	}
	if ( ! empty( $qv['author_name'] ) ) {
		return get_user_by( 'slug', (string) $qv['author_name'] ) instanceof WP_User;
	}
	foreach ( get_taxonomies(
		array(
			'public'   => true,
			'_builtin' => false,
		),
		'objects'
	) as $taxonomy ) {
		if ( ! empty( $taxonomy->query_var ) && ! empty( $qv[ $taxonomy->query_var ] ) ) {
			return get_term_by( 'slug', ntn_core_redirect_leaf_slug( (string) $qv[ $taxonomy->query_var ] ), $taxonomy->name ) instanceof WP_Term;
		}
	}
	if ( ! empty( $qv['pagename'] ) ) {
		return get_page_by_path( (string) $qv['pagename'] ) instanceof WP_Post;
	}
	$others = array_diff( array_keys( $qv ), array( 'paged', 'post_type' ) );
	if ( ! empty( $others ) ) {
		return false;
	}
	if ( ! empty( $qv['post_type'] ) ) {
		$type = get_post_type_object( (string) $qv['post_type'] );
		return $type && ! empty( $type->has_archive );
	}
	return true; // Solo il numero di pagina: l'elenco degli articoli in Home
}

/**
 * Pagina numerata oltre la fine di un archivio che esiste: alla prima pagina invece che all'errore.
 * 302 perché, con nuovi articoli, quella pagina può tornare a esistere.
 *
 * @return void
 */
function ntn_journal_process_paged_fallback() {
	if ( ! is_404() || 'yes' !== get_option( 'ntn_redirect_paged_fallback', 'yes' ) ) {
		return;
	}
	global $wp, $wp_rewrite;
	$qv = ( $wp instanceof WP && is_array( $wp->query_vars ) ) ? $wp->query_vars : array();
	if ( (int) ( $qv['paged'] ?? 0 ) < 2 || ! ntn_redirect_archive_exists( $qv ) ) {
		return;
	}
	$path  = ntn_redirect_current_request()['path'];
	$base  = ( $wp_rewrite instanceof WP_Rewrite && '' !== $wp_rewrite->pagination_base ) ? $wp_rewrite->pagination_base : 'page';
	$first = ntn_redirect_strip_paged( $path, $base );
	if ( $first === $path ) {
		return;
	}
	wp_safe_redirect( ntn_redirect_site_url( $first ), 302 );
	exit;
}
add_action( 'template_redirect', 'ntn_journal_process_paged_fallback', 3 );

/**
 * Interruttore "non tirare a indovinare": un indirizzo che non esiste resta un errore 404
 * invece di finire sulla pagina dal nome più simile. Spento di serie.
 *
 * @param bool $do_redirect Scelta di WordPress.
 * @return bool
 */
function ntn_journal_redirect_guess_404( $do_redirect ) {
	return 'yes' === get_option( 'ntn_redirect_no_guess', 'no' ) ? false : $do_redirect;
}
add_filter( 'do_redirect_guess_404_permalink', 'ntn_journal_redirect_guess_404' );

/**
 * Salvataggi della sezione: aggiungi, suggerite, attiva/disattiva, elimina, opzioni.
 * Restituisce l'avviso da mostrare.
 *
 * @return array{type:string, text:string}|null
 */
function ntn_journal_folder_redirects_handle_post(): ?array {
	if ( ! isset( $_POST['ntn_folder_rule_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ntn_folder_rule_nonce'] ) ), 'ntn_folder_rule' ) ) {
		return null;
	}
	$action = isset( $_POST['ntn_folder_action'] ) ? sanitize_key( wp_unslash( $_POST['ntn_folder_action'] ) ) : '';
	$hash   = isset( $_POST['ntn_folder_hash'] ) ? sanitize_key( wp_unslash( $_POST['ntn_folder_hash'] ) ) : '';
	$host   = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$rules  = ntn_get_folder_rules();

	if ( 'options' === $action ) {
		update_option( 'ntn_redirect_paged_fallback', isset( $_POST['ntn_redirect_paged_fallback'] ) ? 'yes' : 'no' );
		update_option( 'ntn_redirect_no_guess', isset( $_POST['ntn_redirect_no_guess'] ) ? 'yes' : 'no' );
		return array(
			'type' => 'success',
			'text' => 'Opzioni salvate.',
		);
	}

	if ( 'add' === $action ) {
		$source = ntn_redirect_folder_normalize( isset( $_POST['ntn_folder_source'] ) ? sanitize_text_field( wp_unslash( $_POST['ntn_folder_source'] ) ) : '', $host );
		$target = ntn_redirect_folder_normalize( isset( $_POST['ntn_folder_target'] ) ? sanitize_text_field( wp_unslash( $_POST['ntn_folder_target'] ) ) : '', $host );
		$error  = ntn_redirect_folder_validate( $source, $target, $rules );
		if ( '' !== $error ) {
			return array(
				'type' => 'error',
				'text' => $error,
			);
		}
		$key           = md5( $source );
		$rule          = ntn_redirect_folder_new_rule( $source, $target );
		$rules[ $key ] = isset( $rules[ $key ] ) ? array_merge(
			$rules[ $key ],
			array(
				'target'  => $target,
				'enabled' => true,
			)
		) : $rule;
		update_option( 'ntn_redirect_folder_rules', $rules, true );
		return array(
			'type' => 'success',
			'text' => 'Regola salvata: ' . $source . ' → ' . $target,
		);
	}

	if ( 'suggested' === $action ) {
		$home_path = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
		$added     = array();
		foreach ( ntn_redirect_folder_suggestions( $rules, (string) get_option( 'category_base', '' ), (string) get_option( 'tag_base', '' ), $home_path ) as $suggestion ) {
			if ( '' === ntn_redirect_folder_validate( $suggestion['source'], $suggestion['target'], $rules ) ) {
				$rules[ md5( $suggestion['source'] ) ] = ntn_redirect_folder_new_rule( $suggestion['source'], $suggestion['target'] );
				$added[]                               = $suggestion['source'] . ' → ' . $suggestion['target'];
			}
		}
		if ( empty( $added ) ) {
			return array(
				'type' => 'error',
				'text' => 'Nessuna regola suggerita da aggiungere.',
			);
		}
		update_option( 'ntn_redirect_folder_rules', $rules, true );
		return array(
			'type' => 'success',
			'text' => 'Regole aggiunte: ' . implode( ', ', $added ),
		);
	}

	if ( '' === $hash || ! isset( $rules[ $hash ] ) ) {
		return null;
	}

	if ( 'toggle' === $action ) {
		$rules[ $hash ]['enabled'] = empty( $rules[ $hash ]['enabled'] );
		if ( $rules[ $hash ]['enabled'] && ntn_redirect_folder_has_loop( $rules ) ) {
			return array(
				'type' => 'error',
				'text' => 'Questa regola non si può riattivare: con le altre regole attive devierebbe il visitatore all\'infinito.',
			);
		}
		update_option( 'ntn_redirect_folder_rules', $rules, true );
		return array(
			'type' => 'success',
			'text' => $rules[ $hash ]['enabled'] ? 'Regola attivata.' : 'Regola disattivata: resta salvata, ma non devia più nessuno.',
		);
	}

	if ( 'delete' === $action ) {
		unset( $rules[ $hash ] );
		update_option( 'ntn_redirect_folder_rules', $rules, true );
		delete_option( 'ntn_folder_redir_batch_' . $hash );
		return array(
			'type' => 'success',
			'text' => 'Regola eliminata.',
		);
	}

	return null;
}

/**
 * Pulsante di un'azione su una regola (attiva/disattiva, elimina).
 *
 * @param string $action  Azione.
 * @param string $hash    Chiave della regola.
 * @param string $label   Testo del pulsante.
 * @param string $confirm Domanda di conferma ('' = nessuna).
 * @return void
 */
function ntn_journal_folder_rule_button( string $action, string $hash, string $label, string $confirm = '' ): void {
	?>
	<form method="post" action="" style="display: inline;"<?php echo '' !== $confirm ? ' onsubmit="return confirm(\'' . esc_js( $confirm ) . '\');"' : ''; ?>>
		<?php wp_nonce_field( 'ntn_folder_rule', 'ntn_folder_rule_nonce' ); ?>
		<input type="hidden" name="ntn_folder_action" value="<?php echo esc_attr( $action ); ?>">
		<input type="hidden" name="ntn_folder_hash" value="<?php echo esc_attr( $hash ); ?>">
		<input type="submit" class="button<?php echo 'delete' === $action ? ' button-link-delete' : ''; ?>"<?php echo 'delete' === $action ? ' style="color: #d63638;"' : ''; ?> value="<?php echo esc_attr( $label ); ?>">
	</form>
	<?php
}

/**
 * Sezioni "Redirect per cartella" e "Indirizzi che non esistono" della scheda Redirect.
 *
 * @return void
 */
function ntn_journal_render_folder_redirects_ui() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = ntn_journal_folder_redirects_handle_post();
	if ( $notice ) {
		echo '<div class="notice notice-' . esc_attr( 'error' === $notice['type'] ? 'error' : 'success' ) . ' is-dismissible"><p>' . esc_html( $notice['text'] ) . '</p></div>';
	}

	$rules       = ntn_get_folder_rules();
	$home_path   = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
	$suggestions = ntn_redirect_folder_suggestions( $rules, (string) get_option( 'category_base', '' ), (string) get_option( 'tag_base', '' ), $home_path );
	uasort(
		$rules,
		function ( $a, $b ) {
			return strcmp( (string) ( $a['source'] ?? '' ), (string) ( $b['source'] ?? '' ) );
		}
	);
	?>
	<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%; margin-top: 20px;">
		<h2>Redirect per cartella</h2>
		<p>Ogni indirizzo che inizia con la cartella di origine viene portato nella cartella di destinazione, con il resto dell'indirizzo invariato.<br>
		Esempio: con la regola <code>/category/</code> → <code>/sezione/</code>, l'indirizzo <code>/category/console/microsoft/</code> diventa <code>/sezione/console/microsoft/</code>.<br>
		I redirect singoli qui sopra hanno la precedenza sulle regole per cartella.</p>

		<?php if ( ! empty( $suggestions ) ) : ?>
		<div style="background: #fcf9e8; border: 1px solid #dba617; border-left-width: 4px; padding: 12px 15px; margin: 15px 0; border-radius: 4px;">
			<p style="margin: 0 0 8px;"><strong>Suggerimento:</strong> le categorie e i tag del sito oggi usano cartelle diverse da quelle di WordPress, ma i vecchi indirizzi non portano più da nessuna parte. Queste regole li sistemano:</p>
			<ul style="margin: 0 0 10px 18px; list-style: disc;">
				<?php foreach ( $suggestions as $suggestion ) : ?>
				<li><code><?php echo esc_html( $suggestion['source'] ); ?></code> → <code><?php echo esc_html( $suggestion['target'] ); ?></code></li>
				<?php endforeach; ?>
			</ul>
			<form method="post" action="">
				<?php wp_nonce_field( 'ntn_folder_rule', 'ntn_folder_rule_nonce' ); ?>
				<input type="hidden" name="ntn_folder_action" value="suggested">
				<input type="submit" class="button button-primary" value="Aggiungi">
			</form>
		</div>
		<?php endif; ?>

		<h3>Aggiungi regola</h3>
		<form method="post" action="" style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 4px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
			<?php wp_nonce_field( 'ntn_folder_rule', 'ntn_folder_rule_nonce' ); ?>
			<input type="hidden" name="ntn_folder_action" value="add">
			<div style="flex: 1; min-width: 250px;">
				<label for="ntn_folder_source" style="display:block; margin-bottom: 5px;"><strong>Cartella di origine</strong> (vecchio indirizzo, es. /category/)</label>
				<input type="text" id="ntn_folder_source" name="ntn_folder_source" class="regular-text" style="width: 100%;" placeholder="/vecchia-cartella/" required>
			</div>
			<div style="flex: 1; min-width: 250px;">
				<label for="ntn_folder_target" style="display:block; margin-bottom: 5px;"><strong>Cartella di destinazione</strong> (nuovo indirizzo, es. /sezione/)</label>
				<input type="text" id="ntn_folder_target" name="ntn_folder_target" class="regular-text" style="width: 100%;" placeholder="/nuova-cartella/" required>
			</div>
			<div>
				<input type="submit" class="button button-primary" value="Aggiungi regola">
			</div>
		</form>

		<h3 style="margin-top: 30px;">Regole (<?php echo count( $rules ); ?>)</h3>
		<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
			<thead>
				<tr>
					<th>Cartella di origine</th>
					<th>Cartella di destinazione</th>
					<th style="width: 80px;">Visite</th>
					<th style="width: 150px;">Ultimo utilizzo</th>
					<th style="width: 90px;">Stato</th>
					<th style="width: 190px;">Azioni</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rules ) ) : ?>
				<tr>
					<td colspan="6">Nessuna regola per cartella al momento.</td>
				</tr>
				<?php else : ?>
					<?php
					foreach ( $rules as $hash => $rule ) :
						$enabled = ! empty( $rule['enabled'] );
						$hits    = (int) ( $rule['hits'] ?? 0 ) + (int) get_option( 'ntn_folder_redir_batch_' . $hash, 0 );
						?>
					<tr>
						<td><strong><?php echo esc_html( (string) ( $rule['source'] ?? '' ) ); ?></strong></td>
						<td><?php echo esc_html( (string) ( $rule['target'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $hits ) ); ?></td>
						<td><?php echo ! empty( $rule['last_accessed'] ) ? esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( (string) $rule['last_accessed'] ) ) ) : 'Mai'; ?></td>
						<td><?php echo $enabled ? '<span style="color: #00a32a; font-weight: 600;">Attiva</span>' : '<span style="color: #646970;">Spenta</span>'; ?></td>
						<td>
							<?php ntn_journal_folder_rule_button( 'toggle', (string) $hash, $enabled ? 'Disattiva' : 'Attiva' ); ?>
							<?php ntn_journal_folder_rule_button( 'delete', (string) $hash, 'Elimina', 'Sei sicuro di voler eliminare questa regola?' ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%; margin-top: 20px;">
		<h2>Indirizzi che non esistono</h2>
		<form method="post" action="">
			<?php wp_nonce_field( 'ntn_folder_rule', 'ntn_folder_rule_nonce' ); ?>
			<input type="hidden" name="ntn_folder_action" value="options">
			<p>
				<label>
					<input type="checkbox" name="ntn_redirect_paged_fallback" value="1" <?php checked( 'yes', get_option( 'ntn_redirect_paged_fallback', 'yes' ) ); ?>>
					<strong>Pagine numerate oltre la fine:</strong> se un archivio esiste ma la sua pagina numerata no (es. <code>/sezione/notizie/page/999/</code>), porta alla prima pagina dell'archivio invece che all'errore.
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="ntn_redirect_no_guess" value="1" <?php checked( 'yes', get_option( 'ntn_redirect_no_guess', 'no' ) ); ?>>
					<strong>Non tirare a indovinare:</strong> quando un indirizzo non esiste, WordPress di solito porta il visitatore sulla pagina dal nome più simile (a volte giusta, spesso no). Con questa casella spuntata mostra invece la pagina "non trovato" (errore 404).
				</label>
			</p>
			<input type="submit" class="button button-primary" value="Salva opzioni">
		</form>
	</div>
	<?php
}
add_action( 'ntn_journal_render_redirects_tab', 'ntn_journal_render_folder_redirects_ui', 20 );
