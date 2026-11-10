<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * Fase 9 del restyling (v3.13.0): testata sulla fascia e testo come la pagina articolo,
 * senza i dati dell'articolo (autore, data, minuti), che in una pagina statica non servono.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package NTN_Journal
 */

get_header();
?>

<main id="primary" class="site-main ntn-main flex-grow">
	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'ntn-article ntn-page' ); ?>>

			<!-- ==================== TESTATA DELLA PAGINA ==================== -->
			<header class="ntn-band ntn-art-band">
				<div class="ntn-wrap">

					<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
						<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
					<?php endif; ?>

					<div class="ntn-art-head">
						<h1 class="ntn-art-title"><?php the_title(); ?></h1>
						<?php if ( has_excerpt() ) : ?>
							<p class="ntn-art-sub"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
						<?php endif; ?>
					</div>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="ntn-art-hero">
							<div class="ntn-art-hero-img">
								<?php
								the_post_thumbnail(
									'large',
									array(
										'class'         => 'w-full h-full object-cover',
										'fetchpriority' => 'high',
										'loading'       => 'eager',
									)
								);
								?>
							</div>
						</figure>
					<?php endif; ?>

				</div>
			</header>

			<!-- ==================== TESTO DELLA PAGINA ==================== -->
			<div class="ntn-wrap ntn-art-layout">
				<div class="ntn-art-main">

					<div class="dynamic-content-body ntn-prose prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 font-sans leading-relaxed space-y-6">
						<?php
						the_content();

						wp_link_pages(
							array(
								'before' => '<div class="page-links">' . esc_html__( 'Pagine:', 'ntn-journal' ),
								'after'  => '</div>',
							)
						);
						?>
					</div>

					<?php
					// Mostra i commenti (form + lista) se sono aperti o se ce ne sono già.
					// v3.17.1: rispetta anche "Slot Commenti" di NTN Journal Funzioni, come gli articoli.
					if ( get_option( 'ntn_journal_enable_comments', 'yes' ) === 'yes' && ( comments_open() || get_comments_number() ) ) :
						?>
						<section class="ntn-comments" aria-label="<?php esc_attr_e( 'Commenti', 'ntn-journal' ); ?>">
							<div class="ntn-sec-head mb-6">
								<h2 class="ntn-sec-title">// Commenti (<?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?>)</h2>
							</div>
							<?php comments_template(); ?>
						</section>
						<?php
					endif;
					?>

				</div>
			</div>

		</article>

	<?php endwhile; ?>
</main>

<?php
get_footer();
