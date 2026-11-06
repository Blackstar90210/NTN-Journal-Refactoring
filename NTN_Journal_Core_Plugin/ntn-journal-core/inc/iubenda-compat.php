<?php
/**
 * Compatibilità con il plugin iubenda (v4.15.4).
 *
 * Con il motore "new", iubenda rielabora tutta la pagina e trasforma ogni lettera non inglese in un codice
 * numerico. Dentro gli script questi codici restano scritti (`Pok&amp;#233;mon`), quindi i dati strutturati per
 * Google escono con il codice al posto della lettera. Qui, alla fine del lavoro di iubenda, si riportano le lettere
 * vere solo dentro i blocchi JSON-LD. Il resto della pagina e il blocco dei cookie non vengono toccati.
 *
 * @package NTN_Journal_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Riporta alle lettere vere i codici numerici dentro i blocchi <script type="application/ld+json">.
 *
 * I caratteri ASCII (virgolette, apici, &lt;) restano come sono, per non rompere il JSON.
 *
 * @param mixed $html Pagina completa.
 * @return mixed Pagina con i JSON-LD puliti.
 */
function ntn_core_restore_jsonld_entities( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'ld+json' ) || ! function_exists( 'mb_chr' ) ) {
		return $html;
	}

	$result = preg_replace_callback(
		'#(<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>)(.*?)(</script>)#is',
		static function ( $m ) {
			$json = preg_replace_callback(
				'/(?:&amp;|&)#(?:x([0-9a-f]+)|(\d+));/i',
				static function ( $e ) {
					$code = '' !== $e[1] ? hexdec( $e[1] ) : (int) $e[2];
					if ( $code < 0x80 || $code > 0x10FFFF || ( $code >= 0xD800 && $code <= 0xDFFF ) ) {
						return $e[0];
					}
					return mb_chr( $code, 'UTF-8' );
				},
				$m[2]
			);
			// Una "&" rimasta scritta "&amp;" dal rimaneggiamento della pagina torna "&".
			$json = str_replace( '&amp;', '&', (string) $json );
			return $m[1] . $json . $m[3];
		},
		$html
	);

	return is_string( $result ) ? $result : $html;
}
add_filter( 'iubenda_final_output', 'ntn_core_restore_jsonld_entities', 99 );
