<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Hub di categoria di tipo "Statistiche" (v4.3.0): la fascia con il nome della sezione e quattro numeri.
 * v4.5.0: sotto la fascia, tutti gli articoli della sezione (richiesta del CEO), come nelle altre Hub.
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

			<div class="ntn-chub-head">
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip">Hub di sezione</span>
					<span class="ntn-chip ntn-chip--red">Statistiche</span>
				</div>

				<h1 class="ntn-arch-title"><?php echo esc_html( $hub['title'] ); ?></h1>

				<?php if ( '' !== $hub['intro'] ) : ?>
					<p class="ntn-arch-desc"><?php echo esc_html( $hub['intro'] ); ?></p>
				<?php endif; ?>

				<dl class="ntn-chub-stats is-numbers">
					<div>
						<dt class="ntn-stat-k">Articoli</dt>
						<dd class="ntn-stat-v"><?php echo esc_html( number_format_i18n( $stats['articles_total'] ) ); ?></dd>
						<dd class="ntn-stat-sub"><b><?php echo esc_html( number_format_i18n( $stats['articles_year'] ) ); ?></b> nel <?php echo esc_html( $stats_year ); ?></dd>
					</div>
					<div>
						<dt class="ntn-stat-k">Autore più prolifico</dt>
						<?php if ( $stats_author ) : ?>
							<dd class="ntn-stat-v"><a href="<?php echo esc_url( $stats_author['url'] ); ?>"><?php echo esc_html( $stats_author['name'] ); ?></a></dd>
							<dd class="ntn-stat-sub"><?php echo esc_html( number_format_i18n( $stats_author['count'] ) . ( 1 === $stats_author['count'] ? ' articolo' : ' articoli' ) . ' nel ' . $stats_year ); ?></dd>
						<?php else : ?>
							<dd class="ntn-stat-v is-muted">—</dd>
							<dd class="ntn-stat-sub"><?php echo esc_html( 0 === $stats['articles_year'] ? 'nessun articolo nel ' . $stats_year : 'autore non disponibile' ); ?></dd>
						<?php endif; ?>
					</div>
					<div>
						<dt class="ntn-stat-k">Ultimo articolo</dt>
						<?php if ( '' !== $stats_latest ) : ?>
							<dd class="ntn-stat-v is-small"><?php echo esc_html( $stats_latest ); ?></dd>
							<?php if ( '' !== $stats_ago ) : ?>
								<dd class="ntn-stat-sub"><?php echo esc_html( $stats_ago ); ?></dd>
							<?php endif; ?>
						<?php else : ?>
							<dd class="ntn-stat-v is-muted">—</dd>
							<dd class="ntn-stat-sub">nessun articolo</dd>
						<?php endif; ?>
					</div>
					<div>
						<dt class="ntn-stat-k">Videogiochi trattati</dt>
						<dd class="ntn-stat-v"><?php echo esc_html( number_format_i18n( $stats['games_total'] ) ); ?></dd>
						<dd class="ntn-stat-sub">collegati a IGDB</dd>
					</div>
				</dl>
			</div>

		</div>
	</header>

	<div class="ntn-wrap ntn-chub-body">

		<!-- ==================== TUTTI GLI ARTICOLI ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-cfeed">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-cfeed" class="ntn-sec-title">// Tutti gli articoli</h2>
				<span class="ntn-sec-aside">Dal più recente</span>
			</div>

			<?php get_template_part( 'template-parts/hub-article-feed', null, array( 'row_tag' => 'ntn_core_category_hub_post_game_name', 'empty' => 'Non ci sono ancora articoli in questa sezione.' ) ); ?>
		</section>

	</div>
</main>

<?php
get_footer();
