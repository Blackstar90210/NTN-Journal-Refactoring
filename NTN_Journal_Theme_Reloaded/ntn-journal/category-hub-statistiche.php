<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Hub di categoria di tipo "Statistiche" (v4.3.0): la fascia con il nome della sezione e quattro numeri.
 * v4.6.0: sotto la fascia, tutti gli articoli (variante scelta dal CEO): l'ultimo in grande nella prima pagina,
 * poi le schede degli archivi del sito. Dalla pagina 2 in poi solo le schede.
 * v4.7.0: testata, contatori, spazi e schede con le misure del catalogo Videogiochi e della Hub del gioco.
 *
 * I dati arrivano dal plugin; se mancano, si torna all'archivio normale.
 *
 * @package NTN_Journal
 */

$hub   = function_exists( 'ntn_core_get_category_hub_data' ) ? ntn_core_get_category_hub_data() : null;
$stats = function_exists( 'ntn_core_get_category_hub_stats' ) ? ntn_core_get_category_hub_stats() : null;
if ( ! $hub || ! $stats ) {
	locate_template( 'archive.php', true, false );
	return;
}

get_header();

$stats_year   = (string) $stats['year'];
$stats_author = $stats['top_author'];
$stats_latest = (string) $stats['latest_date'];
$stats_ago    = ( '' !== $stats_latest && function_exists( 'ntn_core_category_hub_days_ago' ) ) ? ntn_core_category_hub_days_ago( $stats_latest ) : '';
?>

<main id="primary" class="site-main ntn-main">

	<!-- ==================== FASCIA DI APERTURA ==================== -->
	<header class="ntn-band ntn-chub-band">
		<?php if ( ! empty( $hub['illustration'] ) ) : ?>
			<canvas class="ntn-chub-art" data-ntn-dots="<?php echo esc_attr( implode( ',', $hub['illustration'] ) ); ?>" aria-hidden="true"></canvas>
		<?php endif; ?>
		<div class="ntn-wrap">

			<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
				<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
			<?php endif; ?>

			<div class="ntn-arch-head">
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip">Hub di sezione</span>
					<span class="ntn-chip ntn-chip--red">Statistiche</span>
				</div>

				<h1 class="ntn-arch-title"><?php echo esc_html( $hub['title'] ); ?></h1>

				<?php if ( '' !== $hub['intro'] ) : ?>
					<p class="ntn-arch-desc"><?php echo esc_html( $hub['intro'] ); ?></p>
				<?php endif; ?>

				<div class="ntn-gcounts">
					<span><b><?php echo esc_html( number_format_i18n( $stats['articles_total'] ) ); ?></b>Articoli</span>
					<span><b><?php echo esc_html( number_format_i18n( $stats['articles_year'] ) ); ?></b>Nel <?php echo esc_html( $stats_year ); ?></span>
					<?php if ( $stats_author ) : ?>
						<span><b><a href="<?php echo esc_url( $stats_author['url'] ); ?>"><?php echo esc_html( $stats_author['name'] ); ?></a></b>Autore dell'anno · <?php echo esc_html( number_format_i18n( $stats_author['count'] ) . ( 1 === $stats_author['count'] ? ' articolo' : ' articoli' ) ); ?></span>
					<?php else : ?>
						<span><b>—</b><?php echo esc_html( 0 === $stats['articles_year'] ? 'Nessun articolo nel ' . $stats_year : 'Autore non disponibile' ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $stats_latest ) : ?>
						<span><b><?php echo esc_html( $stats_latest ); ?></b>Ultimo articolo<?php echo '' !== $stats_ago ? esc_html( ' · ' . $stats_ago ) : ''; ?></span>
					<?php else : ?>
						<span><b>—</b>Nessun articolo</span>
					<?php endif; ?>
					<span><b><?php echo esc_html( number_format_i18n( $stats['games_total'] ) ); ?></b>Videogiochi su IGDB</span>
				</div>			</div>

		</div>
	</header>

	<div class="ntn-wrap ntn-hub-body">

		<!-- ==================== TUTTI GLI ARTICOLI ==================== -->
		<section aria-labelledby="ntn-h-cfeed">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-cfeed" class="ntn-sec-title">// Tutti gli articoli</h2>
				<span class="ntn-sec-aside">Dal più recente</span>
			</div>

			<?php if ( have_posts() ) : ?>
				<?php if ( (int) get_query_var( 'paged' ) < 2 ) : ?>
					<?php
					// Apertura: l'articolo più recente della query, solo nella prima pagina
					the_post();
					$stats_lead = ntn_front_card_data( get_the_ID(), 'large' );
					?>
					<div class="ntn-lead ntn-lead--solo ntn-stats-lead">
						<a class="ntn-lead-main group" href="<?php echo esc_url( $stats_lead['permalink'] ); ?>">
							<?php ntn_front_image( $stats_lead, 'aspect-video', true ); ?>
							<div class="ntn-stats-lead-text">
								<div class="ntn-meta">
									<?php if ( $stats_lead['author'] ) : ?>
										<span>by <b><?php echo esc_html( $stats_lead['author'] ); ?></b></span>
									<?php endif; ?>
									<span><?php echo esc_html( $stats_lead['date'] ); ?></span>
									<span><?php echo esc_html( $stats_lead['minutes'] ); ?> min</span>
								</div>
								<h3 class="ntn-lead-title"><?php echo esc_html( $stats_lead['title'] ); ?></h3>
								<?php if ( $stats_lead['excerpt'] ) : ?>
									<p class="ntn-lead-excerpt"><?php echo esc_html( $stats_lead['excerpt'] ); ?></p>
								<?php endif; ?>
							</div>
						</a>
					</div>
				<?php endif; ?>

				<?php if ( have_posts() ) : ?>
					<!-- Le stesse schede degli archivi del sito -->
					<div class="ntn-cells ntn-cells--3 ntn-stats-cards">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content' );
						endwhile;
						?>
					</div>
				<?php endif; ?>

				<div class="ntn-pager-wrap">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 2,
							'prev_text' => '&larr; Precedente',
							'next_text' => 'Successivo &rarr;',
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="ntn-empty">
					<span>Nessun articolo</span>
					<p>Non ci sono ancora articoli in questa sezione.</p>
				</div>
			<?php endif; ?>
		</section>

	</div>
</main>

<?php
get_footer();
