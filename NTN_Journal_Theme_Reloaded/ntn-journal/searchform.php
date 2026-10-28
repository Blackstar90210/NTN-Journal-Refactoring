<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template per il form di ricerca personalizzato
 *
 * @package NTN_Journal
 */
?>
<form role="search" method="get" class="search-form flex items-stretch border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-black/50 focus-within:border-red-600 dark:focus-within:border-red-600 transition-colors duration-300" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="sr-only" for="s"><?php echo esc_html__( 'Cerca:', 'ntn-journal' ); ?></label>
	<div class="relative flex-grow flex items-center">
		<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-500 dark:text-zinc-400">
			<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
				<path stroke="currentColor" stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
			</svg>
		</div>
		<input type="search" id="s" class="block w-full h-full py-3 pl-10 pr-3 text-sm font-mono text-zinc-900 bg-transparent border-0 focus:ring-0 focus:outline-none dark:text-zinc-300 placeholder-zinc-500" placeholder="<?php echo esc_attr__( 'Cerca...', 'ntn-journal' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
	</div>
	<button type="submit" class="px-5 py-3 text-sm font-mono font-bold text-white bg-red-600 hover:bg-red-700 border-l border-zinc-300 dark:border-zinc-700 focus:outline-none transition-colors duration-300 uppercase">
		<?php echo esc_html__( 'Cerca', 'ntn-journal' ); ?>
	</button>
</form>
