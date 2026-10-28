<?php
/**
 * NTN Journal - Ticker Breaking News / Copertura Live
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ntn_journal_render_breaking_news() {
	$is_active = get_option( 'ntn_breaking_news_active', 'no' );
	
	if ( $is_active !== 'yes' ) {
		return;
	}
	
	$label       = get_option( 'ntn_breaking_news_label', 'LIVE' );
	$text        = get_option( 'ntn_breaking_news_text', '' );
	$url         = get_option( 'ntn_breaking_news_url', '' );
	$badge_color = get_option( 'ntn_breaking_news_badge_color', '#dc2626' ); // Default Tailwind red-600
	$sticky      = get_option( 'ntn_breaking_news_sticky', 'no' ) === 'yes';
	$dismissable = get_option( 'ntn_breaking_news_dismissable', 'no' ) === 'yes';
	
	if ( empty( $text ) ) {
		return;
	}
	
	$wrapper_classes = 'ntn-breaking-news-wrapper w-full bg-zinc-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-900 text-zinc-900 dark:text-zinc-300 font-mono text-[12px] transition-all duration-300';
	// Impostiamo sempre relative, se è sticky gestiamo con JS per via del contenitore header
	$wrapper_classes .= ' relative z-[100]';
	
	?>
	<div id="ntn-breaking-news-banner" class="<?php echo esc_attr( $wrapper_classes ); ?>">
		<div class="max-w-7xl mx-auto px-6 md:px-12 py-2 flex items-center justify-between gap-4">
			
			<div class="flex items-center gap-3 overflow-hidden flex-1">
				<!-- Pulsing LED Badge -->
				<div class="flex items-center gap-2 shrink-0 z-10 bg-zinc-50 dark:bg-zinc-950 pr-2 transition-colors duration-300">
					<span class="w-2.5 h-2.5 rounded-full animate-pulse" style="background-color: <?php echo esc_attr( $badge_color ); ?>;"></span>
					<span class="font-bold tracking-wider" style="color: <?php echo esc_attr( $badge_color ); ?>;"><?php echo esc_html( $label ); ?></span>
				</div>
				
				<!-- Testo Notizia con Scorrimento Orizzontale (Marquee) -->
				<div class="ntn-marquee-container text-zinc-500 dark:text-zinc-400 flex-1">
					<div class="ntn-marquee-content">
						<?php if ( ! empty( $url ) ) : ?>
							<a href="<?php echo esc_url( $url ); ?>" class="hover:text-zinc-900 dark:hover:text-white transition-colors duration-200 inline-block pr-8">
								<?php echo esc_html( $text ); ?> <span class="ml-1 inline-block">&rarr;</span>
							</a>
						<?php else : ?>
							<span class="inline-block pr-8"><?php echo esc_html( $text ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
			
			<?php if ( $dismissable ) : ?>
				<!-- Close Button -->
				<button id="ntn-bn-close" type="button" class="shrink-0 text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors duration-200 p-1 cursor-pointer select-none" aria-label="Chiudi Ticker">
					[X]
				</button>
			<?php endif; ?>
			
		</div>
	</div>
	
	<?php if ( $dismissable ) : ?>
		<!-- Pillola Ridotta (nascosta di default) -->
		<div id="ntn-breaking-news-pill" class="fixed top-4 right-4 z-[101] bg-zinc-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-800 px-3 py-1.5 cursor-pointer hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors duration-200 select-none group" style="display: none;">
			<div class="flex items-center gap-2 font-mono text-[11px] font-bold">
				<span class="w-2 h-2 rounded-full animate-pulse" style="background-color: <?php echo esc_attr( $badge_color ); ?>;"></span>
				<span style="color: <?php echo esc_attr( $badge_color ); ?>;"><?php echo esc_html( $label ); ?></span>
				<span class="text-zinc-500 group-hover:text-zinc-700 dark:group-hover:text-zinc-300 ml-1">&#9660;</span>
			</div>
		</div>
	<?php endif; ?>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const banner = document.getElementById('ntn-breaking-news-banner');
			const closeBtn = document.getElementById('ntn-bn-close');
			const pill = document.getElementById('ntn-breaking-news-pill');
			const isDismissable = <?php echo $dismissable ? 'true' : 'false'; ?>;
			const isSticky = <?php echo $sticky ? 'true' : 'false'; ?>;
			const sessionKey = 'ntn_breaking_news_dismissed';
			
			if ( banner ) {
				// Controlla stato salvato
				if ( isDismissable && sessionStorage.getItem(sessionKey) === 'true' ) {
					// L'utente ha chiuso il banner, nascondiamolo
					banner.style.display = 'none';
					if (pill) pill.style.display = 'block';
				} else {
					// Mostra il banner (già visibile di default)
					banner.style.display = 'block';
				}
				
				// Gestione Sticky con JS
				if ( isSticky ) {
					const placeholder = document.createElement('div');
					placeholder.style.display = 'none';
					placeholder.className = 'ntn-breaking-news-placeholder';
					banner.parentNode.insertBefore(placeholder, banner);

					let stickyPos = null;
					let ticking = false;

					window.addEventListener('scroll', function() {
						if (!ticking) {
							window.requestAnimationFrame(function() {
								if (banner.style.display === 'none') {
									ticking = false;
									return;
								}
								
								if (!banner.classList.contains('fixed')) {
									// Registra la posizione prima di fissarlo
									stickyPos = banner.getBoundingClientRect().top + window.scrollY;
								}
								
								if (stickyPos !== null && window.scrollY >= stickyPos) {
									if (!banner.classList.contains('fixed')) {
										banner.classList.add('fixed', 'top-0', 'left-0', 'right-0', 'w-full', '');
										banner.classList.remove('relative');
										placeholder.style.height = banner.offsetHeight + 'px';
										placeholder.style.display = 'block';
									}
								} else {
									if (banner.classList.contains('fixed')) {
										banner.classList.remove('fixed', 'top-0', 'left-0', 'right-0', 'w-full', '');
										banner.classList.add('relative');
										placeholder.style.display = 'none';
										placeholder.style.height = '0px';
									}
								}
								ticking = false;
							});
							ticking = true;
						}
					});
				}

				// Evento Click su [X]
				if ( closeBtn && isDismissable ) {
					closeBtn.addEventListener('click', function() {
						banner.style.display = 'none';
						if (pill) pill.style.display = 'block';
						sessionStorage.setItem(sessionKey, 'true');
					});
				}
				
				// Evento Click su Pillola per riaprire
				if ( pill && isDismissable ) {
					pill.addEventListener('click', function() {
						pill.style.display = 'none';
						banner.style.display = 'block';
						sessionStorage.setItem(sessionKey, 'false');
					});
				}
			}
		});
	</script>
	<?php
}
add_action( 'ntn_journal_after_top_banner', 'ntn_journal_render_breaking_news' );
