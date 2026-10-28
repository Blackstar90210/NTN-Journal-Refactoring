<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package NTN_Journal
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<ul class="comment-list space-y-6 mb-10">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ul',
					'short_ping' => true,
					'callback'   => 'ntn_journal_custom_comment',
				)
			);
			?>
		</ul>

		<?php
		the_comments_navigation(
			array(
				'prev_text' => '<span class="text-xs font-mono font-bold text-zinc-500 hover:text-red-500 uppercase">&larr; Commenti precedenti</span>',
				'next_text' => '<span class="text-xs font-mono font-bold text-zinc-500 hover:text-red-500 uppercase">Commenti successivi &rarr;</span>',
			)
		);
		?>

	<?php endif; // Check for have_comments(). ?>

	<?php if ( ! comments_open() ) : ?>
		<p class="no-comments font-mono text-xs text-zinc-600 uppercase"><?php esc_html_e( 'Commenti disabilitati per questo articolo.', 'ntn-journal' ); ?></p>
	<?php endif; ?>

	<?php
	// Custom comment form layout with Nothing OS minimal aesthetic
	$commenter = wp_get_current_commenter();
	$req       = get_option( 'require_name_email' );
	$html_req  = ( $req ? " required='required'" : '' );

	$comment_author_val = isset( $commenter['comment_author'] ) ? $commenter['comment_author'] : '';
	$comment_email_val  = isset( $commenter['comment_author_email'] ) ? $commenter['comment_author_email'] : '';

	$fields = array(
		'author' => '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">' .
					'<div>' .
					'<label for="author" class="block font-mono text-[9px] text-zinc-500 dark:text-zinc-400 uppercase font-bold tracking-wider mb-1">Nome' . ( $req ? ' *' : '' ) . '</label>' .
					'<input id="author" name="author" type="text" value="' . esc_attr( $comment_author_val ) . '" size="30"' . $html_req . ' class="w-full bg-zinc-100 dark:bg-black/50 border border-zinc-300 dark:border-zinc-700 focus:border-red-500 text-zinc-900 dark:text-zinc-300 font-mono text-xs px-3 py-2.5 outline-none transition-colors" />' .
					'</div>',
		'email'  => '<div>' .
					'<label for="email" class="block font-mono text-[9px] text-zinc-500 dark:text-zinc-400 uppercase font-bold tracking-wider mb-1">Email' . ( $req ? ' *' : '' ) . '</label>' .
					'<input id="email" name="email" type="email" value="' . esc_attr( $comment_email_val ) . '" size="30"' . $html_req . ' class="w-full bg-zinc-100 dark:bg-black/50 border border-zinc-300 dark:border-zinc-700 focus:border-red-500 text-zinc-900 dark:text-zinc-300 font-mono text-xs px-3 py-2.5 outline-none transition-colors" />' .
					'</div>' .
					'</div>',
	);

	comment_form(
		array(
			'title_reply'          => '<span class="font-mono text-[10px] tracking-widest uppercase text-zinc-500 dark:text-zinc-400 font-bold block mb-4">LASCIA UN COMMENTO</span>',
			'title_reply_to'       => '<span class="font-mono text-[10px] tracking-widest uppercase text-zinc-500 dark:text-zinc-400 font-bold block mb-4">RISPONDI A %s</span>',
			'comment_notes_before' => '',
			'fields'               => $fields,
			'comment_field'        => '<div class="mb-4">' .
									  '<label for="comment" class="block font-mono text-[9px] text-zinc-500 dark:text-zinc-400 uppercase font-bold tracking-wider mb-1">Messaggio *</label>' .
									  '<textarea id="comment" name="comment" cols="45" rows="5" required="required" class="w-full bg-zinc-100 dark:bg-black/50 border border-zinc-300 dark:border-zinc-700 focus:border-red-500 text-zinc-900 dark:text-zinc-300 font-sans text-sm p-3.5 outline-none transition-colors"></textarea>' .
									  '</div>',
			'class_submit'         => 'submit-btn bg-zinc-900 dark:bg-white hover:bg-red-600 dark:hover:bg-red-600 text-white dark:text-black hover:text-white dark:hover:text-white px-5 py-2.5 font-mono text-xs font-bold uppercase transition-colors cursor-pointer inline-block',
			'label_submit'         => 'INVIA COMMENTO',
			'submit_field'         => '<div class="form-submit">%1$s %2$s</div>',
		)
	);
	?>

</div><!-- #comments -->
