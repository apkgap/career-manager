<?php
/**
 * Automatic Template Loader & Content Fallback for Career Manager Plugin.
 *
 * Ensures that when the plugin is installed on any WordPress project/theme,
 * `/career/`, `/career-category/*`, and single `/career/*` pages render with a
 * clean, modern layout out of the box.
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load plugin templates for Career Archive, Category, and Single Career pages.
 *
 * @param string $template Resolved theme template path.
 * @return string
 */
function career_template_loader( string $template ): string {
	$settings = career_get_settings();
	$mode     = (string) ( $settings['template_mode'] ?? 'auto' );

	if ( 'theme' === $mode ) {
		return $template;
	}

	if ( is_post_type_archive( 'career' ) || is_tax( 'career_category' ) ) {
		$theme_file = locate_template( array( 'archive-career.php', 'taxonomy-career_category.php' ) );
		if ( 'plugin' === $mode || empty( $theme_file ) ) {
			$plugin_tpl = CAREER_PATH . 'templates/archive-career.php';
			if ( file_exists( $plugin_tpl ) ) {
				return $plugin_tpl;
			}
		}
	}

	if ( is_singular( 'career' ) ) {
		$theme_single = locate_template( array( 'single-career.php' ) );
		if ( 'plugin' === $mode || empty( $theme_single ) ) {
			$plugin_single = CAREER_PATH . 'templates/single-career.php';
			if ( file_exists( $plugin_single ) ) {
				return $plugin_single;
			}
		}
	}

	return $template;
}
add_filter( 'template_include', 'career_template_loader', 99 );

/**
 * Append file download box automatically if the active theme uses its own `single.php`
 * that does not already output `[career_download_file]`.
 *
 * @param string $content Post content.
 * @return string
 */
function career_append_downloads_to_content( string $content ): string {
	if ( ! is_singular( 'career' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	// Skip if our plugin's single-career.php template is active (it renders downloads directly).
	if ( defined( 'CAREER_RENDERING_SINGLE_TEMPLATE' ) && CAREER_RENDERING_SINGLE_TEMPLATE ) {
		return $content;
	}

	if ( false !== strpos( $content, 'career_download_file' ) ) {
		return $content;
	}

	return $content . career_download_file_shortcode( array( 'id' => get_the_ID() ) );
}
add_filter( 'the_content', 'career_append_downloads_to_content', 20 );
