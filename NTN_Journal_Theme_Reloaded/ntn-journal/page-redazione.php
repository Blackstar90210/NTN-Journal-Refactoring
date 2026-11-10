<?php
/**
 * Template Name: Redazione
 *
 * Hub degli autori (v3.17.0): elenco verticale diviso in gruppi personalizzati
 * (per esempio Fondatori, Redazione, Collaboratori), in ordine alfabetico dentro ogni gruppo.
 * I gruppi si scrivono nel riquadro "Gruppi della redazione" di questa pagina; il gruppo di
 * ogni persona si sceglie dal suo profilo. I dati arrivano dal plugin (ntn_core_get_team_data).
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Redazione, versione 25).
 *
 * @package NTN_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main ntn-main flex-grow">
	<?php
	while ( have_posts() ) :
		the_post();
		$ntn_team  = function_exists( 'ntn_core_get_team_data' ) ? ntn_core_get_team_data( get_the_ID() ) : array(
			'groups'  => array(),
			'count'   => 0,
			'updated' => '',
		);
		$ntn_intro = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( (string) get_the_content() ), 60, '…' );
		?>

		<!-- ==================== FASCIA DI APERTURA ==================== -->
		<header class="ntn-band ntn-chub-band">
			<div class="ntn-wrap">

				<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
					<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
				<?php endif; ?>

				<div class="ntn-chub-head">
					<div class="flex flex-wrap gap-2">
						<span class="ntn-chip">Chi siamo</span>
						<?php if ( $ntn_team['count'] ) : ?>
							<span class="ntn-chip ntn-chip--red"><?php echo esc_html( sprintf( 1 === $ntn_team['count'] ? '%d firma' : '%d firme', $ntn_team['count'] ) ); ?></span>
						<?php endif; ?>
					</div>

					<h1 class="ntn-arch-title"><?php the_title(); ?></h1>

					<?php if ( '' !== trim( $ntn_intro ) ) : ?>
						<p class="ntn-arch-desc"><?php echo esc_html( wp_strip_all_tags( $ntn_intro ) ); ?></p>
					<?php endif; ?>

					<?php if ( $ntn_team['count'] ) : ?>
						<dl class="ntn-chub-stats">
							<div><dt class="ntn-stat-k">Firme</dt><dd class="ntn-stat-v"><?php echo esc_html( number_format_i18n( $ntn_team['count'] ) ); ?></dd></div>
							<?php if ( $ntn_team['updated'] ) : ?>
								<div><dt class="ntn-stat-k">Aggiornato</dt><dd class="ntn-stat-v is-small"><?php echo esc_html( $ntn_team['updated'] ); ?></dd></div>
							<?php endif; ?>
						</dl>
					<?php endif; ?>
				</div>

			</div>
		</header>

		<!-- ==================== LE FIRME ==================== -->
		<div class="ntn-wrap ntn-team-body">
			<section class="ntn-home-block" aria-labelledby="ntn-h-team">
				<div class="ntn-sec-head">
					<h2 id="ntn-h-team" class="ntn-sec-title"><span class="ntn-dot text-red-600" aria-hidden="true"></span>// Le firme</h2>
					<span class="ntn-sec-aside">In ordine alfabetico in ogni gruppo</span>
				</div>

				<?php if ( empty( $ntn_team['groups'] ) ) : ?>
					<div class="ntn-empty">
						<span>// Nessuna firma</span>
						<p>La redazione si sta ancora presentando.</p>
					</div>
				<?php else : ?>
					<?php foreach ( $ntn_team['groups'] as $ntn_group ) : ?>
						<?php $ntn_n = count( $ntn_group['people'] ); ?>
						<div class="ntn-team-group">
							<h3 class="ntn-team-group-h">// <?php echo esc_html( $ntn_group['name'] ); ?> <span><?php echo esc_html( sprintf( 1 === $ntn_n ? '%d firma' : '%d firme', $ntn_n ) ); ?></span></h3>
							<?php foreach ( $ntn_group['people'] as $ntn_person ) : ?>
								<?php
								$ntn_avatar   = get_avatar(
									$ntn_person['id'],
									112,
									'',
									'',
									array(
										'class'         => 'w-full h-full object-cover',
										'force_display' => false,
									)
								);
								$ntn_words    = preg_split( '/\s+/', trim( $ntn_person['name'] ) );
								$ntn_initials = '';
								foreach ( array_slice( (array) $ntn_words, 0, 2 ) as $ntn_word ) {
									$ntn_initials .= function_exists( 'mb_substr' ) ? mb_substr( $ntn_word, 0, 1 ) : substr( $ntn_word, 0, 1 );
								}
								?>
								<div class="ntn-team-row">
									<a class="ntn-author-avatar" href="<?php echo esc_url( $ntn_person['url'] ); ?>" tabindex="-1" aria-hidden="true">
										<?php
										// v3.17.1: iniziali a punti sotto, foto sopra solo se esiste (niente sagoma grigia)
										if ( function_exists( 'ntn_journal_author_avatar' ) ) {
											echo ntn_journal_author_avatar( $ntn_person['id'], $ntn_person['name'], 112 ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
										} elseif ( $ntn_avatar ) {
											echo $ntn_avatar; // phpcs:ignore WordPress.Security.EscapeOutput -- markup generato da WordPress.
										} else {
											echo '<span>' . esc_html( strtoupper( $ntn_initials ) ) . '</span>';
										}
										?>
									</a>
									<h4 class="ntn-team-name"><a href="<?php echo esc_url( $ntn_person['url'] ); ?>" rel="author"><?php echo esc_html( $ntn_person['name'] ); ?></a></h4>
									<div class="ntn-team-last">
										<?php if ( $ntn_person['last'] ) : ?>
											<span class="ntn-team-lbl">Ultimo articolo · <?php echo esc_html( $ntn_person['last']['date'] ); ?></span>
											<a href="<?php echo esc_url( $ntn_person['last']['url'] ); ?>"><?php echo esc_html( $ntn_person['last']['title'] ); ?></a>
										<?php else : ?>
											<span class="ntn-team-lbl">Nessun articolo per ora</span>
										<?php endif; ?>
									</div>
									<a class="ntn-team-more" href="<?php echo esc_url( $ntn_person['url'] ); ?>">Tutti i suoi articoli <span aria-hidden="true">→</span></a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		</div>

	<?php endwhile; ?>
</main>

<?php
get_footer();
