<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Get date formatted in Italian (e.g., 02.LUG.2026)
 */
function ntn_journal_get_italian_date() {
	$months    = array(
		'Jan' => 'GEN',
		'Feb' => 'FEB',
		'Mar' => 'MAR',
		'Apr' => 'APR',
		'May' => 'MAG',
		'Jun' => 'GIU',
		'Jul' => 'LUG',
		'Aug' => 'AGO',
		'Sep' => 'SET',
		'Oct' => 'OTT',
		'Nov' => 'NOV',
		'Dec' => 'DIC',
	);
	$eng_month = wp_date( 'M' );
	if ( isset( $months[ $eng_month ] ) ) {
		return wp_date( 'd' ) . '.' . $months[ $eng_month ] . '.' . wp_date( 'Y' );
	}
	return strtoupper( wp_date( 'd.M.Y' ) );
}

/**
 * Register social links fields in WordPress user profiles
 */
function ntn_journal_user_contactmethods( $contactmethods ) {
	$contactmethods['twitter']   = esc_html__( 'Twitter / X URL', 'ntn-journal' );
	$contactmethods['instagram'] = esc_html__( 'Instagram URL', 'ntn-journal' );
	$contactmethods['linkedin']  = esc_html__( 'LinkedIn URL', 'ntn-journal' );
	$contactmethods['github']    = esc_html__( 'GitHub URL', 'ntn-journal' );
	$contactmethods['youtube']   = esc_html__( 'YouTube URL', 'ntn-journal' );
	$contactmethods['facebook']  = esc_html__( 'Facebook URL', 'ntn-journal' );
	return $contactmethods;
}
add_filter( 'user_contactmethods', 'ntn_journal_user_contactmethods' );
