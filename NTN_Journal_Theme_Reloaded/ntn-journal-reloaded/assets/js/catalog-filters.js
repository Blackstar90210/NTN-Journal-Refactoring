document.addEventListener('DOMContentLoaded', function() {
	const grid = document.getElementById('ntn-catalog-grid');
	const sentinel = document.getElementById('ntn-catalog-sentinel');
	if (!grid || !sentinel) return;

	const filterFranchise = document.getElementById('filter_franchise');
	const filterDeveloper = document.getElementById('filter_developer');
	const filterPublisher = document.getElementById('filter_publisher');
	const filterEngine    = document.getElementById('filter_engine');
	const filterYear      = document.getElementById('filter_year');
	
	// Desktop and mobile search inputs
	const filterSearchDesktop = document.getElementById('filter_search');
	const filterSearchMobile  = document.getElementById('mobile_filter_search');
	let filterSearch = window.innerWidth < 768 ? filterSearchMobile : filterSearchDesktop;
	
	const filterSort      = document.getElementById('filter_sort');
	const resetBtnContainer = document.getElementById('ntn-reset-filters-container');
	const resetBtn        = document.getElementById('ntn-reset-filters');
	
	// Mobile toggle logic
	const mobileToggle = document.getElementById('ntn-mobile-filter-toggle');
	const filtersContainer = document.getElementById('ntn-filters-container');
	if (mobileToggle && filtersContainer) {
		mobileToggle.addEventListener('click', () => {
			filtersContainer.classList.toggle('hidden');
			filtersContainer.classList.toggle('flex');
			mobileToggle.classList.toggle('is-open');
		});
	}
	
	// Sync mobile and desktop search
	if (filterSearchDesktop && filterSearchMobile) {
		filterSearchDesktop.addEventListener('input', (e) => { filterSearchMobile.value = e.target.value; filterSearch = filterSearchDesktop; });
		filterSearchMobile.addEventListener('input', (e) => { filterSearchDesktop.value = e.target.value; filterSearch = filterSearchMobile; });
	}

	let currentPage = 1;
	let maxPages    = window.ntn_ssr_max_pages || 1;
	let isLoading   = false;
	
	// Intersection Observer per Infinite Scroll
	const observer = new IntersectionObserver((entries) => {
		if (entries[0].isIntersecting && !isLoading && currentPage < maxPages) {
			currentPage++;
			fetchCatalog(true);
		}
	}, {
		rootMargin: '200px',
	});

	if (currentPage < maxPages) {
		observer.observe(sentinel);
	}
	
	function getFilterParams() {
		const params = new URLSearchParams();
		if (filterFranchise && filterFranchise.value && filterFranchise.value !== '0') params.set('franchise', filterFranchise.value);
		if (filterDeveloper && filterDeveloper.value && filterDeveloper.value !== '0') params.set('developer', filterDeveloper.value);
		if (filterPublisher && filterPublisher.value && filterPublisher.value !== '0') params.set('publisher', filterPublisher.value);
		if (filterEngine    && filterEngine.value && filterEngine.value !== '0')    params.set('engine',    filterEngine.value);
		if (filterYear      && filterYear.value.trim() !== '') params.set('year',     filterYear.value.trim());
		if (filterSearch    && filterSearch.value.trim() !== '') params.set('search', filterSearch.value.trim());
		if (filterSort      && filterSort.value && filterSort.value !== 'year_desc') params.set('sort',     filterSort.value);
		return params;
	}

	function updateURL(params) {
		const url = new URL(window.location.href);
		url.search = params.toString();
		window.history.pushState(Object.fromEntries(params), '', url.toString());
		
		// Gestione bottone Reset e dot notifica
		const hasFilters = Array.from(params.keys()).length > 0;
		if (resetBtnContainer) {
			hasFilters ? resetBtnContainer.classList.remove('hidden') : resetBtnContainer.classList.add('hidden');
		}
		if (mobileToggle) {
			const indicator = mobileToggle.querySelector('.ntn-active-filter-indicator');
			if (hasFilters && !indicator) {
				mobileToggle.insertAdjacentHTML('beforeend', '<div class="ntn-active-filter-indicator w-1.5 h-1.5 rounded-full bg-red-600 ml-1"></div>');
			} else if (!hasFilters && indicator) {
				indicator.remove();
			}
		}
	}
	
	function generateSingleLoader() {
		return `
		<div class="col-span-full flex items-center justify-center w-full min-h-[40vh] transition-opacity duration-300">
			<div class="flex gap-2.5">
				<div class="w-3.5 h-3.5 bg-zinc-400 dark:bg-white animate-pulse" style="animation-delay: 0ms"></div>
				<div class="w-3.5 h-3.5 bg-zinc-400 dark:bg-white animate-pulse" style="animation-delay: 150ms"></div>
				<div class="w-3.5 h-3.5 bg-zinc-400 dark:bg-white animate-pulse" style="animation-delay: 300ms"></div>
				<div class="w-3.5 h-3.5 bg-red-600 animate-pulse" style="animation-delay: 450ms"></div>
				<div class="w-3.5 h-3.5 bg-zinc-300 dark:bg-zinc-950 animate-pulse" style="animation-delay: 600ms"></div>
			</div>
		</div>
		`;
	}

	function fetchCatalog(append = false) {
		if (isLoading) return;
		isLoading = true;
		
		const loader = sentinel.querySelector('.ntn-loader');
		if (loader && append) loader.classList.remove('hidden');

		const params = getFilterParams();
		let transitionPromise = Promise.resolve();

		if (!append) {
			currentPage = 1;
			updateURL(params);
			
			// Lock grid height to prevent Layout Shift
			const currentHeight = grid.clientHeight;
			if (currentHeight > 0) {
				grid.style.minHeight = currentHeight + 'px';
			}
			
			if (document.startViewTransition) {
				transitionPromise = new Promise(resolve => {
					const transition = document.startViewTransition(() => {
						grid.innerHTML = generateSingleLoader();
					});
					transition.ready.then(resolve);
				});
			} else {
				grid.innerHTML = generateSingleLoader();
			}
		}

		params.set('page', currentPage);
		
		if (typeof ntn_catalog_vars === 'undefined' || !ntn_catalog_vars.rest_url) {
			isLoading = false;
			return;
		}

		const fetchUrl = new URL(ntn_catalog_vars.rest_url);
		fetchUrl.search = params.toString();

		fetch(fetchUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': ntn_catalog_vars.rest_nonce || ''
			}
		})
		.then(response => {
			if (!response.ok) throw new Error('Network response was not ok');
			return response.json();
		})
		.then(async res => {
			if (res.success) {
				maxPages = res.data.max_pages;
				await transitionPromise; // Wait for outgoing transition to finish starting
				
				if (append) {
					grid.insertAdjacentHTML('beforeend', res.data.html);
				} else {
					grid.style.minHeight = ''; // Remove lock
					
					if (document.startViewTransition) {
						document.startViewTransition(() => {
							grid.innerHTML = res.data.html;
						});
					} else {
						grid.innerHTML = res.data.html;
					}
				}
				
				observer.disconnect();
				if (currentPage < maxPages) {
					observer.observe(sentinel);
				}
			}
		})
		.catch(() => {
			// Fail-safe handling for network errors
		})
		.finally(() => {
			isLoading = false;
			if (loader) loader.classList.add('hidden');
		});
	}

	// Listener filtri
	const selectFilters = [filterFranchise, filterDeveloper, filterPublisher, filterEngine, filterSort];
	selectFilters.forEach(filter => {
		if (filter) {
			filter.addEventListener('change', () => { fetchCatalog(false); });
		}
	});

	let textTimeout;
	[filterSearchDesktop, filterSearchMobile, filterYear].forEach(input => {
		if (input) {
			input.addEventListener('input', () => {
				clearTimeout(textTimeout);
				textTimeout = setTimeout(() => fetchCatalog(false), 400); // 400ms debounce come da direttiva
			});
		}
	});

	// Reset button
	if (resetBtn) {
		resetBtn.addEventListener('click', (e) => {
			e.preventDefault();
			if (filterFranchise) filterFranchise.value = '0';
			if (filterDeveloper) filterDeveloper.value = '0';
			if (filterPublisher) filterPublisher.value = '0';
			if (filterEngine)    filterEngine.value = '0';
			if (filterYear)      filterYear.value = '';
			if (filterSearchDesktop) filterSearchDesktop.value = '';
			if (filterSearchMobile) filterSearchMobile.value = '';
			if (filterSort)      filterSort.value = 'year_desc';
			fetchCatalog(false);
		});
	}

	// Popstate (Back/Forward)
	window.addEventListener('popstate', (e) => {
		const urlParams = new URLSearchParams(window.location.search);
		if (filterFranchise) filterFranchise.value = urlParams.get('franchise') || '0';
		if (filterDeveloper) filterDeveloper.value = urlParams.get('developer') || '0';
		if (filterPublisher) filterPublisher.value = urlParams.get('publisher') || '0';
		if (filterEngine)    filterEngine.value = urlParams.get('engine') || '0';
		if (filterYear)      filterYear.value = urlParams.get('year') || '';
		const searchVal = urlParams.get('search') || '';
		if (filterSearchDesktop) filterSearchDesktop.value = searchVal;
		if (filterSearchMobile) filterSearchMobile.value = searchVal;
		if (filterSort)      filterSort.value = urlParams.get('sort') || 'year_desc';
		
		fetchCatalog(false);
	});
	
	// SSR gestisce il caricamento iniziale: NIENTE FETCH RIDONDANTE QUI.
});
