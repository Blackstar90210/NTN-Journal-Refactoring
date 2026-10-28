<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The template for displaying 404 pages (not found)
 *
 * Fase 9 del restyling (v3.13.0): fascia a matrice di punti con un grande "404" in Mono,
 * titolo, testo e due pulsanti squadrati (Home e pagina precedente).
 *
 * @package NTN_Journal
 */

get_header();
?>

<main id="primary" class="site-main ntn-main flex-grow flex flex-col">

	<section class="ntn-band ntn-404" aria-labelledby="ntn-404-title">
		<div class="ntn-wrap ntn-404-in">

			<p class="ntn-404-code" aria-hidden="true">404</p>

			<div class="ntn-404-text">
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip ntn-chip--red">Errore 404</span>
				</div>
				<h1 id="ntn-404-title" class="ntn-arch-title">Pagina non trovata</h1>
				<p class="ntn-arch-desc">La pagina che stai cercando non esiste, è stata spostata oppure l'indirizzo è sbagliato.</p>

				<div class="ntn-404-actions">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ntn-btn ntn-btn--solid">&larr; Torna alla Home</a>
					<button type="button" onclick="window.history.back();" class="ntn-btn">Pagina precedente</button>
				</div>

				<p class="ntn-404-status"><span>Status: 404</span><span>Err:// not found</span></p>
			</div>

		</div>
	</section>

</main>

<?php
get_footer();
