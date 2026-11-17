<?php
/**
 * Standalone Single Career Template for Career Manager Plugin.
 *
 * Matches the 2-Column Job Detail UI:
 * - Top White Header Bar: Job Title (H1) + Light Gray Department/Category Badge
 * - Main Gray Container (`#e2e6ef`):
 *   - Left White Card: Job Description, Qualifications, and Optional Attachments
 *   - Right Sticky White Card: Job Title + "Apply Now" Button (Opens Application Modal, or disabled gray state when closed)
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'CAREER_RENDERING_SINGLE_TEMPLATE' ) ) {
	define( 'CAREER_RENDERING_SINGLE_TEMPLATE', true );
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id       = (int) get_the_ID();
	$title         = get_the_title( $post_id );
	$permalink     = (string) get_permalink( $post_id );
	$archive_url   = (string) get_post_type_archive_link( 'career' );
	$posted_date   = career_format_date( $post_id, 'slash' );
	$views_count   = career_get_post_views( $post_id );
	$cat_terms     = get_the_terms( $post_id, 'career_category' );
	$loc_terms     = get_the_terms( $post_id, 'career_location' );
	$type_terms    = get_the_terms( $post_id, 'career_type' );
	$is_urgent     = '1' === (string) get_post_meta( $post_id, '_career_is_urgent', true );
	$is_app_closed = '1' === (string) get_post_meta( $post_id, '_career_application_closed', true );
	$loc_custom    = trim( (string) get_post_meta( $post_id, '_career_location_custom', true ) );

	$location_label = $loc_custom;
	if ( '' === $location_label ) {
		if ( ! empty( $loc_terms ) && ! is_wp_error( $loc_terms ) ) {
			$location_label = implode( ', ', wp_list_pluck( $loc_terms, 'name' ) );
		} else {
			$location_label = career_text( 'ไม่ระบุสถานที่', 'Not specified' );
		}
	}

	$dept_label = ( ! empty( $cat_terms ) && ! is_wp_error( $cat_terms ) )
		? implode( ', ', wp_list_pluck( $cat_terms, 'name' ) )
		: career_text( 'ทั่วไป', 'General' );

	$type_label = ( ! empty( $type_terms ) && ! is_wp_error( $type_terms ) )
		? implode( ', ', wp_list_pluck( $type_terms, 'name' ) )
		: career_text( 'Full-time', 'Full-time' );
	?>
	<article id="single-career-<?php echo esc_attr( (string) $post_id ); ?>" <?php post_class( 'single-career' ); ?>>
		<!-- Top Header Banner -->
		<header id="single-career-top-banner" class="single-career__top-banner">
			<div id="single-career-top-container" class="career-container">
				<?php if ( '' !== $archive_url ) : ?>
					<div id="single-career-nav" class="single-career__nav">
						<a id="single-career-back-link" href="<?php echo esc_url( $archive_url ); ?>" class="single-career__back-link">
							<?php echo career_get_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( career_text( 'กลับไปหน้ารวมตำแหน่งงานทั้งหมด', 'Back to All Positions' ) ); ?></span>
						</a>
					</div>
				<?php endif; ?>

				<div id="single-career-badges" class="single-career__badges">
					<?php if ( ! empty( $cat_terms ) && ! is_wp_error( $cat_terms ) ) : ?>
						<?php foreach ( $cat_terms as $term_item ) : ?>
							<span id="single-career-badge-<?php echo esc_attr( (string) $term_item->term_id ); ?>" class="single-career__dept-pill">
								<?php echo esc_html( $term_item->name ); ?>
							</span>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( $is_urgent ) : ?>
						<span id="single-career-badge-urgent" class="single-career__urgent-pill">
							<span id="single-career-urgent-dot" class="career-card__badge-dot" aria-hidden="true"></span>
							<?php echo esc_html( career_text( 'ด่วน', 'ด่วน' ) ); ?>
						</span>
					<?php endif; ?>

					<?php if ( ! empty( $type_terms ) && ! is_wp_error( $type_terms ) ) : ?>
						<?php foreach ( $type_terms as $t_item ) : ?>
							<span id="single-career-badge-type-<?php echo esc_attr( (string) $t_item->term_id ); ?>" class="single-career__type-pill">
								<?php echo esc_html( $t_item->name ); ?>
							</span>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<h1 id="single-career-title" class="single-career__title">
					<?php echo esc_html( $title ); ?>
				</h1>

				<div id="single-career-quick-meta" class="single-career__quick-meta">
					<span id="single-career-meta-location" class="single-career__meta-item">
						<?php echo career_get_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $location_label ); ?></span>
					</span>

					<span id="single-career-meta-date" class="single-career__meta-item">
						<?php echo career_get_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $posted_date ); ?></span>
					</span>

					<span id="single-career-meta-views" class="single-career__meta-item">
						<?php echo career_get_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( number_format_i18n( $views_count ) . ' ' . career_text( 'ครั้ง', 'views' ) ); ?></span>
					</span>
				</div>
			</div>
		</header>

		<!-- Main Body Section -->
		<div id="single-career-body" class="single-career__body">
			<div id="single-career-container" class="career-container">
				<div id="single-career-layout" class="single-career__layout">
					<!-- Left Column: White Content Card (Job Description & Qualifications) -->
					<div id="single-career-content-card" class="single-career__content-card">
						<?php if ( has_post_thumbnail( $post_id ) ) : ?>
							<div id="single-career-thumb" class="single-career__thumb">
								<?php echo wp_get_attachment_image( (int) get_post_thumbnail_id( $post_id ), 'full', false, array( 'class' => 'single-career__thumb-img' ) ); ?>
							</div>
						<?php endif; ?>

						<div id="single-career-entry" class="single-career__entry entry-content">
							<?php the_content(); ?>
						</div>

						<?php
						// Render File Attachments & PDF Preview Box if any files are attached
						echo career_download_file_shortcode( array( 'id' => $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>

					<!-- Right Column: Sticky Apply Card -->
					<aside id="single-career-apply-sidebar" class="single-career__apply-sidebar">
						<div id="single-career-apply-card" class="single-career__apply-card">
							<div id="single-career-apply-card-header" class="single-career__apply-header">
								<span id="single-career-apply-eyebrow" class="single-career__apply-eyebrow">
									<?php echo esc_html( career_text( 'สรุปข้อมูลตำแหน่งงาน', 'Job Overview' ) ); ?>
								</span>
								<h2 id="single-career-apply-title" class="single-career__apply-title">
									<?php echo esc_html( $title ); ?>
								</h2>
							</div>

							<dl id="single-career-summary-list" class="single-career__summary-list">
								<div id="single-career-sum-dept" class="single-career__summary-row">
									<dt id="single-career-sum-dept-dt"><?php echo esc_html( career_text( 'แผนก / หมวดหมู่', 'Department' ) ); ?></dt>
									<dd id="single-career-sum-dept-dd"><?php echo esc_html( $dept_label ); ?></dd>
								</div>
								<div id="single-career-sum-loc" class="single-career__summary-row">
									<dt id="single-career-sum-loc-dt"><?php echo esc_html( career_text( 'สถานที่ปฏิบัติงาน', 'Location' ) ); ?></dt>
									<dd id="single-career-sum-loc-dd"><?php echo esc_html( $location_label ); ?></dd>
								</div>
								<div id="single-career-sum-type" class="single-career__summary-row">
									<dt id="single-career-sum-type-dt"><?php echo esc_html( career_text( 'รูปแบบการจ้างงาน', 'Employment Type' ) ); ?></dt>
									<dd id="single-career-sum-type-dd"><?php echo esc_html( $type_label ); ?></dd>
								</div>
								<div id="single-career-sum-date" class="single-career__summary-row">
									<dt id="single-career-sum-date-dt"><?php echo esc_html( career_text( 'วันที่ลงประกาศ', 'Date Posted' ) ); ?></dt>
									<dd id="single-career-sum-date-dd"><?php echo esc_html( $posted_date ); ?></dd>
								</div>
							</dl>

							<?php if ( $is_app_closed ) : ?>
								<button id="single-career-apply-btn-disabled"
										type="button"
										class="single-career__apply-btn is-disabled"
										disabled>
									<?php echo esc_html( career_text( 'ปิดรับสมัครชั่วคราว (Closed)', 'Applications Closed' ) ); ?>
								</button>
								<p id="single-career-apply-closed-note" class="single-career__apply-closed-note">
									<?php echo esc_html( career_text( 'ตำแหน่งงานนี้ปิดรับสมัครชั่วคราวในขณะนี้', 'This position is not currently accepting applications.' ) ); ?>
								</p>
							<?php else : ?>
								<button id="single-career-apply-btn"
										type="button"
										class="single-career__apply-btn js-career-open-apply-modal"
										data-job-id="<?php echo esc_attr( (string) $post_id ); ?>"
										data-job-title="<?php echo esc_attr( $title ); ?>">
									<?php echo career_get_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php echo esc_html( career_text( 'สมัครงานตำแหน่งนี้ (Apply Now)', 'Apply Now' ) ); ?></span>
								</button>
							<?php endif; ?>

							<button id="single-career-share-btn"
									type="button"
									class="single-career__share-btn js-career-copy-link"
									data-copy-url="<?php echo esc_url( $permalink ); ?>">
								<?php echo career_get_icon( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html( career_text( 'คัดลอกลิงก์ตำแหน่งงานนี้', 'Copy Job Link' ) ); ?></span>
							</button>
						</div>
					</aside>
				</div>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
