/**
 * Career Manager Plugin — Standalone Frontend Controller
 *
 * Powers:
 * 1. Interactive 2-Column Job Board (Keyword Search + Multi-Checkbox Filters for Category, Location, Employment Type + Clear Filter + Pagination)
 * 2. Universal PDF Preview Modal (`[data-pdf-preview]`, `.career-pdf-preview-btn`)
 * 3. Copy Share Link Button (`.js-career-copy-link`)
 */
( function () {
	'use strict';

	/**
	 * Initialize a single `.career-tabs-instance` container.
	 *
	 * @param {HTMLElement} root
	 */
	function initTabsInstance( root ) {
		if ( ! root || root.dataset.careerInitialized === 'true' ) {
			return;
		}
		root.dataset.careerInitialized = 'true';

		let allJobs = [];
		try {
			const raw = root.dataset.jobs || '[]';
			const parsed = JSON.parse( raw );
			allJobs = Array.isArray( parsed ) ? parsed : [];
		} catch ( e ) {
			allJobs = [];
		}

		const postsPerPage = parseInt( root.dataset.postsPerPage || '10', 10 ) || 10;
		let searchQuery = '';
		let currentPage = 1;

		const selectedFilters = {
			categories: [],
			locations: [],
			types: [],
		};

		const searchInput = root.querySelector( '.js-career-search-input' );
		const clearBtn = root.querySelector( '.js-career-clear-filters' );
		const checkboxes = Array.from( root.querySelectorAll( '.js-career-filter-checkbox' ) );
		const skeletonEl = root.querySelector( '.js-career-skeleton' );
		const cardListEl = root.querySelector( '.js-career-card-list' );
		const emptyStateEl = root.querySelector( '.js-career-empty-state' );
		const paginationBar = root.querySelector( '.js-career-pagination-bar' );

		const btnFirst = root.querySelector( '.js-career-page-first' );
		const btnPrev = root.querySelector( '.js-career-page-prev' );
		const btnNext = root.querySelector( '.js-career-page-next' );
		const btnLast = root.querySelector( '.js-career-page-last' );
		const pageInput = root.querySelector( '.js-career-page-input' );
		const totalPagesEl = root.querySelector( '.js-career-total-pages' );
		const startItemEl = root.querySelector( '.js-career-start-item' );
		const endItemEl = root.querySelector( '.js-career-end-item' );
		const totalItemsEls = Array.from( root.querySelectorAll( '.js-career-total-items' ) );

		const filterPanel = root.querySelector( '.js-career-filter-panel' );
		const filterBackdrop = root.querySelector( '.js-career-filter-backdrop' );
		const openFilterBtn = root.querySelector( '.js-career-open-filter-modal' );
		const closeFilterBtns = Array.from( root.querySelectorAll( '.js-career-close-filter-modal' ) );
		const filterBadgeEl = root.querySelector( '.js-career-filter-badge' );

		function openMobileFilterModal() {
			if ( ! filterPanel ) {
				return;
			}
			filterPanel.classList.add( 'is-mobile-open' );
			if ( filterBackdrop ) {
				filterBackdrop.classList.add( 'is-mobile-open' );
			}
			if ( openFilterBtn ) {
				openFilterBtn.setAttribute( 'aria-expanded', 'true' );
			}
			document.body.style.overflow = 'hidden';
		}

		function closeMobileFilterModal() {
			if ( ! filterPanel ) {
				return;
			}
			filterPanel.classList.remove( 'is-mobile-open' );
			if ( filterBackdrop ) {
				filterBackdrop.classList.remove( 'is-mobile-open' );
			}
			if ( openFilterBtn ) {
				openFilterBtn.setAttribute( 'aria-expanded', 'false' );
			}
			document.body.style.overflow = '';
		}

		function updateActiveFilterBadge() {
			if ( ! filterBadgeEl ) {
				return;
			}
			const activeCount =
				selectedFilters.categories.length +
				selectedFilters.locations.length +
				selectedFilters.types.length +
				( ( searchQuery || '' ).trim() ? 1 : 0 );

			filterBadgeEl.textContent = String( activeCount );
			filterBadgeEl.style.display = activeCount > 0 ? 'inline-flex' : 'none';
		}

		function syncCheckedState() {
			selectedFilters.categories = [];
			selectedFilters.locations = [];
			selectedFilters.types = [];

			checkboxes.forEach( function ( cb ) {
				if ( cb.checked ) {
					const group = cb.getAttribute( 'data-filter-group' );
					const val = cb.value;
					if ( group && selectedFilters[ group ] && val ) {
						selectedFilters[ group ].push( val );
					}
				}
			} );
		}

		function hasIntersection( itemArr, selectedArr ) {
			if ( ! selectedArr || selectedArr.length === 0 ) {
				return true;
			}
			if ( ! Array.isArray( itemArr ) || itemArr.length === 0 ) {
				return false;
			}
			return selectedArr.some( function ( slug ) {
				return itemArr.indexOf( slug ) !== -1;
			} );
		}

		function getFilteredJobs() {
			const q = ( searchQuery || '' ).trim().toLowerCase();

			return allJobs.filter( function ( job ) {
				const matchSearch = ! q || ( job.title || '' ).toLowerCase().indexOf( q ) !== -1;
				const matchCat = hasIntersection( job.categories, selectedFilters.categories );
				const matchLoc = hasIntersection( job.locations, selectedFilters.locations );
				const matchType = hasIntersection( job.types, selectedFilters.types );

				return matchSearch && matchCat && matchLoc && matchType;
			} );
		}

		function renderState( withSkeleton ) {
			const filtered = getFilteredJobs();
			const totalItems = filtered.length;
			const totalPages = totalItems === 0 ? 1 : Math.ceil( totalItems / postsPerPage );

			if ( currentPage > totalPages ) {
				currentPage = totalPages;
			}
			if ( currentPage < 1 ) {
				currentPage = 1;
			}

			const startIdx = ( currentPage - 1 ) * postsPerPage;
			const paginated = filtered.slice( startIdx, startIdx + postsPerPage );

			const updateDom = function () {
				if ( skeletonEl ) {
					skeletonEl.style.display = 'none';
				}
				if ( cardListEl ) {
					cardListEl.innerHTML = paginated
						.map( function ( item ) {
							return item.html || '';
						} )
						.join( '' );
					cardListEl.style.display = totalItems > 0 ? 'flex' : 'none';
				}
				if ( emptyStateEl ) {
					emptyStateEl.style.display = totalItems === 0 ? 'flex' : 'none';
				}
				if ( paginationBar ) {
					paginationBar.style.display = totalPages > 1 ? 'flex' : 'none';
				}
				if ( pageInput ) {
					pageInput.value = String( currentPage );
					pageInput.max = String( totalPages );
				}
				if ( totalPagesEl ) {
					totalPagesEl.textContent = String( totalPages );
				}
				if ( startItemEl ) {
					startItemEl.textContent = String( totalItems === 0 ? 0 : startIdx + 1 );
				}
				if ( endItemEl ) {
					endItemEl.textContent = String( Math.min( currentPage * postsPerPage, totalItems ) );
				}
				if ( totalItemsEls.length ) {
					totalItemsEls.forEach( function ( el ) {
						el.textContent = String( totalItems );
					} );
				}
				updateActiveFilterBadge();
				if ( btnFirst ) {
					btnFirst.disabled = currentPage <= 1;
				}
				if ( btnPrev ) {
					btnPrev.disabled = currentPage <= 1;
				}
				if ( btnNext ) {
					btnNext.disabled = currentPage >= totalPages;
				}
				if ( btnLast ) {
					btnLast.disabled = currentPage >= totalPages;
				}
			};

			if ( withSkeleton && skeletonEl && cardListEl ) {
				cardListEl.style.display = 'none';
				if ( emptyStateEl ) {
					emptyStateEl.style.display = 'none';
				}
				skeletonEl.style.display = 'flex';
				setTimeout( updateDom, 180 );
			} else {
				updateDom();
			}
		}

		checkboxes.forEach( function ( cb ) {
			cb.addEventListener( 'change', function () {
				syncCheckedState();
				currentPage = 1;
				renderState( true );
			} );
		} );

		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				checkboxes.forEach( function ( cb ) {
					cb.checked = false;
				} );
				if ( searchInput ) {
					searchInput.value = '';
				}
				searchQuery = '';
				syncCheckedState();
				currentPage = 1;
				renderState( true );
			} );
		}

		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				searchQuery = searchInput.value || '';
				currentPage = 1;
				renderState( false );
			} );
		}

		if ( btnFirst ) {
			btnFirst.addEventListener( 'click', function () {
				if ( currentPage > 1 ) {
					currentPage = 1;
					renderState( false );
				}
			} );
		}

		if ( btnPrev ) {
			btnPrev.addEventListener( 'click', function () {
				if ( currentPage > 1 ) {
					currentPage -= 1;
					renderState( false );
				}
			} );
		}

		if ( btnNext ) {
			btnNext.addEventListener( 'click', function () {
				currentPage += 1;
				renderState( false );
			} );
		}

		if ( btnLast ) {
			btnLast.addEventListener( 'click', function () {
				const totalPages = Math.max( 1, Math.ceil( getFilteredJobs().length / postsPerPage ) );
				currentPage = totalPages;
				renderState( false );
			} );
		}

		if ( pageInput ) {
			pageInput.addEventListener( 'change', function () {
				const val = parseInt( pageInput.value || '1', 10 );
				if ( ! isNaN( val ) ) {
					currentPage = val;
					renderState( false );
				}
			} );
		}

		if ( openFilterBtn ) {
			openFilterBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				openMobileFilterModal();
			} );
		}

		closeFilterBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				closeMobileFilterModal();
			} );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && filterPanel && filterPanel.classList.contains( 'is-mobile-open' ) ) {
				closeMobileFilterModal();
			}
		} );
	}

	window.careerInitTabsInstance = initTabsInstance;

	function initAll() {
		document.querySelectorAll( '.career-tabs-instance' ).forEach( initTabsInstance );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	// Copy Link Handler
	document.addEventListener( 'click', function ( e ) {
		const copyBtn = e.target.closest( '.js-career-copy-link' );
		if ( copyBtn ) {
			e.preventDefault();
			const url = copyBtn.getAttribute( 'data-copy-url' ) || window.location.href;
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url );
			}
			const spanEl = copyBtn.querySelector( 'span' );
			if ( spanEl ) {
				const prevText = spanEl.textContent;
				copyBtn.classList.add( 'is-copied' );
				spanEl.textContent = 'คัดลอกลิงก์เรียบร้อยแล้ว ✓';
				setTimeout( function () {
					spanEl.textContent = prevText;
					copyBtn.classList.remove( 'is-copied' );
				}, 2200 );
			}
		}
	} );

	// Apply Now Application Modal & AJAX Submission Handler
	document.addEventListener( 'DOMContentLoaded', function () {
		const applyModal = document.getElementById( 'career-apply-modal' );
		const applyForm = document.getElementById( 'career-apply-form' );
		const alertBox = document.getElementById( 'career-apply-alert' );
		const submitBtn = document.getElementById( 'career-apply-submit-btn' );
		const submitText = document.getElementById( 'career-apply-submit-text' );

		if ( ! applyModal || ! applyForm ) {
			return;
		}

		function openApplyModal() {
			alertBox.classList.add( 'is-hidden' );
			alertBox.classList.remove( 'is-error', 'is-success' );
			alertBox.textContent = '';
			applyModal.classList.remove( 'is-hidden' );
			document.body.style.overflow = 'hidden';
		}

		function closeApplyModal() {
			applyModal.classList.add( 'is-hidden' );
			document.body.style.overflow = '';
		}

		document.addEventListener( 'click', function ( e ) {
			const openTrigger = e.target.closest( '.js-career-open-apply-modal' );
			if ( openTrigger ) {
				e.preventDefault();
				openApplyModal();
				return;
			}

			const closeTrigger = e.target.closest( '.js-career-close-apply-modal' );
			if ( closeTrigger ) {
				e.preventDefault();
				closeApplyModal();
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! applyModal.classList.contains( 'is-hidden' ) ) {
				closeApplyModal();
			}
		} );

		applyForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( submitBtn && submitBtn.disabled ) {
				return;
			}

			alertBox.classList.add( 'is-hidden' );
			alertBox.classList.remove( 'is-error', 'is-success' );

			const originalBtnLabel = submitText ? submitText.textContent : 'Submit Application';
			if ( submitBtn ) {
				submitBtn.disabled = true;
			}
			if ( submitText ) {
				submitText.textContent = 'กำลังส่งข้อมูล... (Submitting...)';
			}

			const formData = new FormData( applyForm );
			const ajaxUrl =
				window.careerManagerConfig && window.careerManagerConfig.ajaxUrl
					? window.careerManagerConfig.ajaxUrl
					: '/wp-admin/admin-ajax.php';

			fetch( ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			} )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( res ) {
					if ( res && res.success ) {
						alertBox.textContent =
							( res.data && res.data.message ) ||
							'ส่งใบสมัครงานเรียบร้อยแล้ว!';
						alertBox.classList.add( 'is-success' );
						alertBox.classList.remove( 'is-hidden' );
						applyForm.reset();

						setTimeout( function () {
							closeApplyModal();
						}, 2200 );
					} else {
						const errMsg =
							( res && res.data && res.data.message ) ||
							'เกิดข้อผิดพลาด กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง';
						alertBox.textContent = errMsg;
						alertBox.classList.add( 'is-error' );
						alertBox.classList.remove( 'is-hidden' );
					}
				} )
				.catch( function () {
					alertBox.textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง';
					alertBox.classList.add( 'is-error' );
					alertBox.classList.remove( 'is-hidden' );
				} )
				.finally( function () {
					if ( submitBtn ) {
						submitBtn.disabled = false;
					}
					if ( submitText ) {
						submitText.textContent = originalBtnLabel;
					}
				} );
		} );
	} );

	// PDF Modal Handler
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof window.careerOpenPdfPreview === 'function' ) {
			return;
		}

		const modal = document.getElementById( 'career-pdf-preview-modal' );
		const iframe = document.getElementById( 'career-pdf-modal-iframe' );
		const titleEl = document.getElementById( 'career-pdf-modal-title' );
		const subtitleEl = document.getElementById( 'career-pdf-modal-subtitle' );
		const newTabBtn = document.getElementById( 'career-pdf-modal-newtab-btn' );
		const downloadBtn = document.getElementById( 'career-pdf-modal-download-btn' );
		const closeBtn = document.getElementById( 'career-pdf-modal-close-btn' );
		const backdrop = document.getElementById( 'career-pdf-modal-backdrop' );

		if ( ! modal || ! iframe ) {
			return;
		}

		window.careerOpenPdfPreview = function ( url, title ) {
			if ( ! url ) {
				return;
			}
			if ( titleEl ) {
				titleEl.textContent = title || 'เอกสารตัวอย่าง PDF';
			}
			if ( subtitleEl ) {
				subtitleEl.textContent = url.split( '/' ).pop().split( '#' )[ 0 ].split( '?' )[ 0 ] || 'document.pdf';
			}
			if ( newTabBtn ) {
				newTabBtn.href = url;
			}
			if ( downloadBtn ) {
				downloadBtn.href = url;
			}
			iframe.src = url.indexOf( '#' ) === -1 ? url + '#toolbar=1&navpanes=0' : url;
			modal.classList.remove( 'is-hidden' );
			document.body.style.overflow = 'hidden';
		};

		window.careerClosePdfPreview = function () {
			modal.classList.add( 'is-hidden' );
			iframe.src = 'about:blank';
			document.body.style.overflow = '';
		};

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', window.careerClosePdfPreview );
		}
		if ( backdrop ) {
			backdrop.addEventListener( 'click', window.careerClosePdfPreview );
		}

		document.addEventListener( 'click', function ( e ) {
			const trigger = e.target.closest( '[data-pdf-preview], .career-pdf-preview-btn' );
			if ( trigger ) {
				e.preventDefault();
				const pdfUrl = trigger.getAttribute( 'data-pdf-preview' ) || trigger.getAttribute( 'href' );
				const pdfTitle = trigger.getAttribute( 'data-pdf-title' ) || trigger.innerText.trim();
				if ( pdfUrl && pdfUrl !== '#' ) {
					window.careerOpenPdfPreview( pdfUrl, pdfTitle );
				}
			}
		} );
	} );
} )();
