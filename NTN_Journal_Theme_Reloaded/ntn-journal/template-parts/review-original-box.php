<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template part for the original review box.
 * Accepts an argument 'is_mobile' to change the ID and avoid duplicate DOM IDs when rendered twice.
 * Accepts 'hide_verdict' to hide the verdict (used in the game Hub).
 *
 * Fase 5 del restyling (v3.7.0): valutazione sulla fascia a matrice di punti che segue il tema,
 * cinque LED quadrati con il voto anche in numero, pro e contro senza il verde.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Recensione).
 */

$post_id = get_the_ID();
$rating = get_post_rating( $post_id );

if ( empty( $rating ) ) {
	return;
}

$is_mobile = isset( $args['is_mobile'] ) ? $args['is_mobile'] : false;
$box_id = $is_mobile ? 'review-summary-card-mobile' : 'review-summary-card';

$final_rating = max( 1, min( 5, intval( $rating ) ) );
$pros_meta = get_post_meta( $post_id, 'pros', true );
$cons_meta = get_post_meta( $post_id, 'cons', true );
$verdict_meta = get_post_meta( $post_id, 'verdict', true );
$review_source_meta = get_post_meta( $post_id, 'ntn_review_source', true );

// Parse Pros: una voce per riga, le virgole restano nel testo (v3.22.4)
$pros_arr = array();
if ( ! empty( $pros_meta ) ) {
	if ( is_array( $pros_meta ) ) {
		$pros_arr = $pros_meta;
	} else {
		$pros_arr = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $pros_meta ) ) ) );
	}
}

// Parse Cons: una voce per riga, le virgole restano nel testo (v3.22.4)
$cons_arr = array();
if ( ! empty( $cons_meta ) ) {
	if ( is_array( $cons_meta ) ) {
		$cons_arr = $cons_meta;
	} else {
		$cons_arr = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $cons_meta ) ) ) );
	}
}

// Verdict (dalla 4.16.1 nessun testo di esempio: se manca, la sezione non compare)
$verdict_text = ! empty( $verdict_meta ) ? $verdict_meta : '';

// Giudizio e link al suo tag (Bocciato, Consigliato con riserva, Consigliato, Must Play)
$rating_label = function_exists( 'get_rating_label' ) ? get_rating_label( $final_rating ) : (string) $final_rating;
$rating_link = '';
$rating_slug = sanitize_title( $rating_label );
if ( in_array( $rating_slug, array( 'bocciato', 'consigliato-con-riserva', 'consigliato', 'must-play' ) ) ) {
	$tag_obj = get_term_by( 'slug', $rating_slug, 'post_tag' );
	if ( $tag_obj && ! is_wp_error( $tag_obj ) && $tag_obj instanceof WP_Term ) {
		$tag_link = get_term_link( $tag_obj );
		$rating_link = ( ! empty( $tag_link ) && ! is_wp_error( $tag_link ) && is_string( $tag_link ) ) ? $tag_link : home_url( '/tag/' . $rating_slug . '/' );
	} else {
		$rating_link = home_url( '/tag/' . $rating_slug . '/' );
	}
}
?>

<section id="<?php echo esc_attr( $box_id ); ?>" class="ntn-rbox" aria-label="<?php echo esc_attr( sprintf( 'Valutazione: %s, voto %d su 5', $rating_label, $final_rating ) ); ?>">
	<?php if ( ! empty( $review_source_meta ) ) : ?>
	<!-- Origine del videogioco -->
	<div class="ntn-rbox-origin">
		<span>// Origine del videogioco</span>
		<b><?php echo esc_html( $review_source_meta ); ?></b>
	</div>
	<?php endif; ?>

	<!-- Valutazione finale sulla fascia a matrice di punti -->
	<div class="ntn-rbox-eval">
		<div class="min-w-0">
			<span class="ntn-rbox-k">// Valutazione finale</span>
			<?php if ( ! empty( $rating_link ) ) : ?>
				<a href="<?php echo esc_url( $rating_link ); ?>" class="ntn-verdict-word">
					<span><?php echo esc_html( $rating_label ); ?></span>
					<span class="ntn-verdict-dot" aria-hidden="true"></span>
				</a>
			<?php else : ?>
				<span class="ntn-verdict-word"><?php echo esc_html( $rating_label ); ?></span>
			<?php endif; ?>
		</div>

		<div class="ntn-scorebox">
			<div class="ntn-leds ntn-leds--xl" role="img" aria-label="<?php echo esc_attr( sprintf( 'Voto %d su 5', $final_rating ) ); ?>">
				<?php
				for ( $i = 1; $i <= 5; $i++ ) {
					$led_class = 'ntn-led';
					if ( $i <= $final_rating ) {
						$led_class .= ' is-on';
					}
					if ( $i === $final_rating ) {
						$led_class .= ' is-last';
					}
					echo '<i class="' . esc_attr( $led_class ) . '"></i>';
				}
				?>
			</div>
			<span class="ntn-score-n"><b><?php echo esc_html( number_format( $final_rating, 0 ) ); ?></b> / 5 · <?php echo esc_html( $rating_label ); ?></span>
		</div>
	</div>

	<?php if ( ! empty( $pros_arr ) || ! empty( $cons_arr ) ) : ?>
	<!-- Pro e contro: compaiono solo quelli scritti nella recensione -->
	<div class="ntn-rbox-pc"<?php echo ( empty( $pros_arr ) || empty( $cons_arr ) ) ? ' style="grid-template-columns:minmax(0,1fr)"' : ''; ?>>
		<?php if ( ! empty( $pros_arr ) ) : ?>
		<div class="is-pro">
			<h4><span>// Pro</span><span><?php echo esc_html( count( $pros_arr ) ); ?></span></h4>
			<ul>
				<?php foreach ( $pros_arr as $pro ) : ?>
					<li><?php echo esc_html( $pro ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $cons_arr ) ) : ?>
		<div class="is-con">
			<h4><span>// Contro</span><span><?php echo esc_html( count( $cons_arr ) ); ?></span></h4>
			<ul>
				<?php foreach ( $cons_arr as $con ) : ?>
					<li><?php echo esc_html( $con ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php if ( empty( $args['hide_verdict'] ) && '' !== trim( (string) $verdict_text ) ) : ?>
	<!-- Il verdetto -->
	<div class="ntn-rbox-verdict">
		<span class="ntn-rbox-k">// Il verdetto</span>
		<div class="ntn-rbox-verdict-text">
			<?php echo wp_kses_post( wpautop( $verdict_text ) ); ?>
		</div>
	</div>
	<?php endif; ?>
</section>
