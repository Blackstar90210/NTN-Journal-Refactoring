<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * Fase 2 del restyling (v3.5.0): barra di sistema sulla fascia di apertura e testata squadrata.
 * v3.12.0: testata su una riga come nel mockup (logo, menu senza "//", tendina Piattaforme,
 * lente e chiaro/scuro). La ricerca si apre dalla lente; da telefono i menu stanno in un pannello.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package NTN_Journal
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">



	<script>
		// Immediate check for saved theme to prevent FOUC (Flash of Unstyled Content)
		(function() {
			var savedTheme = localStorage.getItem('ntn_blog_theme');
			if (savedTheme === 'light') {
				document.documentElement.classList.remove('dark');
			} else if (savedTheme === 'dark') {
				document.documentElement.classList.add('dark');
			} else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				document.documentElement.classList.add('dark');
			} else {
				document.documentElement.classList.remove('dark');
			}
		})();
	</script>
	<?php wp_head(); ?>

</head>

<body <?php body_class( 'bg-white text-zinc-900 dark:bg-black dark:text-zinc-300 selection:bg-[#401212] selection:text-zinc-100 font-sans transition-colors duration-300' ); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site min-h-screen flex flex-col justify-between">
	<a class="skip-link screen-reader-text sr-only" href="#primary"><?php esc_html_e( 'Skip to content', 'ntn-journal' ); ?></a>

	<header class="ntn-header">

		<!-- Barra di sistema: testo personalizzato e orologio, sulla fascia che segue il tema -->
		<div class="ntn-topbar">
			<div class="ntn-wrap ntn-topbar-in">
				<span class="ntn-topbar-text"><?php echo esc_html( get_theme_mod( 'ntn_journal_top_banner_text', '// NOME SITO V1.00' ) ); ?></span>
				<span id="wp-header-clock" class="ntn-topbar-clock" data-date="<?php echo esc_attr( function_exists('ntn_journal_get_italian_date') ? ntn_journal_get_italian_date() : date('d M Y') ); ?>">--:--:-- | <?php echo esc_html( function_exists('ntn_journal_get_italian_date') ? ntn_journal_get_italian_date() : date('d M Y') ); ?></span>
			</div>
		</div>

		<?php do_action( 'ntn_journal_after_top_banner' ); ?>

		<!-- Testata su una riga: logo, menu, lente, chiaro/scuro (e pulsante menu da telefono) -->
		<div class="ntn-wrap ntn-header-main">

			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ntn-logo group">
				<span class="ntn-logo-dot" aria-hidden="true"></span>
				<?php
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					$logo_image = wp_get_attachment_image_src( $custom_logo_id, 'full' );
					if ( $logo_image ) {
						// Immagine nascosta per SEO
						$w = ! empty( $logo_image[1] ) ? intval( $logo_image[1] ) : 180;
						$h = ! empty( $logo_image[2] ) ? intval( $logo_image[2] ) : 40;
						echo '<img src="' . esc_url( $logo_image[0] ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="' . esc_attr( $w ) . '" height="' . esc_attr( $h ) . '" class="sr-only">';
					}
				}
				?>
				<span class="flex flex-col justify-center min-w-0">
					<?php
					$custom_logo_image = get_theme_mod( 'ntn_journal_main_logo_image' );
					$show_site_title   = get_theme_mod( 'ntn_journal_show_site_title', true );
					$show_site_tagline = get_theme_mod( 'ntn_journal_show_site_tagline', true );
					$title_tag         = ( is_front_page() || is_home() ) ? 'h1' : 'span';

					if ( ! empty( $custom_logo_image ) ) :
						if ( $show_site_title ) :
							?>
							<<?php echo esc_html( $title_tag ); ?> class="m-0 p-0 flex items-center h-8 sm:h-10">
								<img src="<?php echo esc_url( $custom_logo_image ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="180" height="40" class="h-full w-auto object-contain">
							</<?php echo esc_html( $title_tag ); ?>>
							<?php
						endif;
					else :
						if ( $show_site_title ) :
							?>
							<<?php echo esc_html( $title_tag ); ?> class="ntn-logo-text">
								<?php
								$ntn_logo = get_theme_mod( 'ntn_journal_main_logo_text', 'NOME-SITO' );
								if ( mb_strlen( $ntn_logo ) > 0 ) {
									$ntn_logo_first = mb_substr( $ntn_logo, 0, 1 );
									$ntn_logo_rest  = mb_substr( $ntn_logo, 1 );
									echo '<span class="text-red-600">' . esc_html( $ntn_logo_first ) . '</span>' . esc_html( $ntn_logo_rest );
								}
								?>
							</<?php echo esc_html( $title_tag ); ?>>
							<?php
						endif;
					endif;

					if ( $show_site_tagline ) :
						?>
						<span class="ntn-logo-tagline"><?php bloginfo( 'description' ); ?></span>
					<?php endif; ?>
				</span>
			</a>

			<!-- Menu: voci "Articoli" e voci del menu Primary -->
			<nav class="ntn-nav" aria-label="<?php esc_attr_e( 'Menu principale', 'ntn-journal' ); ?>">
				<?php if ( function_exists( 'ntn_journal_header_menus' ) ) { ntn_journal_header_menus( 'desktop' ); } ?>
			</nav>

			<div class="ntn-header-tools">
				<?php if ( function_exists( 'ntn_journal_header_platform_drop' ) ) { ntn_journal_header_platform_drop(); } ?>
				<button type="button" class="ntn-sq ntn-search-toggle" aria-controls="ntn-search-panel" aria-expanded="<?php echo get_search_query() ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Apri la ricerca', 'ntn-journal' ); ?>">
					<svg class="ntn-ic-lens" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
					<svg class="ntn-ic-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
				</button>

				<div class="theme-toggle-container">
					<button type="button" class="ntn-sq theme-toggle-btn" aria-label="<?php esc_attr_e( 'Cambia tema chiaro o scuro', 'ntn-journal' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
							<circle cx="12" cy="12" r="8"></circle>
							<path d="M12 4a8 8 0 0 0 0 16z" fill="currentColor"></path>
						</svg>
						<span class="sr-only">THEME: <span class="theme-text">DARK</span></span>
					</button>
				</div>

				<button type="button" class="ntn-sq ntn-menu-toggle" aria-controls="ntn-mobile-nav" aria-expanded="false" aria-label="<?php esc_attr_e( 'Apri il menu', 'ntn-journal' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
				</button>
			</div>

		</div>

		<!-- Ricerca (v3.19.0): scende dalla lente con una linea rossa; aperta sulla pagina dei risultati.
		     I suggerimenti (articoli e giochi) li disegna assets/js/live-search.js. -->
		<div id="ntn-search-panel" class="ntn-search-panel<?php echo get_search_query() ? ' is-open is-done' : ''; ?>"<?php echo get_search_query() ? '' : ' hidden'; ?>>
			<span class="ntn-search-line" aria-hidden="true"></span>
			<div class="ntn-search-clip">
				<div class="ntn-wrap ntn-search-panel-in">
					<div class="ntn-search-box">
						<form role="search" method="get" class="ntn-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<label class="ntn-search-label" for="search-input-wp">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
								<span>// Cerca</span>
							</label>
							<input
								type="search"
								id="search-input-wp"
								name="s"
								value="<?php echo esc_attr( get_search_query() ); ?>"
								placeholder="Articoli, giochi, studi…"
								aria-label="Cerca nel sito"
								autocomplete="off"
								class="ntn-search-input"
							/>
							<button type="button" class="ntn-search-clear"<?php echo get_search_query() ? '' : ' hidden'; ?>>Cancella</button>
							<button type="submit" class="ntn-search-go">Cerca</button>
						</form>
					</div>
					<div class="ntn-search-hint" aria-hidden="true"><span><kbd>↑</kbd> <kbd>↓</kbd> scegli</span><span><kbd>Invio</kbd> apri</span><span><kbd>Esc</kbd> chiudi</span></div>
				</div>
			</div>
		</div>

		<!-- Menu da telefono e tablet -->
		<div id="ntn-mobile-nav" class="ntn-mobile-nav" hidden>
			<div class="ntn-wrap ntn-mobile-nav-in">
				<?php if ( function_exists( 'ntn_journal_header_menus' ) ) { ntn_journal_header_menus( 'mobile' ); } ?>
			</div>
		</div>

	</header>

	<!-- Theme switching Script -->
	<!-- Enqueued via wp_enqueue_script -->

