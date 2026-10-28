document.addEventListener('DOMContentLoaded', function() {
    const lazyVideos = document.querySelectorAll('.ntn-lazy-video');
    
    lazyVideos.forEach(function(videoContainer) {
        if (!videoContainer.hasAttribute('tabindex')) {
            videoContainer.setAttribute('tabindex', '0');
        }
        if (!videoContainer.hasAttribute('role')) {
            videoContainer.setAttribute('role', 'button');
        }
        if (!videoContainer.hasAttribute('aria-label')) {
            videoContainer.setAttribute('aria-label', 'Riproduci Video Trailer');
        }

        function playVideo() {
            const videoId = videoContainer.getAttribute('data-video-id');
            if (videoId) {
                const iframe = document.createElement('iframe');
                iframe.setAttribute('src', 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0');
                iframe.setAttribute('title', 'Video Trailer YouTube');
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allowfullscreen', '1');
                iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                iframe.className = 'absolute top-0 left-0 w-full h-full';
                
                videoContainer.innerHTML = ''; // Rimuovi il bottone di play e il placeholder
                videoContainer.removeAttribute('role');
                videoContainer.removeAttribute('tabindex');
                videoContainer.removeAttribute('aria-label');
                videoContainer.appendChild(iframe);
            }
        }

        videoContainer.addEventListener('click', playVideo);
        videoContainer.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                playVideo();
            }
        });
    });
});
