<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package NTN_Journal
 */

?>
<section class="no-results not-found text-center py-16 border border-dashed border-zinc-300 dark:border-zinc-900 max-w-md mx-auto bg-zinc-50/10 p-6 my-12">
    <svg class="mx-auto w-10 h-10 text-zinc-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
    </svg>
    
    <header class="page-header">
        <span class="font-mono text-[10px] text-zinc-500 dark:text-zinc-400 uppercase tracking-widest block font-bold">// NESSUN RISULTATO</span>
        <h2 class="font-display font-bold text-xl text-zinc-950 dark:text-zinc-300 uppercase mt-1">Nessun Articolo Trovato</h2>
    </header>

    <div class="page-content mt-4">
        <?php if ( is_search() ) : ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed mb-4">
                Siamo spiacenti, ma la tua ricerca non ha prodotto risultati. Riprova inserendo parole chiave diverse.
            </p>
            <form role="search" method="get" class="relative max-w-xs mx-auto" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input
                    type="search"
                    name="s"
                    placeholder="Nuova ricerca..."
                    class="w-full pl-4 pr-16 py-1.5 bg-zinc-50 dark:bg-black/50 border border-zinc-300 dark:border-zinc-700 text-xs font-mono rounded-none focus:outline-none focus:border-red-500 dark:focus:border-red-500 transition-colors placeholder-zinc-400 text-zinc-900 dark:text-zinc-300"
                />
                <button type="submit" class="absolute right-0 top-0 bottom-0 px-3 bg-zinc-900 dark:bg-white text-white dark:text-black font-mono text-[10px] uppercase font-bold tracking-wider hover:bg-red-600 dark:hover:bg-red-600 dark:hover:text-white transition-colors">
                    CERCA
                </button>
            </form>
        <?php else : ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                Sembra che non siamo in grado di trovare quello che stai cercando. Torna alla home page per consultare gli ultimi scritti del team.
            </p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mt-4 inline-block px-4 py-2 border border-zinc-950 dark:border-zinc-100 text-xs font-mono uppercase tracking-wider hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors font-bold">
                TORNA ALLA HOME
            </a>
        <?php endif; ?>
    </div>
</section>
