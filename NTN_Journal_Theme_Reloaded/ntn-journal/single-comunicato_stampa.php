<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The template for displaying all single Comunicati Stampa
 *
 * Fase 8 del restyling (v3.10.0): testata sulla fascia come la pagina articolo, scheda del
 * comunicato a celle e nota finale in riquadro. Il giallo resta come accento dei comunicati.
 *
 * @package NTN_Journal
 */

get_header();

// Estrai i metadati personalizzati
$publisher   = get_post_meta( get_the_ID(), 'ntn_press_publisher', true );
$developer   = get_post_meta( get_the_ID(), 'ntn_press_developer', true );
$agency      = get_post_meta( get_the_ID(), 'ntn_press_agency', true );
$source_link = get_post_meta( get_the_ID(), 'ntn_press_source_link', true );
$embargo     = get_post_meta( get_the_ID(), 'ntn_press_embargo', true );
$assets_link = get_post_meta( get_the_ID(), 'ntn_press_assets', true );
$disclaimer  = get_option( 'ntn_press_disclaimer', 'Nota: Questo è un comunicato stampa ufficiale. Il testo non è stato redatto dalla redazione di NTN Journal.' );

$publisher   = is_array( $publisher ) ? implode( ', ', array_filter( $publisher ) ) : ( is_scalar( $publisher ) ? trim( (string) $publisher ) : '' );
$developer   = is_array( $developer ) ? implode( ', ', array_filter( $developer ) ) : ( is_scalar( $developer ) ? trim( (string) $developer ) : '' );
$agency      = is_array( $agency ) ? implode( ', ', array_filter( $agency ) ) : ( is_scalar( $agency ) ? trim( (string) $agency ) : '' );
$source_link = is_array( $source_link ) ? reset( $source_link ) : ( is_scalar( $source_link ) ? trim( (string) $source_link ) : '' );
$embargo     = is_array( $embargo ) ? reset( $embargo ) : ( is_scalar( $embargo ) ? trim( (string) $embargo ) : '' );
$assets_link = is_array( $assets_link ) ? reset( $assets_link ) : ( is_scalar( $assets_link ) ? trim( (string) $assets_link ) : '' );

$archive_link_raw = get_post_type_archive_link( 'comunicato_stampa' );
$archive_link     = ( ! empty( $archive_link_raw ) && ! is_wp_error( $archive_link_raw ) && is_string( $archive_link_raw ) ) ? $archive_link_raw : home_url( '/comunicato-stampa/' );

$press_facts = array_filter(
	array(
		'Editore'    => $publisher,
		'Studio'     => $developer,
		'Agenzia PR' => $agency,
	)
);
?>

<main id="primary" class="site-main ntn-main flex-grow">
	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'ntn-article ntn-press' ); ?>>

			<!-- ==================== TESTATA DEL COMUNICATO ==================== -->
			<header class="ntn-band ntn-art-band">
				<div class="ntn-wrap">

					<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
						<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
					<?php endif; ?>

					<div class="ntn-art-head">
						<div class="flex flex-wrap gap-2">
							<a href="<?php echo esc_url( $archive_link ); ?>" class="ntn-chip ntn-chip--press ntn-chip-link" title="Vai all'archivio dei Comunicati Stampa">Comunicato stampa ufficiale</a>
						</div>

						<h1 class="ntn-art-title"><?php the_title(); ?></h1>

						<div class="ntn-meta ntn-art-meta">
							<span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y · H:i' ) ); ?></time></span>
						</div>

						<?php if ( ! empty( $press_facts ) || ! empty( $source_link ) || ! empty( $assets_link ) ) : ?>
							<dl class="ntn-press-facts">
								<?php foreach ( $press_facts as $fact_label => $fact_value ) : ?>
									<div>
										<dt><?php echo esc_html( $fact_label ); ?></dt>
										<dd><?php echo esc_html( $fact_value ); ?></dd>
									</div>
								<?php endforeach; ?>
								<?php if ( ! empty( $source_link ) ) : ?>
									<div>
										<dt>Fonte ufficiale</dt>
										<dd><a href="<?php echo esc_url( $source_link ); ?>" target="_blank" rel="nofollow noopener noreferrer">Vai alla fonte &nearr;</a></dd>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $assets_link ) ) : ?>
									<div>
										<dt>Press kit</dt>
										<dd><a href="<?php echo esc_url( $assets_link ); ?>" target="_blank" rel="nofollow noopener noreferrer">Scarica i materiali &nearr;</a></dd>
									</div>
								<?php endif; ?>
							</dl>
						<?php endif; ?>
					</div>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="ntn-art-hero">
							<div class="ntn-art-hero-img">
								<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover', 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?>
							</div>
						</figure>
					<?php endif; ?>

				</div>
			</header>

			<!-- ==================== TESTO DEL COMUNICATO ==================== -->
			<div class="ntn-wrap ntn-art-layout">
				<div class="ntn-art-main">

					<div class="dynamic-content-body ntn-prose prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 font-sans leading-relaxed space-y-6">
						<?php
						the_content();

						wp_link_pages(
							array(
								'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ntn-journal' ),
								'after'  => '</div>',
							)
						);
						?>
					</div>

					<!-- Nota automatica: il testo non è della redazione -->
					<aside class="ntn-press-note">
						<span>// Nota</span>
						<div><?php echo wp_kses_post( $disclaimer ); ?></div>
					</aside>

				</div>
			</div>

		</article>

	<?php endwhile; ?>
</main>

<?php
get_footer();
