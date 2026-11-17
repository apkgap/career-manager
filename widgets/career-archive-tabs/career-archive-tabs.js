/**
 * Elementor Frontend Handler for Career Archive Tabs Widget.
 */
( function () {
	'use strict';

	const initWidget = ( $scope ) => {
		const root = $scope && $scope[0] ? $scope[0].querySelector( '.career-tabs-instance' ) : null;
		if ( ! root ) {
			return;
		}
		if ( typeof window.careerInitTabsInstance === 'function' ) {
			window.careerInitTabsInstance( root );
		}
	};

	window.addEventListener( 'elementor/frontend/init', () => {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/career-archive-tabs.default',
				initWidget
			);
		}
	} );
} )();
