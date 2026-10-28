<?php
/**
 * Pannello "// Condividi" (v3.16.0, scelta del CEO).
 *
 * Cinque celle con le sigle in Mono: X, Bluesky, Facebook, WhatsApp e Copia link.
 * I link ai social funzionano senza JavaScript; "Copia link" usa lo script del tema
 * (assets/js/main.js) e, se manca, apre comunque l'articolo.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Articolo, colonna di destra).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function ntn_journal_share_buttons() {
	if ( ! get_theme_mod( 'ntn_journal_enable_share_buttons', true ) ) {
		return;
	}

	$permalink = (string) get_permalink();
	$url       = rawurlencode( $permalink );
	$title     = rawurlencode( html_entity_decode( get_the_title(), ENT_COMPAT, 'UTF-8' ) );

	$networks = array(
		'x'        => array(
			'name'  => 'X',
			'label' => 'X',
			'url'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
		),
		'bluesky'  => array(
			'name'  => 'Bluesky',
			'label' => 'BSKY',
			'url'   => 'https://bsky.app/intent/compose?text=' . $title . '%20' . $url,
		),
		'facebook' => array(
			'name'  => 'Facebook',
			'label' => 'FB',
			'url'   => 'https://www.facebook.com/sharer.php?u=' . $url,
		),
		'whatsapp' => array(
			'name'  => 'WhatsApp',
			'label' => 'WA',
			'url'   => 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url,
		),
	);

	?>
	<div class="ntn-panel ntn-share-panel">
		<div class="ntn-panel-h"><span>// Condividi</span></div>
		<div class="ntn-share">
			<?php foreach ( $networks as $network ) : ?>
				<a href="<?php echo esc_url( $network['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( 'Condividi su %s', $network['name'] ) ); ?>"><?php echo esc_html( $network['label'] ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( $permalink ); ?>" class="ntn-share-copy" data-ntn-copy="<?php echo esc_url( $permalink ); ?>" aria-label="Copia il link dell'articolo">Link</a>
		</div>
	</div>
	<?php
}
