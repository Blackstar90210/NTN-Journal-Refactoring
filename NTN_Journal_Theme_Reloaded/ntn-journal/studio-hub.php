<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Hub dello studio (v3.21.0): la pagina del tag di uno studio che ha giochi nel catalogo (es. /tag/3dclouds/).
 * Testata a matrice di punti, poi "// Tutti gli articoli" e "// Giochi dello studio" (ordine scelto dal CEO).
 * Lo carica tag.php con $studio già pronto (dati dal plugin, ntn_core_get_studio_hub_data()).
 *
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html, pagina "Hub studio".
 *
 * @package NTN_Journal
 */

if ( empty( $studio ) || ! is_array( $studio ) ) {
	require get_template_directory() . '/archive.php';
	return;
}

get_header();

/**
 * Etichetta rossa della riga: il gioco dello studio di cui parla l'articolo, se c'è.
 */
$studio_games = wp_list_pluck( $studio['games'], 'title' );
$studio_row   = static function ( $post_id ) use ( $studio_games ) {
	$tags = get_the_terms( $post_id, 'post_tag' );
	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$name = trim( wp_strip_all_tags( wp_specialchars_decode( $tag->name, ENT_QUOTES ) ) );
			if ( in_array( $name, $studio_games, true ) ) {
				return $name;
			}
		}
	}
	return '';
};
?>

<main id="primary" class="site-main ntn-main">

	<!-- ==================== FASCIA DI APERTURA ==================== -->
	<header class="ntn-band ntn-chub-band">
		<div class="ntn-wrap">

			<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
				<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
			<?php endif; ?>

			<div class="ntn-chub-head">
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip">Studio</span>
					<?php if ( ! empty( $studio['parent'] ) ) : ?>
						<span class="ntn-chip ntn-chip--red">Made in Italy</span>
					<?php endif; ?>
				</div>

				<h1 class="ntn-arch-title"><?php echo esc_html( $studio['name'] ); ?></h1>

				<?php if ( '' !== $studio['description'] ) : ?>
					<p class="ntn-arch-desc"><?php echo esc_html( $studio['description'] ); ?></p>
				<?php endif; ?>

				<dl class="ntn-chub-stats">
					<?php foreach ( $studio['stats'] as $stat ) : ?>
						<?php
						if ( '' === (string) $stat[1] ) {
							continue; }
						?>
						<div><dt class="ntn-stat-k"><?php echo esc_html( $stat[0] ); ?></dt><dd class="ntn-stat-v<?php echo 'Ultimo articolo' === $stat[0] ? ' is-small' : ''; ?>"><?php echo esc_html( is_numeric( $stat[1] ) ? number_format_i18n( (int) $stat[1] ) : $stat[1] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</div>

		</div>
	</header>

	<div class="ntn-wrap ntn-chub-body">

		<!-- ==================== TUTTI GLI ARTICOLI ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-sfeed">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-sfeed" class="ntn-sec-title"><span class="ntn-dot text-red-600" aria-hidden="true"></span>// Tutti gli articoli</h2>
				<span class="ntn-sec-aside">Dal più recente</span>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="ntn-chub-feed">
					<?php
					while ( have_posts() ) :
						the_post();
						$row_tag  = $studio_row( get_the_ID() );
						$row_mins = (int) get_post_meta( get_the_ID(), '_ntn_reading_time', true );
						?>
						<a class="ntn-chub-row" href="<?php the_permalink(); ?>">
							<span class="ntn-chub-row-date"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span>
							<span class="min-w-0">
								<?php if ( '' !== $row_tag ) : ?>
									<span class="ntn-chub-row-tag">// <?php echo esc_html( $row_tag ); ?></span>
								<?php else : ?>
									<span class="ntn-chub-row-tag is-plain">// <?php echo esc_html( $studio['name'] ); ?></span>
								<?php endif; ?>
								<span class="ntn-chub-row-title"><?php the_title(); ?></span>
							</span>
							<span class="ntn-chub-row-min"><?php echo esc_html( max( 1, $row_mins ) . ' min' ); ?></span>
						</a>
					<?php endwhile; ?>
				</div>

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
					<p>Non ci sono ancora articoli su questo studio.</p>
				</div>
			<?php endif; ?>
		</section>

		<!-- ==================== GIOCHI DELLO STUDIO ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-sgames">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-sgames" class="ntn-sec-title">// Giochi dello studio</h2>
				<span class="ntn-sec-aside">Dal catalogo IGDB · dal più recente</span>
			</div>
			<div class="ntn-games ntn-studio-games">
				<?php foreach ( $studio['games'] as $game ) : ?>
					<?php $game_date = ( '' !== $game['release'] && function_exists( 'ntn_journal_game_card_date' ) ) ? ntn_journal_game_card_date( $game['hub_id'], $game['release'] ) : ''; ?>
					<article class="ntn-game-card group">
						<?php if ( '' !== $game['url'] ) : ?>
							<a href="<?php echo esc_url( $game['url'] ); ?>" class="absolute inset-0 z-10"><span class="sr-only"><?php echo esc_html( $game['title'] ); ?></span></a>
						<?php endif; ?>
						<div class="ntn-game-cover">
							<?php if ( '' !== $game['cover'] ) : ?>
								<img src="<?php echo esc_url( $game['cover'] ); ?>" alt="<?php echo esc_attr( $game['title'] ); ?>" width="400" height="533" loading="lazy" decoding="async" class="w-full h-full object-cover">
							<?php else : ?>
								<div class="ntn-game-nocover">NO COVER</div>
							<?php endif; ?>
						</div>
						<div class="ntn-game-info">
							<h3 class="ntn-game-title"><?php echo esc_html( $game['title'] ); ?></h3>
							<span class="ntn-game-year ntn-studio-game-meta">
								<span><?php echo esc_html( $game_date ); ?></span>
								<span><b><?php echo esc_html( number_format_i18n( $game['articles'] ) ); ?></b> <?php echo esc_html( 1 === $game['articles'] ? 'articolo' : 'articoli' ); ?></span>
							</span>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</main>

<?php
get_footer();
