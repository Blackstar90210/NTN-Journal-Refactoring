<?php
/**
 * Aspetto dei singoli commenti (callback di wp_list_comments in comments.php).
 *
 * Sta qui, caricato da functions.php, perché la funzione deve esistere prima che
 * comments.php la chiami. Prima della 3.4.1 era dichiarata in fondo a
 * comments.php, dopo la chiamata, e gli articoli con commenti andavano in errore.
 *
 * @package NTN_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ntn_journal_custom_comment' ) ) {
	/**
	 * Custom Comment Callback for Nothing OS / NTN Journal styling.
	 *
	 * Il tag <li> resta aperto: lo chiude WordPress (Walker_Comment::end_el).
	 *
	 * @param WP_Comment $comment Commento.
	 * @param array      $args    Argomenti di wp_list_comments().
	 * @param int        $depth   Profondità della risposta.
	 */
	function ntn_journal_custom_comment( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- richiesto dai template tag dei commenti.
		?>
		<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'border-b border-zinc-300 dark:border-zinc-900 pb-6 mb-6 last:border-0 last:pb-0 last:mb-0' ); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body flex gap-4">

				<!-- Avatar block -->
				<div class="w-8 h-8 border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-black flex items-center justify-center font-mono text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase shrink-0 overflow-hidden transition-colors">
					<?php
					$avatar = get_avatar( $comment, 64, '', '', array( 'class' => 'w-full h-full object-cover' ) );
					if ( $avatar ) {
						echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput -- markup generato da WordPress.
					} else {
						echo esc_html( substr( get_comment_author(), 0, 2 ) );
					}
					?>
				</div>

				<div class="flex-grow min-w-0">
					<header class="comment-meta mb-2 flex flex-wrap items-baseline gap-2">
						<span class="font-mono text-xs font-bold text-zinc-900 dark:text-zinc-300 uppercase"><?php comment_author_link(); ?></span>
						<span class="text-[9px] font-mono text-zinc-500 dark:text-zinc-400">//</span>
						<span class="text-[9px] font-mono text-zinc-500 uppercase">
							<?php echo esc_html( sprintf( '%1$s @ %2$s', get_comment_date(), get_comment_time() ) ); ?>
						</span>
					</header>

					<div class="comment-content text-zinc-600 dark:text-zinc-400 font-sans text-xs sm:text-sm leading-relaxed">
						<?php comment_text(); ?>
					</div>

					<div class="reply mt-2">
						<?php
						comment_reply_link(
							array_merge(
								$args,
								array(
									'depth'      => $depth,
									'max_depth'  => $args['max_depth'],
									'reply_text' => '<span class="font-mono text-[9px] text-red-500 font-bold hover:underline">RISPONDI</span>',
								)
							)
						);
						?>
					</div>
				</div>

			</article>
		<?php
	}
}
