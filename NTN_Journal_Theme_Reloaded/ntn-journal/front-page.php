<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Home (fase 3 del restyling, v3.4.0).
 *
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Home).
 * Apertura a matrice di punti con un articolo grande e tre piccoli al posto del
 * carosello, ultime notizie come lista compatta, recensioni a celle con i cinque
 * LED, fascia di un Hub di sezione, prossime uscite dal catalogo, editoriali (v3.21.1).
 *
 * @package NTN_Journal
 */

get_header();

$ntn_home     = ntn_front_page_data();
$ntn_lead     = $ntn_home['lead'];
$ntn_main     = ! empty( $ntn_lead ) ? $ntn_lead[0] : null;
$ntn_side     = array_slice( $ntn_lead, 1, 3 );
$ntn_counts   = $ntn_home['counts'];
$ntn_weekdays = array( 'domenica', 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato' );

/**
 * Link di una categoria per slug, con la Home come ripiego.
 *
 * @param string $slug Slug della categoria.
 * @return string
 */
$ntn_cat_url = static function ( $slug ) {
	$cat  = get_category_by_slug( $slug );
	$link = $cat ? get_category_link( $cat->term_id ) : '';
	return ( $link && ! is_wp_error( $link ) ) ? $link : home_url( '/' );
};

// Fascia dell'Hub di sezione: la categoria si sceglie dal Customizer.
$ntn_hub_slug = (string) get_theme_mod( 'ntn_journal_front_hub_category', 'sviluppatori-italiani-di-videogiochi' );
$ntn_hub_cat  = $ntn_hub_slug ? get_category_by_slug( $ntn_hub_slug ) : null;
$ntn_hub_map  = (bool) get_theme_mod( 'ntn_journal_front_hub_map', true );
?>

<main id="primary" class="site-main ntn-main flex-grow">

	<?php if ( $ntn_main ) : ?>
	<!-- ==================== APERTURA ==================== -->
	<section class="ntn-band" aria-labelledby="ntn-lead-title">
		<div class="ntn-wrap">
			<div class="ntn-edition">
				<span><strong>Edizione di <?php echo esc_html( $ntn_weekdays[ (int) wp_date( 'w' ) ] ); ?></strong> · <?php echo esc_html( wp_date( 'd.m.Y' ) ); ?></span>
				<span class="ntn-edition-counts">
					<?php if ( $ntn_counts['today'] ) : ?>
						<span><b><?php echo esc_html( number_format_i18n( $ntn_counts['today'] ) ); ?></b><?php echo 1 === $ntn_counts['today'] ? 'Articolo oggi' : 'Articoli oggi'; ?></span>
					<?php endif; ?>
					<?php if ( $ntn_counts['reviews'] ) : ?>
						<span><b><?php echo esc_html( number_format_i18n( $ntn_counts['reviews'] ) ); ?></b>Recensioni</span>
					<?php endif; ?>
					<?php if ( $ntn_counts['games'] ) : ?>
						<span><b><?php echo esc_html( number_format_i18n( $ntn_counts['games'] ) ); ?></b>Giochi nel catalogo</span>
					<?php endif; ?>
				</span>
			</div>

			<div class="ntn-lead">
				<a class="ntn-lead-main group" href="<?php echo esc_url( $ntn_main['permalink'] ); ?>">
					<?php ntn_front_image( $ntn_main, 'aspect-video', true ); ?>
					<div class="flex flex-wrap gap-2">
						<span class="ntn-chip ntn-chip--red">In evidenza</span>
						<?php if ( $ntn_main['cat_name'] ) : ?>
							<span class="ntn-chip"><?php echo esc_html( $ntn_main['cat_name'] ); ?></span>
						<?php endif; ?>
					</div>
					<h2 id="ntn-lead-title" class="ntn-lead-title"><?php echo esc_html( $ntn_main['title'] ); ?></h2>
					<?php if ( $ntn_main['excerpt'] ) : ?>
						<p class="ntn-lead-excerpt"><?php echo esc_html( $ntn_main['excerpt'] ); ?></p>
					<?php endif; ?>
					<div class="ntn-meta">
						<?php ntn_front_entity( $ntn_main ); ?>
						<?php if ( $ntn_main['author'] ) : ?>
							<span>by <b><?php echo esc_html( $ntn_main['author'] ); ?></b></span>
						<?php endif; ?>
						<span><?php echo esc_html( $ntn_main['date'] ); ?></span>
						<span><?php echo esc_html( $ntn_main['minutes'] ); ?> min</span>
					</div>
				</a>

				<?php if ( ! empty( $ntn_side ) ) : ?>
				<div class="ntn-lead-side">
					<?php foreach ( $ntn_side as $ntn_card ) : ?>
						<a class="ntn-lead-item group" href="<?php echo esc_url( $ntn_card['permalink'] ); ?>">
							<?php ntn_front_image( $ntn_card, 'aspect-[4/3]' ); ?>
							<div class="min-w-0">
								<?php if ( $ntn_card['cat_name'] ) : ?>
									<span class="ntn-kicker"><?php echo esc_html( $ntn_card['cat_name'] ); ?></span>
								<?php endif; ?>
								<h3 class="ntn-lead-item-title"><?php echo esc_html( $ntn_card['title'] ); ?></h3>
								<?php if ( $ntn_card['excerpt'] ) : ?>
									<p class="ntn-lead-item-excerpt"><?php echo esc_html( wp_trim_words( $ntn_card['excerpt'], 14, '…' ) ); ?></p>
								<?php endif; ?>
								<div class="ntn-meta mt-2"><?php ntn_front_entity( $ntn_card ); ?></div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<div class="ntn-wrap">

		<?php if ( ! empty( $ntn_home['news'] ) ) : ?>
		<!-- ==================== ULTIME NOTIZIE ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-news">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-news" class="ntn-sec-title"><span class="ntn-dot text-red-600" aria-hidden="true"></span>// Ultime notizie</h2>
				<a class="ntn-sec-aside hover:text-red-600" href="<?php echo esc_url( $ntn_cat_url( 'notizie' ) ); ?>">Tutte le notizie →</a>
			</div>
			<div class="ntn-news">
				<?php foreach ( array_chunk( $ntn_home['news'], 3 ) as $ntn_col ) : ?>
					<div>
						<?php foreach ( $ntn_col as $ntn_card ) : ?>
							<?php
							$ntn_is_today = wp_date( 'Y-m-d', $ntn_card['timestamp'] ) === wp_date( 'Y-m-d' );
							$ntn_when     = $ntn_is_today ? wp_date( 'H:i', $ntn_card['timestamp'] ) : wp_date( 'd.m', $ntn_card['timestamp'] );
							?>
							<a class="ntn-row group" href="<?php echo esc_url( $ntn_card['permalink'] ); ?>">
								<span class="ntn-row-time"><?php echo esc_html( $ntn_when ); ?></span>
								<span class="min-w-0">
									<span class="ntn-meta ntn-row-entity"><?php ntn_front_entity( $ntn_card ); ?></span>
									<span class="ntn-row-title"><?php echo esc_html( $ntn_card['title'] ); ?></span>
								</span>
								<span class="ntn-row-min"><?php echo esc_html( $ntn_card['minutes'] ); ?> min</span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $ntn_home['reviews'] ) ) : ?>
		<!-- ==================== RECENSIONI ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-rev">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-rev" class="ntn-sec-title">// Recensioni</h2>
				<a class="ntn-sec-aside hover:text-red-600" href="<?php echo esc_url( $ntn_cat_url( 'recensioni' ) ); ?>">Archivio recensioni →</a>
			</div>
			<div class="ntn-cells ntn-cells--4">
				<?php foreach ( $ntn_home['reviews'] as $ntn_card ) : ?>
					<a class="ntn-cell ntn-card group" href="<?php echo esc_url( $ntn_card['permalink'] ); ?>">
						<?php ntn_front_image( $ntn_card, 'aspect-[16/10]' ); ?>
						<div class="ntn-meta"><?php ntn_front_entity( $ntn_card ); ?></div>
						<h3 class="ntn-card-title"><?php echo esc_html( $ntn_card['title'] ); ?></h3>
						<?php if ( $ntn_card['rating'] ) : ?>
							<div class="ntn-score"><?php ntn_front_leds( $ntn_card['rating'] ); ?></div>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

	</div>

	<?php if ( $ntn_hub_cat ) : ?>
	<!-- ==================== FASCIA HUB DI SEZIONE ==================== -->
	<section class="ntn-band ntn-hubband" aria-labelledby="ntn-h-hub">
		<div class="ntn-wrap ntn-hubband-grid">
			<div>
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip">Hub di sezione</span>
					<?php if ( $ntn_hub_map ) : ?>
						<span class="ntn-chip ntn-chip--red">Made in Italy</span>
					<?php endif; ?>
				</div>
				<h2 id="ntn-h-hub" class="ntn-hubband-title"><?php echo esc_html( $ntn_hub_cat->name ); ?></h2>
				<?php if ( $ntn_hub_cat->description ) : ?>
					<p class="ntn-hubband-text"><?php echo esc_html( wp_strip_all_tags( $ntn_hub_cat->description ) ); ?></p>
				<?php endif; ?>
				<div class="ntn-hubband-stats">
					<div><span class="ntn-stat-k">Articoli</span><span class="ntn-stat-v"><?php echo esc_html( number_format_i18n( $ntn_hub_cat->count ) ); ?></span></div>
				</div>
				<a class="ntn-cta" href="<?php echo esc_url( get_category_link( $ntn_hub_cat->term_id ) ); ?>">Apri la sezione →</a>
			</div>
			<?php if ( $ntn_hub_map ) : ?>
				<canvas id="ntn-italy" class="ntn-hubband-map" aria-hidden="true"></canvas>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<div class="ntn-wrap">

		<?php if ( ! empty( $ntn_home['releases'] ) ) : ?>
		<!-- ==================== PROSSIME USCITE ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-rel">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-rel" class="ntn-sec-title">// Prossime uscite dal catalogo</h2>
				<a class="ntn-sec-aside hover:text-red-600" href="<?php echo esc_url( get_post_type_archive_link( 'videogioco' ) ); ?>">Catalogo completo →</a>
			</div>
			<div class="overflow-x-auto">
				<table class="ntn-rel">
					<thead>
						<tr><th scope="col">Uscita</th><th scope="col">Gioco</th><th scope="col" class="ntn-hide-sm">Studio</th><th scope="col">Piattaforme</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $ntn_home['releases'] as $ntn_game ) : ?>
							<tr>
								<td class="ntn-rel-date<?php echo esc_attr( $ntn_game['soon'] ? ' is-soon' : '' ); ?>"><?php echo esc_html( $ntn_game['date'] ); ?></td>
								<td class="ntn-rel-game"><a href="<?php echo esc_url( $ntn_game['permalink'] ); ?>"><?php echo esc_html( $ntn_game['title'] ); ?></a></td>
								<td class="ntn-rel-meta ntn-hide-sm"><?php echo esc_html( $ntn_game['developer'] ); ?></td>
								<td class="ntn-rel-meta"><?php echo esc_html( $ntn_game['platforms'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $ntn_home['features'] ) ) : ?>
		<!-- ==================== EDITORIALI ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-feat">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-feat" class="ntn-sec-title">// Editoriali</h2>
				<a class="ntn-sec-aside hover:text-red-600" href="<?php echo esc_url( $ntn_cat_url( 'editoriali' ) ); ?>">Tutti →</a>
			</div>
			<div class="ntn-cells ntn-cells--3">
				<?php foreach ( $ntn_home['features'] as $ntn_card ) : ?>
					<a class="ntn-cell ntn-card group" href="<?php echo esc_url( $ntn_card['permalink'] ); ?>">
						<?php ntn_front_image( $ntn_card, 'aspect-[16/10]' ); ?>
						<div class="ntn-meta">
							<?php if ( $ntn_card['cat_name'] ) : ?>
								<span><?php echo esc_html( $ntn_card['cat_name'] ); ?></span>
							<?php endif; ?>
							<span><?php echo esc_html( $ntn_card['minutes'] ); ?> min</span>
						</div>
						<h3 class="ntn-card-title"><?php echo esc_html( $ntn_card['title'] ); ?></h3>
						<?php if ( $ntn_card['excerpt'] ) : ?>
							<p class="ntn-card-excerpt"><?php echo esc_html( wp_trim_words( $ntn_card['excerpt'], 22, '…' ) ); ?></p>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'sidebar-home-twitch' ) || is_active_sidebar( 'sidebar-home-youtube' ) ) : ?>
		<!-- ==================== LIVE E VIDEO (widget) ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-video">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-video" class="ntn-sec-title">// Live e video</h2>
			</div>
			<div class="ntn-cells ntn-cells--2">
				<?php
				foreach ( array(
					'sidebar-home-twitch'  => 'Twitch live',
					'sidebar-home-youtube' => 'YouTube',
				) as $ntn_sidebar => $ntn_label ) :
					?>
					<?php if ( is_active_sidebar( $ntn_sidebar ) ) : ?>
						<div class="ntn-cell">
							<div class="ntn-meta mb-3"><span class="ntn-entity"><span class="ntn-dot" aria-hidden="true"></span><?php echo esc_html( $ntn_label ); ?></span></div>
							<div class="ntn-video-widget aspect-video w-full relative ntn-frame">
								<?php dynamic_sidebar( $ntn_sidebar ); ?>
							</div>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

	</div>

</main>

<?php
get_footer();
