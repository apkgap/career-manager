<?php
/**
 * Shortcodes (`[career_tabs]`, `[career_archive_tabs]`, `[career_download_file]`),
 * Force Download Handler (`?career_download_file=ID`), and Universal PDF Preview Modal.
 *
 * Matches the 2-column Job Board UI:
 * - Left Card: Search input + Filter By (Clear Filter) + Checkbox groups for Category, Location, Employment Type
 * - Right Column: Stacked job cards with Title, optional "Applications closed" bar,
 *   solid Burgundy Category pill + outlined Urgent pill, and Pin Location | Calendar Date meta row.
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Render a single Career Card HTML string (shared by Shortcode, Archive Template, and Widget).
 *
 * @param int $post_id Career post ID.
 * @return string HTML markup.
 */
function career_render_card_html( int $post_id ): string {
	$title           = get_the_title( $post_id );
	$permalink       = (string) get_permalink( $post_id );
	$date            = career_format_date( $post_id, 'slash' );
	$pdf_url         = career_get_primary_pdf_url( $post_id );
	$preview_enabled = career_is_pdf_preview_enabled( $post_id );

	$is_urgent     = '1' === (string) get_post_meta( $post_id, '_career_is_urgent', true );
	$is_app_closed = '1' === (string) get_post_meta( $post_id, '_career_application_closed', true );
	$loc_custom    = trim( (string) get_post_meta( $post_id, '_career_location_custom', true ) );

	$cat_terms  = get_the_terms( $post_id, 'career_category' );
	$loc_terms  = get_the_terms( $post_id, 'career_location' );
	$type_terms = get_the_terms( $post_id, 'career_type' );

	$location_label = $loc_custom;
	if ( '' === $location_label ) {
		if ( ! empty( $loc_terms ) && ! is_wp_error( $loc_terms ) ) {
			$loc_names      = wp_list_pluck( $loc_terms, 'name' );
			$location_label = implode( ', ', $loc_names );
		} else {
			$location_label = 'Not specified';
		}
	}

	$card_classes = 'career-card';
	if ( $is_urgent ) {
		$card_classes .= ' is-urgent';
	}
	if ( $is_app_closed ) {
		$card_classes .= ' is-closed';
	}

	ob_start();
	?>
	<article id="career-card-<?php echo esc_attr( (string) $post_id ); ?>" class="<?php echo esc_attr( $card_classes ); ?>">
		<?php if ( $is_urgent ) : ?>
			<div id="career-card-ribbon-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__urgent-ribbon">
				<span id="career-card-badge-urgent-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__urgent-ribbon-text">
					<?php echo esc_html( career_text( 'ด่วน', 'ด่วน' ) ); ?>
				</span>
			</div>
		<?php endif; ?>

		<div id="career-card-body-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__body">
			<div id="career-card-badges-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__badges">
				<?php if ( ! empty( $cat_terms ) && ! is_wp_error( $cat_terms ) ) : ?>
					<?php foreach ( $cat_terms as $c_term ) : ?>
						<span id="career-card-badge-cat-<?php echo esc_attr( $post_id . '-' . $c_term->term_id ); ?>" class="career-card__badge career-card__badge--category">
							<?php echo esc_html( $c_term->name ); ?>
						</span>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( ! empty( $type_terms ) && ! is_wp_error( $type_terms ) ) : ?>
					<?php foreach ( $type_terms as $t_term ) : ?>
						<span id="career-card-badge-type-<?php echo esc_attr( $post_id . '-' . $t_term->term_id ); ?>" class="career-card__badge career-card__badge--type">
							<?php echo esc_html( $t_term->name ); ?>
						</span>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div id="career-card-top-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__top">
				<h3 id="career-card-title-heading-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__title">
					<a id="career-card-link-<?php echo esc_attr( (string) $post_id ); ?>" href="<?php echo esc_url( $permalink ); ?>" class="career-card__link">
						<?php echo esc_html( $title ); ?>
					</a>
				</h3>

				<?php if ( '' !== $pdf_url && $preview_enabled ) : ?>
					<div id="career-card-actions-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__actions">
						<button id="career-card-pdf-btn-<?php echo esc_attr( (string) $post_id ); ?>"
								type="button"
								class="career-pdf-preview-btn career-card__preview-btn"
								data-pdf-preview="<?php echo esc_url( $pdf_url ); ?>"
								data-pdf-title="<?php echo esc_attr( $title ); ?>">
							<?php echo career_get_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( career_text( 'PDF Preview', 'PDF Preview' ) ); ?></span>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $is_app_closed ) : ?>
				<div id="career-card-closed-bar-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__closed-banner">
					<?php echo esc_html( career_text( 'Applications closed', 'Applications closed' ) ); ?>
				</div>
			<?php endif; ?>

			<div id="career-card-meta-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta">
				<span id="career-card-meta-loc-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta-item">
					<span id="career-card-meta-loc-icon-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta-icon">
						<?php echo career_get_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span id="career-card-meta-loc-text-<?php echo esc_attr( (string) $post_id ); ?>"><?php echo esc_html( $location_label ); ?></span>
				</span>

				<span id="career-card-meta-sep-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta-sep" aria-hidden="true">•</span>

				<span id="career-card-meta-date-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta-item">
					<span id="career-card-meta-date-icon-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta-icon">
						<?php echo career_get_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span id="career-card-meta-date-text-<?php echo esc_attr( (string) $post_id ); ?>"><?php echo esc_html( $date ); ?></span>
				</span>

				<span id="career-card-meta-cta-<?php echo esc_attr( (string) $post_id ); ?>" class="career-card__meta-more">
					<span><?php echo esc_html( career_text( 'ดูรายละเอียด', 'View Details' ) ); ?></span>
					<?php echo career_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</div>
		</div>
	</article>
	<?php
	return (string) preg_replace( '/[\r\n\t]+/', ' ', (string) ob_get_clean() );
}

/**
 * Render the interactive Career Job Board component (`[career_tabs]` / `[career_archive_tabs]`).
 *
 * @param array<string, mixed> $atts Shortcode or widget attributes.
 * @return string HTML markup.
 */
function career_render_tabs_component( array $atts = array() ): string {
	wp_enqueue_style( 'career-manager-frontend' );
	wp_enqueue_script( 'career-manager-frontend' );

	$settings = career_get_settings();
	$defaults = array(
		'title'            => '',
		'posts_per_page'   => (int) ( $settings['posts_per_page'] ?? 10 ),
		'orderby'          => 'date',
		'order'            => 'DESC',
		'view_all_text'    => career_text( 'ดูทั้งหมด', 'View All' ),
		'view_all_url'     => '',
		'tab_active_color' => (string) ( $settings['primary_color'] ?? '#800020' ),
		'instance_id'      => 'sc-' . wp_rand( 1000, 9999 ),
	);

	$args = shortcode_atts( $defaults, $atts, 'career_tabs' );

	$allowed_orderby = array( 'date', 'title', 'menu_order', 'rand', 'ID' );
	$orderby         = in_array( (string) $args['orderby'], $allowed_orderby, true ) ? (string) $args['orderby'] : 'date';
	$order           = in_array( strtoupper( (string) $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( (string) $args['order'] ) : 'DESC';
	$posts_per_page  = max( 1, min( 100, (int) $args['posts_per_page'] ) );
	$widget_id       = sanitize_key( (string) $args['instance_id'] );
	$active_color    = sanitize_hex_color( (string) $args['tab_active_color'] ) ?: '#800020';

	// Query all published career posts
	$all_query = new \WP_Query(
		array(
			'post_type'      => 'career',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => $orderby,
			'order'          => $order,
		)
	);

	$all_posts = array();
	if ( $all_query->have_posts() ) {
		while ( $all_query->have_posts() ) {
			$all_query->the_post();
			$pid = (int) get_the_ID();

			$c_terms = get_the_terms( $pid, 'career_category' );
			$l_terms = get_the_terms( $pid, 'career_location' );
			$t_terms = get_the_terms( $pid, 'career_type' );

			$cat_slugs  = ( ! empty( $c_terms ) && ! is_wp_error( $c_terms ) ) ? array_values( wp_list_pluck( $c_terms, 'slug' ) ) : array();
			$loc_slugs  = ( ! empty( $l_terms ) && ! is_wp_error( $l_terms ) ) ? array_values( wp_list_pluck( $l_terms, 'slug' ) ) : array();
			$type_slugs = ( ! empty( $t_terms ) && ! is_wp_error( $t_terms ) ) ? array_values( wp_list_pluck( $t_terms, 'slug' ) ) : array();

			$all_posts[] = array(
				'id'         => $pid,
				'title'      => get_the_title( $pid ),
				'date'       => get_the_date( 'd/m/Y', $pid ),
				'timestamp'  => get_the_date( 'Y-m-d', $pid ),
				'categories' => $cat_slugs,
				'locations'  => $loc_slugs,
				'types'      => $type_slugs,
				'html'       => career_render_card_html( $pid ),
			);
		}
		wp_reset_postdata();
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'career_category',
			'hide_empty' => false,
		)
	);
	$locations  = get_terms(
		array(
			'taxonomy'   => 'career_location',
			'hide_empty' => false,
		)
	);
	$emp_types  = get_terms(
		array(
			'taxonomy'   => 'career_type',
			'hide_empty' => false,
		)
	);

	$all_posts_json = wp_json_encode( $all_posts );
	if ( ! $all_posts_json ) {
		$all_posts_json = '[]';
	}

	ob_start();
	?>
	<div id="career-tabs-<?php echo esc_attr( $widget_id ); ?>"
		 class="career-board career-tabs career-tabs-instance"
		 style="--career-primary: <?php echo esc_attr( $active_color ); ?>;"
		 data-widget-id="<?php echo esc_attr( $widget_id ); ?>"
		 data-posts-per-page="<?php echo esc_attr( (string) $posts_per_page ); ?>"
		 data-jobs="<?php echo esc_attr( $all_posts_json ); ?>">

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<h2 id="career-tabs-title-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__title">
				<?php echo esc_html( (string) $args['title'] ); ?>
			</h2>
		<?php endif; ?>

		<div id="career-board-layout-<?php echo esc_attr( $widget_id ); ?>" class="career-board__layout">
			<!-- Mobile Popup Backdrop -->
			<div id="career-filter-backdrop-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-backdrop js-career-filter-backdrop js-career-close-filter-modal"></div>

			<!-- Left Sidebar (Desktop) / Popup Modal (Mobile): Search & Checkbox Filters -->
			<aside id="career-filter-panel-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel js-career-filter-panel">
				<!-- Mobile Popup Header -->
				<div id="career-filter-mobile-head-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__mobile-header">
					<div id="career-filter-mobile-title-wrap-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__mobile-title-wrap">
						<?php echo career_get_icon( 'sliders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h3 id="career-filter-mobile-title-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__mobile-title">
							<?php echo esc_html( career_text( 'ค้นหาและกรองตำแหน่งงาน', 'Search & Filter Jobs' ) ); ?>
						</h3>
					</div>
					<button id="career-filter-mobile-close-<?php echo esc_attr( $widget_id ); ?>"
							type="button"
							class="career-filter-panel__mobile-close js-career-close-filter-modal"
							aria-label="<?php esc_attr_e( 'Close Filter', 'career-manager' ); ?>">✕</button>
				</div>

				<div id="career-filter-body-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__body">
					<!-- Search Section -->
					<div id="career-filter-search-sec-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__section">
						<h3 id="career-filter-search-heading-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__heading">
							<?php echo esc_html( career_text( 'Search', 'Search' ) ); ?>
						</h3>
						<div id="career-search-box-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__search-box">
							<span id="career-search-icon-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__search-icon">
								<?php echo career_get_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<input id="career-search-input-<?php echo esc_attr( $widget_id ); ?>"
								   type="text"
								   class="career-filter-panel__search-input js-career-search-input"
								   placeholder="<?php echo esc_attr( career_text( 'Search jobs or keywords', 'Search jobs or keywords' ) ); ?>" />
						</div>
					</div>

					<!-- Filter By Header -->
					<div id="career-filter-header-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__header">
						<h3 id="career-filter-by-heading-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__heading">
							<?php echo esc_html( career_text( 'Filter By', 'Filter By' ) ); ?>
						</h3>
						<button id="career-clear-filter-btn-<?php echo esc_attr( $widget_id ); ?>"
								type="button"
								class="career-filter-panel__clear-btn js-career-clear-filters">
							<?php echo esc_html( career_text( 'Clear Filter', 'Clear Filter' ) ); ?>
						</button>
					</div>

					<!-- Category Group -->
					<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div id="career-filter-group-cat-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__group">
							<h4 id="career-filter-title-cat-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__group-title">
								<?php echo esc_html( career_text( 'Category', 'Category' ) ); ?>
							</h4>
							<div id="career-filter-list-cat-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__checklist">
								<?php foreach ( $categories as $cat ) : ?>
									<label id="career-check-cat-label-<?php echo esc_attr( $widget_id . '-' . $cat->term_id ); ?>" class="career-filter-panel__checkbox-label">
										<input id="career-check-cat-<?php echo esc_attr( $widget_id . '-' . $cat->term_id ); ?>"
											   type="checkbox"
											   class="career-filter-panel__checkbox js-career-filter-checkbox"
											   data-filter-group="categories"
											   value="<?php echo esc_attr( $cat->slug ); ?>" />
										<span><?php echo esc_html( $cat->name ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Location Group -->
					<?php if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
						<div id="career-filter-group-loc-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__group">
							<h4 id="career-filter-title-loc-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__group-title">
								<?php echo esc_html( career_text( 'Location', 'Location' ) ); ?>
							</h4>
							<div id="career-filter-list-loc-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__checklist">
								<?php foreach ( $locations as $loc ) : ?>
									<label id="career-check-loc-label-<?php echo esc_attr( $widget_id . '-' . $loc->term_id ); ?>" class="career-filter-panel__checkbox-label">
										<input id="career-check-loc-<?php echo esc_attr( $widget_id . '-' . $loc->term_id ); ?>"
											   type="checkbox"
											   class="career-filter-panel__checkbox js-career-filter-checkbox"
											   data-filter-group="locations"
											   value="<?php echo esc_attr( $loc->slug ); ?>" />
										<span><?php echo esc_html( $loc->name ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Employment Type Group -->
					<?php if ( ! empty( $emp_types ) && ! is_wp_error( $emp_types ) ) : ?>
						<div id="career-filter-group-type-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__group">
							<h4 id="career-filter-title-type-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__group-title">
								<?php echo esc_html( career_text( 'Employment Type', 'Employment Type' ) ); ?>
							</h4>
							<div id="career-filter-list-type-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__checklist">
								<?php foreach ( $emp_types as $etype ) : ?>
									<label id="career-check-type-label-<?php echo esc_attr( $widget_id . '-' . $etype->term_id ); ?>" class="career-filter-panel__checkbox-label">
										<input id="career-check-type-<?php echo esc_attr( $widget_id . '-' . $etype->term_id ); ?>"
											   type="checkbox"
											   class="career-filter-panel__checkbox js-career-filter-checkbox"
											   data-filter-group="types"
											   value="<?php echo esc_attr( $etype->slug ); ?>" />
										<span><?php echo esc_html( $etype->name ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Mobile Popup Footer -->
				<div id="career-filter-mobile-foot-<?php echo esc_attr( $widget_id ); ?>" class="career-filter-panel__mobile-footer">
					<button id="career-filter-mobile-apply-<?php echo esc_attr( $widget_id ); ?>"
							type="button"
							class="career-filter-panel__mobile-apply js-career-close-filter-modal">
						<span><?php echo esc_html( career_text( 'ดูผลลัพธ์', 'Show Results' ) ); ?> (<span id="career-filter-mobile-count-<?php echo esc_attr( $widget_id ); ?>" class="js-career-total-items"><?php echo esc_html( (string) count( $all_posts ) ); ?></span>)</span>
					</button>
				</div>
			</aside>

			<!-- Right Column: Job Cards List -->
			<div id="career-content-col-<?php echo esc_attr( $widget_id ); ?>" class="career-board__results">
				<!-- Results Toolbar -->
				<div id="career-results-toolbar-<?php echo esc_attr( $widget_id ); ?>" class="career-board__toolbar">
					<div id="career-results-summary-<?php echo esc_attr( $widget_id ); ?>" class="career-board__summary">
						<span id="career-results-badge-<?php echo esc_attr( $widget_id ); ?>" class="career-board__count-badge js-career-total-items"><?php echo esc_html( (string) count( $all_posts ) ); ?></span>
						<span id="career-results-label-<?php echo esc_attr( $widget_id ); ?>" class="career-board__summary-text">
							<?php echo esc_html( career_text( 'ตำแหน่งงานที่เปิดรับ (Open Positions)', 'Open Positions Available' ) ); ?>
						</span>
					</div>

					<button id="career-mobile-filter-btn-<?php echo esc_attr( $widget_id ); ?>"
							type="button"
							class="career-board__filter-trigger js-career-open-filter-modal"
							aria-controls="career-filter-panel-<?php echo esc_attr( $widget_id ); ?>"
							aria-expanded="false">
						<?php echo career_get_icon( 'sliders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( career_text( 'Filter', 'Filter' ) ); ?></span>
						<span id="career-mobile-filter-badge-<?php echo esc_attr( $widget_id ); ?>" class="career-board__filter-badge js-career-filter-badge" style="display: none;">0</span>
					</button>
				</div>

				<!-- Loading Skeleton -->
				<div id="career-loading-skeleton-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__loading-skeleton js-career-skeleton" style="display: none;">
					<?php for ( $s = 1; $s <= 3; $s++ ) : ?>
						<div id="career-skel-card-<?php echo esc_attr( $widget_id . '-' . $s ); ?>" class="career-card-skeleton">
							<div id="career-skel-title-<?php echo esc_attr( $widget_id . '-' . $s ); ?>" class="career-skeleton-bone career-skeleton-bone--title"></div>
							<div id="career-skel-badges-<?php echo esc_attr( $widget_id . '-' . $s ); ?>" class="career-skeleton-bone career-skeleton-bone--btn"></div>
							<div id="career-skel-meta-<?php echo esc_attr( $widget_id . '-' . $s ); ?>" class="career-skeleton-bone career-skeleton-bone--meta"></div>
						</div>
					<?php endfor; ?>
				</div>

				<!-- Server-Side Initial Cards + JS Dynamic Container -->
				<div id="career-card-list-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__card-list js-career-card-list">
					<?php
					$initial_posts = array_slice( $all_posts, 0, $posts_per_page );
					foreach ( $initial_posts as $p_item ) {
						echo $p_item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>

				<!-- Empty State -->
				<div id="career-empty-state-<?php echo esc_attr( $widget_id ); ?>"
					 class="career-tabs__empty-state js-career-empty-state"
					 style="display: <?php echo empty( $all_posts ) ? 'flex' : 'none'; ?>;">
					<div id="career-empty-icon-wrapper-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__empty-icon">
						<?php echo career_get_icon( 'folder-open' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<h3 id="career-empty-title-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__empty-title">
						<?php echo esc_html( career_text( 'ไม่พบตำแหน่งงานที่ตรงกับเงื่อนไข', 'No matching job positions found' ) ); ?>
					</h3>
					<p id="career-empty-desc-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__empty-desc">
						<?php echo esc_html( career_text( 'ลองปรับคำค้นหาหรือกด Clear Filter เพื่อดูตำแหน่งงานทั้งหมด', 'Try adjusting your keywords or click Clear Filter to view all positions.' ) ); ?>
					</p>
				</div>

				<!-- Pagination Controls (shown when total pages > 1) -->
				<?php
				$total_items = count( $all_posts );
				$total_pages = max( 1, (int) ceil( $total_items / $posts_per_page ) );
				$start_item  = $total_items > 0 ? 1 : 0;
				$end_item    = min( $posts_per_page, $total_items );
				?>
				<div id="career-pagination-bar-<?php echo esc_attr( $widget_id ); ?>"
					 class="career-tabs__pagination-bar js-career-pagination-bar"
					 style="display: <?php echo $total_pages > 1 ? 'flex' : 'none'; ?>;">
					<div id="career-pagination-nav-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__pagination-nav">
						<button id="career-pagination-first-<?php echo esc_attr( $widget_id ); ?>" type="button" class="career-tabs__page-btn js-career-page-first" disabled>«</button>
						<button id="career-pagination-prev-<?php echo esc_attr( $widget_id ); ?>" type="button" class="career-tabs__page-btn js-career-page-prev" disabled>‹</button>

						<span id="career-pagination-status-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__page-status">
							<input id="career-pagination-input-<?php echo esc_attr( $widget_id ); ?>" type="number" min="1" max="<?php echo esc_attr( (string) $total_pages ); ?>" value="1" class="career-tabs__page-input js-career-page-input" />
							<span><?php echo esc_html( career_text( 'of', 'of' ) ); ?> <span id="career-pagination-total-<?php echo esc_attr( $widget_id ); ?>" class="js-career-total-pages"><?php echo esc_html( (string) $total_pages ); ?></span></span>
						</span>

						<button id="career-pagination-next-<?php echo esc_attr( $widget_id ); ?>" type="button" class="career-tabs__page-btn js-career-page-next" <?php disabled( $total_pages <= 1 ); ?>>›</button>
						<button id="career-pagination-last-<?php echo esc_attr( $widget_id ); ?>" type="button" class="career-tabs__page-btn js-career-page-last" <?php disabled( $total_pages <= 1 ); ?>>»</button>
					</div>

					<div id="career-pagination-info-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__pagination-info">
						<span id="career-pagination-start-<?php echo esc_attr( $widget_id ); ?>" class="js-career-start-item"><?php echo esc_html( (string) $start_item ); ?></span>-<span id="career-pagination-end-<?php echo esc_attr( $widget_id ); ?>" class="js-career-end-item"><?php echo esc_html( (string) $end_item ); ?></span>
						<?php echo esc_html( career_text( 'of', 'of' ) ); ?>
						<span id="career-pagination-total-items-<?php echo esc_attr( $widget_id ); ?>" class="js-career-total-items"><?php echo esc_html( (string) $total_items ); ?></span>
					</div>
				</div>

				<?php if ( ! empty( $args['view_all_url'] ) ) : ?>
					<div id="career-tabs-footer-<?php echo esc_attr( $widget_id ); ?>" class="career-tabs__footer">
						<a id="career-tabs-footer-link-<?php echo esc_attr( $widget_id ); ?>" href="<?php echo esc_url( (string) $args['view_all_url'] ); ?>" class="career-tabs__footer-btn">
							<span id="career-tabs-footer-text-<?php echo esc_attr( $widget_id ); ?>"><?php echo esc_html( (string) $args['view_all_text'] ); ?></span>
							<span id="career-tabs-footer-icon-<?php echo esc_attr( $widget_id ); ?>" class="career-card__btn-icon">
								<?php echo career_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Shortcode callback for `[career_tabs]` and `[career_archive_tabs]`.
 *
 * @param array<string, mixed>|string $atts Shortcode attributes.
 * @return string
 */
function career_tabs_shortcode( $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	return career_render_tabs_component( $atts );
}
add_shortcode( 'career_tabs', 'career_tabs_shortcode' );
add_shortcode( 'career_archive_tabs', 'career_tabs_shortcode' );

/**
 * Shortcode `[career_download_file]` to display downloadable file attachments & PDF preview for a career post.
 *
 * @param array<string, mixed>|string $atts Shortcode attributes.
 * @return string
 */
function career_download_file_shortcode( $atts = array() ): string {
	wp_enqueue_style( 'career-manager-frontend' );
	wp_enqueue_script( 'career-manager-frontend' );

	$atts = shortcode_atts(
		array( 'id' => 0 ),
		is_array( $atts ) ? $atts : array(),
		'career_download_file'
	);

	$post_id = absint( $atts['id'] ) ?: (int) get_the_ID();
	if ( $post_id <= 0 ) {
		return '';
	}

	$files = get_post_meta( $post_id, '_career_file_attachments', true );
	if ( empty( $files ) || ! is_array( $files ) ) {
		return '';
	}

	$preview_enabled = career_is_pdf_preview_enabled( $post_id );

	ob_start();
	?>
	<div id="career-downloads-box-<?php echo esc_attr( (string) $post_id ); ?>" class="career-downloads-box">
		<h4 id="career-downloads-heading-<?php echo esc_attr( (string) $post_id ); ?>" class="career-downloads-box__heading">
			<span id="career-downloads-icon-<?php echo esc_attr( (string) $post_id ); ?>" class="career-downloads-box__icon">
				<?php echo career_get_icon( 'paperclip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<span><?php echo esc_html( career_text( 'ไฟล์เอกสารประกอบรับสมัครงาน / ดาวน์โหลด (*.pdf, *.zip, *.rar)', 'Job Application Documents & Attachments' ) ); ?></span>
		</h4>

		<div id="career-files-list-<?php echo esc_attr( (string) $post_id ); ?>" class="career-downloads-box__list">
			<?php foreach ( $files as $f_idx => $f ) : ?>
				<?php
				$file_id      = isset( $f['id'] ) ? absint( $f['id'] ) : 0;
				$ext          = strtoupper( isset( $f['ext'] ) ? (string) $f['ext'] : 'FILE' );
				$title        = ! empty( $f['title'] ) ? (string) $f['title'] : career_text( 'ดาวน์โหลดเอกสาร', 'Download File' );
				$direct_url   = $file_id > 0 ? (string) wp_get_attachment_url( $file_id ) : ( isset( $f['url'] ) ? (string) $f['url'] : '' );
				$download_url = $file_id > 0 ? site_url( '/?career_download_file=' . $file_id ) : $direct_url;
				$size         = isset( $f['size'] ) ? (string) $f['size'] : '';
				$is_pdf       = ( 'PDF' === $ext || 1 === preg_match( '/\.pdf($|\?)/i', $direct_url ) );
				$item_key     = $post_id . '-' . $f_idx;
				?>
				<div id="career-file-item-<?php echo esc_attr( $item_key ); ?>" class="career-file-item">
					<div id="career-file-left-<?php echo esc_attr( $item_key ); ?>" class="career-file-item__left">
						<span id="career-file-ext-<?php echo esc_attr( $item_key ); ?>" class="career-file-item__ext <?php echo $is_pdf ? 'is-pdf' : ''; ?>">
							<?php echo esc_html( $ext ); ?>
						</span>
						<div id="career-file-meta-<?php echo esc_attr( $item_key ); ?>" class="career-file-item__meta">
							<span id="career-file-title-<?php echo esc_attr( $item_key ); ?>" class="career-file-item__title"><?php echo esc_html( $title ); ?></span>
							<?php if ( '' !== $size ) : ?>
								<span id="career-file-size-<?php echo esc_attr( $item_key ); ?>" class="career-file-item__size">(<?php echo esc_html( $size ); ?>)</span>
							<?php endif; ?>
						</div>
					</div>

					<div id="career-file-actions-<?php echo esc_attr( $item_key ); ?>" class="career-file-item__actions">
						<?php if ( $is_pdf && '' !== $direct_url && $preview_enabled ) : ?>
							<button id="career-file-preview-btn-<?php echo esc_attr( $item_key ); ?>"
									type="button"
									class="career-pdf-preview-btn career-file-item__btn career-file-item__btn--preview"
									data-pdf-preview="<?php echo esc_url( $direct_url ); ?>"
									data-pdf-title="<?php echo esc_attr( $title ); ?>">
								<?php echo career_get_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html( career_text( 'ดูตัวอย่าง', 'Preview' ) ); ?></span>
							</button>
						<?php endif; ?>

						<a id="career-file-download-btn-<?php echo esc_attr( $item_key ); ?>"
						   href="<?php echo esc_url( $download_url ); ?>"
						   class="career-file-item__btn career-file-item__btn--download"
						   download
						   target="_blank"
						   rel="noopener noreferrer">
							<?php echo career_get_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( career_text( 'ดาวน์โหลด', 'Download' ) ); ?></span>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php
		if ( $preview_enabled ) :
			$pdf_files = array_filter(
				$files,
				static function ( $file ): bool {
					$ext = strtolower( (string) ( $file['ext'] ?? '' ) );
					$url = (string) ( $file['url'] ?? '' );
					return 'pdf' === $ext || 1 === preg_match( '/\.pdf($|\?)/i', $url );
				}
			);
			if ( ! empty( $pdf_files ) ) :
				?>
				<div id="career-pdf-embeds-<?php echo esc_attr( (string) $post_id ); ?>" class="career-pdf-embeds">
					<?php foreach ( $pdf_files as $p_idx => $p_file ) : ?>
						<?php
						$pdf_url   = (string) ( $p_file['url'] ?? '' );
						$pdf_title = (string) ( $p_file['title'] ?? 'เอกสาร PDF' );
						if ( '' === $pdf_url ) {
							continue;
						}
						?>
						<div id="career-pdf-card-<?php echo esc_attr( $post_id . '-' . $p_idx ); ?>" class="career-pdf-embed-card">
							<div id="career-pdf-card-header-<?php echo esc_attr( $post_id . '-' . $p_idx ); ?>" class="career-pdf-embed-card__header">
								<strong id="career-pdf-card-title-<?php echo esc_attr( $post_id . '-' . $p_idx ); ?>"><?php echo esc_html( $pdf_title ); ?></strong>
								<a id="career-pdf-card-open-<?php echo esc_attr( $post_id . '-' . $p_idx ); ?>" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( career_text( 'เปิดดูในแท็บใหม่ →', 'Open in new tab →' ) ); ?>
								</a>
							</div>
							<div id="career-pdf-card-body-<?php echo esc_attr( $post_id . '-' . $p_idx ); ?>" class="career-pdf-embed-card__body">
								<iframe id="career-pdf-iframe-<?php echo esc_attr( $post_id . '-' . $p_idx ); ?>"
										src="<?php echo esc_url( $pdf_url . '#toolbar=1&navpanes=0' ); ?>"
										title="<?php echo esc_attr( $pdf_title ); ?>"
										loading="lazy"></iframe>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			endif;
		endif;
		?>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'career_download_file', 'career_download_file_shortcode' );

/**
 * Handle Force Download `?career_download_file=ATTACHMENT_ID`.
 */
function career_handle_force_download(): void {
	if ( ! isset( $_GET['career_download_file'] ) ) {
		return;
	}

	$file_id = absint( $_GET['career_download_file'] );
	if ( $file_id <= 0 ) {
		return;
	}

	$file_path = get_attached_file( $file_id );
	if ( ! $file_path || ! file_exists( $file_path ) ) {
		return;
	}

	$filename  = basename( $file_path );
	$filetype  = wp_check_filetype( $filename );
	$mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream';

	if ( ob_get_level() ) {
		ob_end_clean();
	}

	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: ' . $mime_type );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . (string) filesize( $file_path ) );
	readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action( 'init', 'career_handle_force_download', 15 );

/**
 * Render Universal PDF Preview Modal in `wp_footer`.
 */
function career_render_pdf_modal_footer(): void {
	?>
	<div id="career-pdf-preview-modal" class="career-pdf-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="career-pdf-modal-title">
		<div id="career-pdf-modal-backdrop" class="career-pdf-modal__backdrop"></div>
		<div id="career-pdf-modal-dialog" class="career-pdf-modal__dialog">
			<div id="career-pdf-modal-header" class="career-pdf-modal__header">
				<div id="career-pdf-modal-title-wrap" class="career-pdf-modal__title-wrap">
					<h3 id="career-pdf-modal-title" class="career-pdf-modal__title">
						<?php echo esc_html( career_text( 'เอกสารตัวอย่าง (PDF Preview)', 'PDF Preview' ) ); ?>
					</h3>
					<span id="career-pdf-modal-subtitle" class="career-pdf-modal__subtitle">PDF Document</span>
				</div>
				<div id="career-pdf-modal-actions" class="career-pdf-modal__actions">
					<a id="career-pdf-modal-newtab-btn" href="#" target="_blank" rel="noopener noreferrer" class="career-pdf-modal__btn career-pdf-modal__btn--secondary">
						<?php echo esc_html( career_text( 'เปิดในแท็บใหม่', 'Open in new tab' ) ); ?>
					</a>
					<a id="career-pdf-modal-download-btn" href="#" download class="career-pdf-modal__btn career-pdf-modal__btn--primary">
						<?php echo esc_html( career_text( 'ดาวน์โหลด', 'Download' ) ); ?>
					</a>
					<button id="career-pdf-modal-close-btn" type="button" class="career-pdf-modal__close" aria-label="<?php esc_attr_e( 'ปิดหน้าต่าง', 'career-manager' ); ?>">✕</button>
				</div>
			</div>
			<div id="career-pdf-modal-body" class="career-pdf-modal__body">
				<iframe id="career-pdf-modal-iframe" class="career-pdf-modal__iframe" src="about:blank" title="PDF Preview" allowfullscreen></iframe>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'career_render_pdf_modal_footer', 99 );
