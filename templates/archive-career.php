<?php
/**
 * Standalone Career Archive & Category Template for Career Manager Plugin.
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

$settings      = career_get_settings();
$archive_title = career_text(
	(string) ( $settings['archive_title_th'] ?? 'ตำแหน่งงานว่าง' ),
	(string) ( $settings['archive_title_en'] ?? 'Job Vacancies' )
);
$archive_sub   = career_text(
	(string) ( $settings['archive_sub_th'] ?? 'ประกาศรับสมัครงาน' ),
	(string) ( $settings['archive_sub_en'] ?? 'Job Announcements' )
);
$archive_note  = career_text(
	(string) ( $settings['archive_note_th'] ?? '' ),
	(string) ( $settings['archive_note_en'] ?? '' )
);

if ( is_tax() ) {
	$queried_term = get_queried_object();
	if ( $queried_term instanceof \WP_Term ) {
		$archive_title = $queried_term->name;
		if ( '' !== $queried_term->description ) {
			$archive_note = $queried_term->description;
		}
	}
}
?>

<main id="career-archive-main" class="career-archive">
	<header id="career-archive-hero" class="career-archive__hero">
		<div id="career-archive-hero-container" class="career-container">
			<div id="career-archive-hero-inner" class="career-archive__hero-inner">
				<?php if ( '' !== $archive_sub ) : ?>
					<span id="career-archive-eyebrow" class="career-archive__eyebrow">
						<?php echo career_get_icon( 'briefcase' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $archive_sub ); ?></span>
					</span>
				<?php endif; ?>

				<h1 id="career-archive-heading" class="career-archive__title">
					<?php echo esc_html( $archive_title ); ?>
				</h1>

				<?php if ( '' !== $archive_note ) : ?>
					<p id="career-archive-subtitle" class="career-archive__subtitle">
						<?php echo esc_html( $archive_note ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<div id="career-archive-container" class="career-container">
		<div id="career-archive-body" class="career-archive__body">
			<?php
			echo career_render_tabs_component(
				array(
					'title'          => '',
					'posts_per_page' => (int) ( $settings['posts_per_page'] ?? 10 ),
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'instance_id'    => 'archive-main',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</main>

<?php
get_footer();
