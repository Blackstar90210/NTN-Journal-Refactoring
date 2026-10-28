<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Get defined term label for ratings.
 */
function get_rating_label( $rating ) {
	if ( $rating === null || $rating === '' || ! is_scalar( $rating ) || ! is_numeric( $rating ) ) {
		return '';
	}
	$val = intval( $rating );
	if ( $val === 1 || $val === 2 ) {
		return 'BOCCIATO';
	} elseif ( $val === 3 ) {
		return 'CONSIGLIATO CON RISERVA';
	} elseif ( $val === 4 ) {
		return 'CONSIGLIATO';
	} elseif ( $val === 5 ) {
		return 'MUST PLAY';
	}
	return '';
}

/**
 * Get defined term initials for ratings.
 */
function get_rating_initials( $rating ) {
	if ( $rating === null || $rating === '' || ! is_scalar( $rating ) || ! is_numeric( $rating ) ) {
		return '';
	}
	$val = intval( $rating );
	if ( $val === 1 || $val === 2 ) {
		return 'B';
	} elseif ( $val === 3 ) {
		return 'C.R.';
	} elseif ( $val === 4 ) {
		return 'C';
	} elseif ( $val === 5 ) {
		return 'M.P.';
	}
	return '';
}
