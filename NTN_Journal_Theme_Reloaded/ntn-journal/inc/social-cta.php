<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Pannello "// Seguici" (v3.16.1, al posto del riquadro "Ti è piaciuto questo articolo?").
 *
 * Sta nella colonna laterale sotto "// Fonti preferite su Google" (da telefono, sotto l'articolo)
 * con le stesse celle di "// Condividi": una per ogni profilo inserito nel Personalizza.
 * Se nessun profilo è inserito, il pannello non compare.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Articolo, versione 23).
 */
function ntn_journal_social_cta_box() {
	$all_socials = array(
		'telegram'  => array(
			'name'  => 'Telegram',
			'label' => 'TG',
		),
		'facebook'  => array(
			'name'  => 'Facebook',
			'label' => 'FB',
		),
		'instagram' => array(
			'name'  => 'Instagram',
			'label' => 'IG',
		),
		'x'         => array(
			'name'  => 'X',
			'label' => 'X',
		),
		'bluesky'   => array(
			'name'  => 'Bluesky',
			'label' => 'BSKY',
		),
		'mastodon'  => array(
			'name'  => 'Mastodon',
			'label' => 'MASTO',
		),
	);

	$social_channels = array();
	foreach ( $all_socials as $id => $data ) {
		$url = get_theme_mod( 'ntn_social_' . $id );
		if ( ! empty( $url ) && is_string( $url ) ) {
			$social_channels[] = array(
				'name'  => $data['name'],
				'label' => $data['label'],
				'url'   => $url,
			);
		}
	}

	if ( empty( $social_channels ) ) {
		return '';
	}

	$site_name = preg_replace( '/^www\./i', '', (string) get_bloginfo( 'name' ) );
	$count     = count( $social_channels );

	ob_start();
	?>
	<div class="ntn-panel ntn-follow">
		<div class="ntn-panel-h"><span>// Seguici</span><span><?php echo esc_html( sprintf( 1 === $count ? '%d canale' : '%d canali', $count ) ); ?></span></div>
		<div class="ntn-share ntn-share--follow">
			<?php foreach ( $social_channels as $channel ) : ?>
				<a href="<?php echo esc_url( $channel['url'] ); ?>" target="_blank" rel="noopener noreferrer me" aria-label="<?php echo esc_attr( sprintf( '%s su %s', $site_name, $channel['name'] ) ); ?>"><?php echo esc_html( $channel['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Pannello "// Fonti preferite su Google" (v3.16.0, al posto del box "Google feed").
 *
 * Sta nella colonna laterale sotto "// Condividi" (da telefono, sotto l'articolo).
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Articolo).
 */
function ntn_journal_google_feed_box() {
	if ( ! get_theme_mod( 'ntn_journal_enable_google_feed', true ) ) {
		return '';
	}

	$feed_url = get_theme_mod( 'ntn_google_feed_url', '' );
	if ( empty( $feed_url ) ) {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$domain    = ! empty( $site_host ) ? $site_host : 'q-gin.info';
		// Rimuove 'www.' per consentire a Google di rilevare l'editore/fonte esatta
		$domain   = preg_replace( '/^www\./i', '', (string) $domain );
		$feed_url = 'https://www.google.com/preferences/source?q=' . rawurlencode( $domain );
	} else {
		// Se un URL personalizzato preferences/source contiene q=www., ripuliscilo
		$feed_url = preg_replace( '/([?&]q=)www\./i', '$1', (string) $feed_url );
	}

	$site_name = preg_replace( '/^www\./i', '', (string) get_bloginfo( 'name' ) );

	ob_start();
	?>
	<div class="ntn-panel ntn-gfeed">
		<div class="ntn-panel-h"><span>// Fonti preferite su Google</span></div>
		<div class="ntn-gfeed-b">
			<p><?php echo esc_html( sprintf( /* translators: %s: nome del sito. */ __( 'Aggiungi %s alle tue fonti preferite: le nostre notizie compariranno più spesso nelle "Notizie principali" di Google.', 'ntn-journal' ), $site_name ) ); ?></p>
			<a href="<?php echo esc_url( $feed_url ); ?>" target="_blank" rel="noopener noreferrer" class="ntn-btn ntn-btn--solid ntn-gfeed-btn">Aggiungi su Google <span aria-hidden="true">↗</span></a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
