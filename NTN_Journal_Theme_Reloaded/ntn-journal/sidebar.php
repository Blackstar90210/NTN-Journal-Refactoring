<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package NTN_Journal
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area p-6 bg-zinc-50 dark:bg-black/20 border-t md:border-t-0 border-zinc-300 dark:border-zinc-700 transition-colors duration-300">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
