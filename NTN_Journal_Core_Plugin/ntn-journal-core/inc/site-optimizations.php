<?php
/**
 * NTN Journal Core - Ottimizzazioni e protezioni del sito (v4.20.0)
 *
 * Prima stavano nel tema (inc/performance.php). Non sono presentazione: lavorano su caricamenti di immagini,
 * intestazioni, emoji, Gravatar e indirizzi degli autori, e devono funzionare anche se si cambia tema.
 * Dalla 4.26.0 ci sono anche XML-RPC disattivato e i campi meta delle recensioni (prima nel tema).
 * Nel tema restano solo le parti che riguardano le sue pagine (preload dell'immagine principale e
 * DNS prefetch / preconnect).
 *
 * Il plugin viene caricato prima del tema. I temi fino alla 4.19.x hanno ancora queste ottimizzazioni
 * dentro performance.php: con quei temi il plugin non le attiva per non farle due volte
 * (ntn_core_owns_site_optimizations()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Le ottimizzazioni sono compito del plugin?
 *
 * @return bool true con un altro tema o con il tema NTN dalla 4.20.0; false con le versioni precedenti del tema NTN.
 */
function ntn_core_owns_site_optimizations() {
	if ( 'ntn-journal' !== get_template() ) {
		return true;
	}
	return version_compare( (string) wp_get_theme( 'ntn-journal' )->get( 'Version' ), '4.20.0', '>=' );
}

/**
 * Campi meta "protetti" che WordPress deve lasciar leggere (dalla 4.26.0, prima nel tema).
 *
 * I campi che iniziano con "_" sono nascosti all'editor e all'API. Questi servono alle recensioni
 * (voto, pro, contro, verdetto, prodotto recensito) e all'immagine in evidenza: restano leggibili come prima,
 * tutti gli altri campi protetti restano protetti.
 *
 * @param bool   $protected Se il campo è protetto.
 * @param string $meta_key  Nome del campo.
 * @return bool
 */
function ntn_core_allow_review_meta( $protected, $meta_key ) {
	$allowed = array( '_thumbnail_id', 'rating', 'pros', 'cons', 'verdict', 'reviewed_item' );
	return in_array( $meta_key, $allowed, true ) ? false : $protected;
}

/**
 * Disabilita i Gravatar Hovercards (pop-up sui profili utente).
 * Metodo 1: toglie la classe 'avatar' dalle immagini (il bersaglio degli script).
 */
function ntn_core_gravatar_avatar_classes( $classes ) {
	foreach ( $classes as $key => $class ) {
		if ( 'avatar' === $class || 'photo' === $class || 0 === strpos( $class, 'avatar-' ) ) {
			unset( $classes[ $key ] );
		}
	}
	return $classes;
}

/**
 * Metodo 2: toglie lo script ufficiale di Jetpack.
 */
function ntn_core_gravatar_dequeue_script() {
	wp_dequeue_script( 'gprofiles' );
	wp_deregister_script( 'gprofiles' );
}

/**
 * Metodo 3: nasconde il riquadro con il CSS, se lo script viene caricato comunque.
 */
function ntn_core_gravatar_hide_css() {
	if ( class_exists( 'Jetpack' ) ) {
		echo '<style>
			/* Nasconde i popup di Gravatar Hovercards (Jetpack o altri plugin) */
			div.gprofile, 
			div.gravatar-hovercard, 
			#profilecards,
			.gravatar-profile-card {
				display: none !important;
				opacity: 0 !important;
				visibility: hidden !important;
				pointer-events: none !important;
				z-index: -9999 !important;
			}
		</style>';
	}
}

/**
 * Ottimizza la compressione delle immagini caricate su WordPress.
 * Di default WP comprime al 82%. Abbassiamo al 75% per ridurre drasticamente
 * il peso (ideale per connessioni lente e vecchi PC) senza perdite visibili
 * a occhio nudo nel contesto del nostro design brutalista.
 */
function ntn_core_jpeg_quality() {
	if ( get_theme_mod( 'ntn_journal_enable_compression', true ) ) {
		return 75;
	}
	return 82; // Qualità standard di WordPress
}


/**
 * ==============================================================================
 * DISABLE EMOJIS (Performance Optimization)
 * ==============================================================================
 */
function ntn_core_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

/**
 * ==============================================================================
 * CLEANUP HEADER & SECURITY HARDENING
 * ==============================================================================
 */
function ntn_core_header_cleanup() {
	// Rimuove la versione di WordPress dal codice sorgente (meta generator)
	remove_action( 'wp_head', 'wp_generator' );

	// Rimuove Windows Live Writer manifest
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Rimuove RSD link
	remove_action( 'wp_head', 'rsd_link' );

	// Rimuove lo shortlink rel="shortlink"
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );

	// Rimuove l'URL dell'API REST dall'header (mantiene la funzionalità ma pulisce l'header)
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );

	// Rimuove oEmbed discovery links
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
}

/**
 * Login degli utenti da non mostrare ai visitatori (v4.25.2): pagina autore, elenco utenti, sitemap.
 *
 * @return string[]
 */
function ntn_core_hidden_author_logins() {
	$logins = apply_filters( 'ntn_core_hidden_author_logins', array( 'admin' ) );
	return array_values( array_filter( array_map( 'strval', (array) $logins ) ) );
}

/**
 * La protezione degli autori vale per questa richiesta? (v4.25.2)
 * Solo per i visitatori: chi ha fatto l'accesso al sito e il pannello di amministrazione non cambiano.
 *
 * @return bool
 */
function ntn_core_author_protection_applies() {
	return ! is_admin() && ! is_user_logged_in();
}

/**
 * Risponde "pagina non trovata" (404) e lascia che WordPress mostri la pagina 404 del tema.
 */
function ntn_core_author_not_found() {
	global $wp_query;
	if ( isset( $wp_query ) && is_object( $wp_query ) && method_exists( $wp_query, 'set_404' ) ) {
		$wp_query->set_404();
	}
	remove_action( 'template_redirect', 'redirect_canonical' ); // niente rimando a /author/nome/
	status_header( 404 );
	nocache_headers();
}

/**
 * Blocca davvero l'enumerazione degli autori (v4.25.2).
 *
 * Prima /?author=1 rimandava a /author/nome-utente/ e quindi mostrava il nome: ora, per i visitatori,
 * /?author=N e /?author_name=... danno 404. Anche la pagina autore degli utenti nascosti (login "admin")
 * dà 404. Le altre pagine autore (/author/nome/) restano come prima.
 */
function ntn_core_block_author_enumeration() {
	if ( ! ntn_core_author_protection_applies() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sola lettura dell'indirizzo.
	if ( ! empty( $_GET['author'] ) || ! empty( $_GET['author_name'] ) ) {
		ntn_core_author_not_found();
		return;
	}

	if ( is_author() ) {
		$user = get_queried_object();
		if ( $user instanceof WP_User && in_array( $user->user_login, ntn_core_hidden_author_logins(), true ) ) {
			ntn_core_author_not_found();
		}
	}
}

/**
 * Elenco utenti pubblico dell'API (/wp-json/wp/v2/users): per i visitatori non esiste più (v4.25.2).
 * Il sito non lo usa; l'editor e il pannello, con l'accesso fatto, continuano ad averlo.
 *
 * @param array $endpoints Rotte dell'API.
 * @return array
 */
function ntn_core_hide_rest_users( $endpoints ) {
	if ( is_user_logged_in() || ! is_array( $endpoints ) ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( (string) $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}

/**
 * Toglie gli utenti nascosti (login "admin") dalla sitemap degli utenti di WordPress (v4.25.2).
 *
 * @param array $args Argomenti di WP_User_Query.
 * @return array
 */
function ntn_core_sitemap_hide_authors( $args ) {
	$args                  = is_array( $args ) ? $args : array();
	$current               = isset( $args['login__not_in'] ) ? (array) $args['login__not_in'] : array();
	$args['login__not_in'] = array_values( array_unique( array_merge( $current, ntn_core_hidden_author_logins() ) ) );
	return $args;
}


/**
 * Verifica se un file PNG è davvero trasparente.
 * L'intestazione dice solo se il file PUÒ avere trasparenza: molti programmi salvano in RGBA
 * anche immagini piene (screenshot, copertine). In quel caso si controllano i pixel (v3.22.5).
 */
function ntn_core_png_has_transparency( $file_path ) {
	if ( empty( $file_path ) || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
		return false;
	}
	$contents = @file_get_contents( $file_path, false, null, 0, 1024 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- file immagine rovinato o mancante: l'esito è già controllato nel codice, l'avviso PHP non serve.
	if ( ! $contents || strlen( $contents ) < 26 ) {
		return false;
	}
	// PNG Header: 8 bytes, poi chunk IHDR (byte 12-29). Il byte 25 è il Color Type
	if ( substr( $contents, 1, 3 ) !== 'PNG' ) {
		return false;
	}
	$color_type = ord( $contents[25] );
	// Color Type 4 (Grayscale + Alpha) o 6 (RGBA), oppure chunk di trasparenza tRNS: può essere trasparente
	if ( 4 !== $color_type && 6 !== $color_type && strpos( $contents, 'tRNS' ) === false ) {
		return false;
	}

	// Il filtro dei formati chiama la funzione per ogni miniatura: il risultato si calcola una volta sola
	static $cache = array();
	$key          = $file_path . '|' . (int) @filesize( $file_path ) . '|' . (int) @filemtime( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- file immagine rovinato o mancante: l'esito è già controllato nel codice, l'avviso PHP non serve.
	if ( ! isset( $cache[ $key ] ) ) {
		$opaque        = ntn_core_png_is_fully_opaque( $file_path );
		$cache[ $key ] = ( true !== $opaque ); // Se i pixel non si possono leggere, il PNG resta protetto
	}
	return $cache[ $key ];
}

/**
 * Controlla i pixel di un PNG con canale alfa: true se sono tutti opachi, false se almeno uno
 * è trasparente, null se nessuna libreria grafica può leggerli.
 * L'immagine viene ridotta a 256 px (la media conserva le zone trasparenti) e si controllano
 * anche i quattro angoli a piena risoluzione, dove di solito sta lo sfondo trasparente dei loghi.
 */
function ntn_core_png_is_fully_opaque( $file_path ) {
	$max = 256;

	if ( function_exists( 'imagecreatefrompng' ) ) {
		$src = @imagecreatefrompng( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- file immagine rovinato o mancante: l'esito è già controllato nel codice, l'avviso PHP non serve.
		if ( ! $src ) {
			return null;
		}
		$w = imagesx( $src );
		$h = imagesy( $src );
		foreach ( array( array( 0, 0 ), array( $w - 1, 0 ), array( 0, $h - 1 ), array( $w - 1, $h - 1 ) ) as $corner ) {
			$rgba = imagecolorsforindex( $src, imagecolorat( $src, $corner[0], $corner[1] ) );
			if ( $rgba['alpha'] > 0 ) {
				unset( $src );
				return false;
			}
		}
		$scale = min( 1, $max / max( $w, $h ) );
		$tw    = max( 1, (int) round( $w * $scale ) );
		$th    = max( 1, (int) round( $h * $scale ) );
		$small = imagecreatetruecolor( $tw, $th );
		imagealphablending( $small, false );
		imagesavealpha( $small, true );
		imagefill( $small, 0, 0, imagecolorallocatealpha( $small, 0, 0, 0, 127 ) );
		imagecopyresampled( $small, $src, 0, 0, 0, 0, $tw, $th, $w, $h );
		unset( $src );
		for ( $y = 0; $y < $th; $y++ ) {
			for ( $x = 0; $x < $tw; $x++ ) {
				// Alfa di GD: 0 = opaco, 127 = trasparente
				if ( ( ( imagecolorat( $small, $x, $y ) >> 24 ) & 0x7F ) > 0 ) {
					unset( $small );
					return false;
				}
			}
		}
		unset( $small );
		return true;
	}

	if ( class_exists( 'Imagick' ) ) {
		try {
			$im = new Imagick( $file_path );
			$w  = $im->getImageWidth();
			$h  = $im->getImageHeight();
			foreach ( array( array( 0, 0 ), array( $w - 1, 0 ), array( 0, $h - 1 ), array( $w - 1, $h - 1 ) ) as $corner ) {
				$alpha = $im->exportImagePixels( $corner[0], $corner[1], 1, 1, 'A', Imagick::PIXEL_CHAR );
				if ( isset( $alpha[0] ) && $alpha[0] < 255 ) {
					$im->clear();
					return false;
				}
			}
			$im->thumbnailImage( min( $w, $max ), min( $h, $max ), true );
			// Alfa di Imagick letto come byte: 255 = opaco
			$alphas = $im->exportImagePixels( 0, 0, $im->getImageWidth(), $im->getImageHeight(), 'A', Imagick::PIXEL_CHAR );
			$im->clear();
			return ! empty( $alphas ) && min( $alphas ) >= 255;
		} catch ( Exception $e ) {
			return null;
		}
	}

	return null;
}

/**
 * Converte automaticamente le miniature PNG in JPEG durante il caricamento
 * per sfruttare l'algoritmo di compressione e garantire compatibilità social (Open Graph),
 * a meno che l'immagine PNG non contenga trasparenza/canale alfa (es. loghi, badge, icone).
 * I file pregressi rimangono invariati.
 */
function ntn_core_convert_png_to_jpeg_thumbnails( $formats, $filename = '', $mime_type = '' ) {
	// v4.20.1: per le miniature WordPress chiama il filtro senza nome del file. In quel caso si usa
	// l'originale dell'allegato in lavorazione, così le miniature di un PNG trasparente restano PNG.
	$source = ! empty( $filename ) ? (string) $filename : ( 'image/png' === $mime_type ? ntn_core_image_source_in_progress() : '' );
	if ( '' !== $source && ntn_core_png_has_transparency( $source ) ) {
		return $formats; // Preserva il formato PNG trasparente
	}
	$formats['image/png'] = 'image/jpeg';
	return $formats;
}

/**
 * File originale dell'allegato di cui WordPress sta creando le miniature (v4.20.1).
 *
 * @param string|null $set Percorso da ricordare, '' per azzerare, null per leggere.
 * @return string Percorso ricordato ('' se nessuno).
 */
function ntn_core_image_source_in_progress( $set = null ) {
	static $current = '';
	if ( null !== $set ) {
		$current = (string) $set;
	}
	return $current;
}

/**
 * Prima delle miniature: ricorda il file originale dell'allegato.
 * Agganciata a intermediate_image_sizes_advanced (caricamento e rigenerazione completa, anche da plugin)
 * e a wp_get_missing_image_subsizes (wp_update_image_subsizes(), che crea solo le misure mancanti).
 */
function ntn_core_remember_image_source( $new_sizes, $image_meta = array(), $attachment_id = 0 ) {
	$file = $attachment_id ? get_attached_file( (int) $attachment_id ) : '';
	ntn_core_image_source_in_progress( is_string( $file ) ? $file : '' );
	return $new_sizes;
}

/**
 * Dopo tutte le miniature: dimentica il file, così non vale per l'immagine successiva.
 * Si usa wp_generate_attachment_metadata e non wp_update_attachment_metadata, che WordPress
 * chiama dopo OGNI miniatura (_wp_make_subsizes): lì il file verrebbe dimenticato troppo presto.
 */
function ntn_core_forget_image_source( $metadata ) {
	ntn_core_image_source_in_progress( '' );
	return $metadata;
}

/**
 * Ottimizza l'upload originale alla radice:
 * 1. Converte automaticamente i PNG non trasparenti in JPEG.
 * 2. Rimpicciolisce qualsiasi immagine (JPEG o PNG) se supera i 1920px (1080p).
 * 3. Sovrascrive/elimina l'originale gigante, garantendo VERO risparmio su disco.
 */
function ntn_core_optimize_upload_size_and_format( $upload ) {
	if ( isset( $upload['error'] ) && $upload['error'] !== false ) {
		return $upload;
	}

	$type = $upload['type'];

	if ( $type === 'image/jpeg' || $type === 'image/png' ) {
		$file_path = $upload['file'];

		// Se è un PNG con canale di trasparenza, evita la distruzione del canale alfa
		if ( $type === 'image/png' && ntn_core_png_has_transparency( $file_path ) ) {
			$image_editor = wp_get_image_editor( $file_path );
			if ( ! is_wp_error( $image_editor ) ) {
				$size = $image_editor->get_size();
				if ( ! is_wp_error( $size ) && ( $size['width'] > 1920 || $size['height'] > 1920 ) ) {
					$image_editor->resize( 1920, 1920, false );
					$image_editor->save( $file_path, 'image/png' );
				}
			}
			return $upload;
		}

		$image_editor = wp_get_image_editor( $file_path );

		if ( ! is_wp_error( $image_editor ) ) {
			// Impostiamo SEMPRE true per forzare la compressione su tutti i JPEG e PNG
			$modified = true;

			$size = $image_editor->get_size();
			if ( ! is_wp_error( $size ) && ( $size['width'] > 1920 || $size['height'] > 1920 ) ) {
				$image_editor->resize( 1920, 1920, false );
			}

			if ( $modified ) {
				// Assicuriamo l'estensione .jpg minuscola
				$new_file_path = preg_replace( '/\.(png|jpeg|jpg)$/i', '.jpg', $file_path );

				// Salvataggio diretto sovrascrivendo il file o creandolo nuovo (sicuro in WP_Image_Editor)
				$saved_image = $image_editor->save( $new_file_path, 'image/jpeg' );

				if ( ! is_wp_error( $saved_image ) ) {
					// Rilascia la memoria dell'editor
					unset( $image_editor );

					// Rimuove il file originale solo se il path differisce in modo sostanziale (es. da .png a .jpg).
					// L'uso di strtolower previene la cancellazione accidentale su Windows per i file .JPG
					if ( strtolower( $new_file_path ) !== strtolower( $file_path ) ) {
						@unlink( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- file immagine rovinato o mancante: l'esito è già controllato nel codice, l'avviso PHP non serve.
					}

					// Aggiorna sempre i riferimenti se c'è stata una modifica testuale nel path (es. .JPG -> .jpg)
					if ( $new_file_path !== $file_path ) {
						$upload['file'] = $new_file_path;
						$upload['url']  = preg_replace( '/\.(png|jpeg|jpg)$/i', '.jpg', $upload['url'] );
					}

					// Assicura il corretto mime type
					$upload['type'] = 'image/jpeg';
				}
			}
		}
	}

	return $upload;
}

// Disabilita lo scaling nativo di WordPress (> 2560px) poiché gestiamo già tutto a 1920px.
// Questo previene definitivamente la creazione di file -scaled orfani in caso di edge-cases.

/**
 * Collega le ottimizzazioni ai filtri di WordPress, solo se non le fa già il tema (vedi sopra).
 */
function ntn_core_register_site_optimizations() {
	if ( ! ntn_core_owns_site_optimizations() ) {
		return;
	}
	add_filter( 'xmlrpc_enabled', '__return_false' ); // v4.26.0: prima nel tema (inc/setup.php)
	add_filter( 'is_protected_meta', 'ntn_core_allow_review_meta', 10, 2 ); // v4.26.0: prima nel tema (functions.php)
	add_filter( 'get_avatar_class', 'ntn_core_gravatar_avatar_classes' );
	add_filter( 'jetpack_is_hovercards_enabled', '__return_false', 99 );
	add_action( 'wp_enqueue_scripts', 'ntn_core_gravatar_dequeue_script', 999 );
	add_action( 'wp_head', 'ntn_core_gravatar_hide_css' );
	add_filter( 'jpeg_quality', 'ntn_core_jpeg_quality' );
	add_filter( 'wp_editor_set_quality', 'ntn_core_jpeg_quality' );
	add_action( 'init', 'ntn_core_disable_emojis' );
	add_action( 'init', 'ntn_core_header_cleanup' );
	add_action( 'template_redirect', 'ntn_core_block_author_enumeration', 1 ); // v4.25.2: prima del rimando automatico di WordPress
	add_filter( 'rest_endpoints', 'ntn_core_hide_rest_users' );
	add_filter( 'wp_sitemaps_users_query_args', 'ntn_core_sitemap_hide_authors' );
	add_filter( 'image_editor_output_format', 'ntn_core_convert_png_to_jpeg_thumbnails', 10, 3 );
	add_filter( 'intermediate_image_sizes_advanced', 'ntn_core_remember_image_source', 10, 3 );
	add_filter( 'wp_get_missing_image_subsizes', 'ntn_core_remember_image_source', 10, 3 );
	add_filter( 'wp_generate_attachment_metadata', 'ntn_core_forget_image_source', 999, 1 );
	add_filter( 'wp_handle_upload', 'ntn_core_optimize_upload_size_and_format' );
	add_filter( 'wp_handle_sideload', 'ntn_core_optimize_upload_size_and_format' );
	// Disabilita lo scaling nativo di WordPress (> 2560px) poiché gestiamo già tutto a 1920px.
	// Questo previene definitivamente la creazione di file -scaled orfani in caso di edge-cases.
	add_filter( 'big_image_size_threshold', '__return_false' );
}
ntn_core_register_site_optimizations();
