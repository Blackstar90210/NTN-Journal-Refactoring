<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Formattazione contenuti del corpo degli articoli.
 * Nota: La manipolazione dei link con prefisso '//', underscore e dot rosso è stata
 * dismessa in favore dello stile tipografico Nothing OS pulito e reattivo gestito direttamente via CSS (.prose a).
 */
function ntn_format_content_links( $content ) {
	return $content;
}
// add_filter( 'the_content', 'ntn_format_content_links', 20 );
