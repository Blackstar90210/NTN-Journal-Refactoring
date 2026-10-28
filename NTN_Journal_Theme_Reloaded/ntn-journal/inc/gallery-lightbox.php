<?php
/**
 * Supporto per Gallerie e Lightbox
 * Carica gli asset condizionalmente e inizializza GLightbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue GLightbox assets only if core/gallery or core/image are present
 */
function ntn_enqueue_gallery_lightbox() {
	if ( ( is_singular() && ( has_block( 'core/gallery' ) || has_block( 'core/image' ) ) ) || is_tag() || is_singular( 'videogioco' ) ) {
		
		// Enqueue CSS
		wp_enqueue_style( 'glightbox', get_template_directory_uri() . '/assets/css/glightbox.min.css', array(), '3.3.0' );
		
		// Enqueue JS
		wp_enqueue_script( 'glightbox', get_template_directory_uri() . '/assets/js/glightbox.min.js', array(), '3.3.0', true );

		// In-line JS per inizializzare il lightbox automaticamente
		wp_add_inline_script( 'glightbox', "
			document.addEventListener('DOMContentLoaded', function() {
				var images = document.querySelectorAll('.wp-block-gallery img, .wp-block-image img');
				var manualGlightbox = document.querySelectorAll('.glightbox');
				if (images.length === 0 && manualGlightbox.length === 0) return;

				var getLargestUrl = function(img) {
					var srcset = img.getAttribute('srcset');
					if (srcset) {
						var sources = srcset.split(',').map(function(s) {
							var parts = s.trim().split(' ');
							return { url: parts[0], width: parseInt(parts[1], 10) || 0 };
						});
						sources.sort(function(a, b) { return b.width - a.width; });
						return sources[0].url;
					}
					return img.getAttribute('src');
				};

				var galleries = document.querySelectorAll('.wp-block-gallery');
				galleries.forEach(function(gal, i) {
					gal.setAttribute('data-gallery-id', 'gallery-' + i);
				});

				images.forEach(function(img) {
					// Ignora le immagini all all'interno di un link custom che non punta a un media
					var parent = img.closest('a');
					var isLink = parent !== null;
					var isImageLink = isLink && parent.href.match(/\.(jpeg|jpg|gif|png|webp|svg|avif)(\?.*)?$/i);
					
					// Se l'immagine è avvolta da un link che non porta ad un'immagine, ignoriamo il lightbox
					if (isLink && !isImageLink) {
						return;
					}

					var targetUrl = isImageLink ? parent.href : getLargestUrl(img);
					var gallery = img.closest('.wp-block-gallery');
					var galleryId = gallery ? gallery.getAttribute('data-gallery-id') : 'image-' + Math.random().toString(36).substr(2, 9);

					// Determinazione dell'etichetta accessibile (WCAG / WebMCP Agentic Navigation)
					var altText = (img.getAttribute('alt') || '').trim();
					var figure = img.closest('figure');
					var figcaption = figure ? figure.querySelector('figcaption') : null;
					var captionText = figcaption ? figcaption.textContent.trim() : '';

					if (!captionText && gallery) {
						var galCaption = gallery.querySelector(':scope > figcaption');
						if (galCaption) {
							captionText = galCaption.textContent.trim();
						}
					}

					var desc = altText || captionText || (img.getAttribute('title') || '').trim();
					var accessibleLabel = desc ? ('Visualizza immagine ingrandita: ' + desc) : 'Visualizza immagine ingrandita';

					if (isImageLink) {
						parent.classList.add('glightbox');
						parent.setAttribute('data-gallery', galleryId);
						if (!parent.getAttribute('aria-label')) {
							parent.setAttribute('aria-label', accessibleLabel);
						}
					} else {
						var a = document.createElement('a');
						a.href = targetUrl;
						a.classList.add('glightbox');
						a.setAttribute('data-gallery', galleryId);
						a.setAttribute('aria-label', accessibleLabel);
						
						img.parentNode.insertBefore(a, img);
						a.appendChild(img);
					}
				});

				if (typeof GLightbox !== 'undefined') {
					GLightbox({
						selector: '.glightbox',
						touchNavigation: true,
						loop: true,
						zoomable: true
					});
				}
			});
		" );
	}
}
add_action( 'wp_enqueue_scripts', 'ntn_enqueue_gallery_lightbox' );

/**
 * Assicura che i link alle immagini nelle gallerie o blocchi immagine abbiano un aria-label accessibile lato server
 *
 * @param string $block_content Contenuto HTML del blocco.
 * @param array  $block         Dati del blocco analizzato.
 * @return string
 */
function ntn_filter_accessible_gallery_links( $block_content, $block ) {
	if ( empty( $block_content ) || ! is_string( $block_content ) ) {
		return $block_content;
	}

	if ( isset( $block['blockName'] ) && in_array( $block['blockName'], array( 'core/gallery', 'core/image' ), true ) ) {
		if ( preg_match( '/<a\s+([^>]*?)href=[\'"]([^\'"]+\.(?:jpe?g|png|gif|webp|svg|avif)(?:\?[^\'"]*)?)[\'"]([^>]*?)>/i', $block_content ) ) {
			$block_content = preg_replace_callback(
				'/<a\s+([^>]*?)href=[\'"]([^\'"]+\.(?:jpe?g|png|gif|webp|svg|avif)(?:\?[^\'"]*)?)[\'"]([^>]*?)>(.*?<\/a>)/is',
				function( $matches ) {
					$before = $matches[1];
					$url    = $matches[2];
					$after  = $matches[3];
					$inner  = $matches[4];

					if ( strpos( $before, 'aria-label' ) !== false || strpos( $after, 'aria-label' ) !== false ) {
						return $matches[0];
					}

					$alt = '';
					if ( preg_match( '/alt=[\'"]([^\'"]+)[\'"]/i', $inner, $m_alt ) ) {
						$alt = trim( $m_alt[1] );
					}

					$label = ! empty( $alt ) ? sprintf( 'Visualizza immagine ingrandita: %s', esc_attr( $alt ) ) : 'Visualizza immagine ingrandita';
					return '<a ' . $before . 'href="' . esc_url( $url ) . '"' . $after . ' aria-label="' . esc_attr( $label ) . '">' . $inner;
				},
				$block_content
			);
		}
	}

	return $block_content;
}
add_filter( 'render_block', 'ntn_filter_accessible_gallery_links', 10, 2 );
