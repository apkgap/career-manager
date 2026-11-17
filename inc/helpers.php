<?php
/**
 * Standalone Helper Functions for Career Manager Plugin.
 *
 * Provides self-contained multilingual text fallback, Thai Buddhist Era (B.E.)
 * date formatting, SVG icons, post view tracking, and PDF attachment lookup
 * so the plugin works on any WordPress project without theme dependencies.
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bilingual text helper (supports Polylang, WPML, or standard locale fallback).
 *
 * @param string $th Thai text.
 * @param string $en English text.
 * @return string
 */
function career_text( string $th, string $en = '' ): string {
	if ( '' === $en ) {
		return $th;
	}

	if ( function_exists( 'cttm_text' ) ) {
		return (string) cttm_text( $th, $en );
	}

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = (string) pll_current_language( 'slug' );
		if ( '' !== $lang && 0 !== strpos( strtolower( $lang ), 'th' ) ) {
			return $en;
		}
		return $th;
	}

	$locale = get_locale();
	if ( 0 !== strpos( strtolower( (string) $locale ), 'th' ) && '' !== $en ) {
		return $en;
	}

	return $th;
}

/**
 * Get plugin settings array with defaults.
 *
 * @return array<string, mixed>
 */
function career_get_settings(): array {
	$defaults = array(
		'primary_color'      => '#800020',
		'primary_dark_color' => '#5c0017',
		'notify_email'       => (string) get_option( 'admin_email' ),
		'archive_title_th'   => 'ตำแหน่งงานว่าง',
		'archive_title_en'   => 'Job Vacancies',
		'archive_sub_th'     => 'ประกาศรับสมัครงาน',
		'archive_sub_en'     => 'Job Announcements',
		'archive_note_th'    => '',
		'archive_note_en'    => '',
		'posts_per_page'     => 10,
		'enable_pdf_preview' => '1',
		'template_mode'      => 'auto', // 'auto', 'plugin', 'theme'
	);

	$saved = get_option( 'career_manager_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return array_merge( $defaults, $saved );
}

/**
 * Format post date into standard or Thai Buddhist Era (year + 543) format.
 *
 * @param int|\WP_Post|null $post   Post ID or object.
 * @param string            $format 'slash' (d/m/Y), 'short' (j ม.ค. 2569), or 'full'.
 * @return string
 */
function career_format_date( $post = null, string $format = 'slash' ): string {
	$post_obj = get_post( $post );
	if ( ! $post_obj ) {
		return '';
	}

	$timestamp = get_post_time( 'U', false, $post_obj );
	if ( ! $timestamp ) {
		return (string) get_the_date( 'd/m/Y', $post_obj );
	}

	$day        = (int) gmdate( 'j', (int) $timestamp );
	$day_padded = gmdate( 'd', (int) $timestamp );
	$month_num  = (int) gmdate( 'n', (int) $timestamp );
	$month_pad  = gmdate( 'm', (int) $timestamp );
	$year_ce    = (int) gmdate( 'Y', (int) $timestamp );
	$year_be    = $year_ce + 543;

	if ( 'slash' === $format ) {
		return sprintf( '%s/%s/%d', $day_padded, $month_pad, $year_ce );
	}

	if ( 'slash_be' === $format ) {
		return sprintf( '%s/%s/%d', $day_padded, $month_pad, $year_be );
	}

	$thai_months_short = array(
		1  => 'ม.ค.',
		2  => 'ก.พ.',
		3  => 'มี.ค.',
		4  => 'เม.ย.',
		5  => 'พ.ค.',
		6  => 'มิ.ย.',
		7  => 'ก.ค.',
		8  => 'ส.ค.',
		9  => 'ก.ย.',
		10 => 'ต.ค.',
		11 => 'พ.ย.',
		12 => 'ธ.ค.',
	);

	$thai_months_full = array(
		1  => 'มกราคม',
		2  => 'กุมภาพันธ์',
		3  => 'มีนาคม',
		4  => 'เมษายน',
		5  => 'พฤษภาคม',
		6  => 'มิถุนายน',
		7  => 'กรกฎาคม',
		8  => 'สิงหาคม',
		9  => 'กันยายน',
		10 => 'ตุลาคม',
		11 => 'พฤศจิกายน',
		12 => 'ธันวาคม',
	);

	if ( 'full' === $format ) {
		$m_label = $thai_months_full[ $month_num ] ?? $month_pad;
		return sprintf( '%d %s %d', $day, $m_label, $year_be );
	}

	$m_label = $thai_months_short[ $month_num ] ?? $month_pad;
	return sprintf( '%d %s %d', $day, $m_label, $year_be );
}

/**
 * Get post view count for a career item.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function career_get_post_views( int $post_id ): int {
	$views = get_post_meta( $post_id, 'post_views_count', true );
	return is_numeric( $views ) ? (int) $views : 0;
}

/**
 * Increment post views when viewing a single career post on frontend.
 */
function career_track_post_views(): void {
	if ( is_admin() || ! is_singular( 'career' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( $post_id <= 0 ) {
		return;
	}

	$views = (int) get_post_meta( $post_id, 'post_views_count', true );
	update_post_meta( $post_id, 'post_views_count', $views + 1 );
}
add_action( 'wp', 'career_track_post_views' );

/**
 * Get all PDF attachments associated with a career post.
 *
 * @param int $post_id Post ID.
 * @return array<int, array{id:int, url:string, title:string, size:string}>
 */
function career_get_post_pdf_files( int $post_id ): array {
	if ( $post_id <= 0 ) {
		return array();
	}

	$pdf_files = array();
	$seen_urls = array();

	$file_attachments = get_post_meta( $post_id, '_career_file_attachments', true );
	if ( is_array( $file_attachments ) && ! empty( $file_attachments ) ) {
		foreach ( $file_attachments as $file ) {
			$url = isset( $file['url'] ) ? (string) $file['url'] : '';
			$ext = isset( $file['ext'] ) ? strtolower( (string) $file['ext'] ) : '';

			if ( '' !== $url && ( 'pdf' === $ext || 1 === preg_match( '/\.pdf($|\?)/i', $url ) ) ) {
				if ( ! in_array( $url, $seen_urls, true ) ) {
					$seen_urls[] = $url;
					$pdf_files[] = array(
						'id'    => isset( $file['id'] ) ? absint( $file['id'] ) : 0,
						'url'   => $url,
						'title' => ! empty( $file['title'] ) ? (string) $file['title'] : get_the_title( $post_id ),
						'size'  => isset( $file['size'] ) ? (string) $file['size'] : '',
					);
				}
			}
		}
	}

	if ( empty( $pdf_files ) ) {
		$attached_media = get_attached_media( 'application/pdf', $post_id );
		if ( ! empty( $attached_media ) ) {
			foreach ( $attached_media as $media ) {
				$media_url = wp_get_attachment_url( $media->ID );
				if ( $media_url && ! in_array( $media_url, $seen_urls, true ) ) {
					$seen_urls[] = $media_url;
					$file_path   = get_attached_file( $media->ID );
					$file_size   = ( $file_path && file_exists( $file_path ) ) ? (string) size_format( (int) filesize( $file_path ) ) : '';
					$pdf_files[] = array(
						'id'    => (int) $media->ID,
						'url'   => (string) $media_url,
						'title' => $media->post_title ? (string) $media->post_title : get_the_title( $post_id ),
						'size'  => $file_size,
					);
				}
			}
		}
	}

	return $pdf_files;
}

/**
 * Get the primary PDF URL for a career post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function career_get_primary_pdf_url( int $post_id ): string {
	$files = career_get_post_pdf_files( $post_id );
	return ! empty( $files[0]['url'] ) ? (string) $files[0]['url'] : '';
}

/**
 * Check whether PDF preview is enabled for a career post.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function career_is_pdf_preview_enabled( int $post_id ): bool {
	if ( $post_id > 0 ) {
		$meta = (string) get_post_meta( $post_id, '_career_pdf_preview', true );
		if ( '1' === $meta ) {
			return true;
		}
		if ( '0' === $meta ) {
			return false;
		}
	}

	$settings = career_get_settings();
	return '0' !== (string) ( $settings['enable_pdf_preview'] ?? '1' );
}

/**
 * Render inline SVG icons for standalone portability.
 *
 * @param string $name Icon slug: search, map-pin, calendar, eye, arrow-right, user-tie, folder-open, print, download, paperclip.
 * @return string SVG HTML markup.
 */
function career_get_icon( string $name ): string {
	$icons = array(
		'search'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
		'map-pin'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
		'calendar'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" y2="10"></line></svg>',
		'eye'          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
		'arrow-right'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
		'arrow-up-right' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>',
		'chevron-left' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>',
		'briefcase'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
		'building'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>',
		'clock'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
		'share'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line></svg>',
		'send'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" x2="11" y1="2" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
		'sliders'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="4" y1="21" y2="14"></line><line x1="4" x2="4" y1="10" y2="3"></line><line x1="12" x2="12" y1="21" y2="12"></line><line x1="12" x2="12" y1="8" y2="3"></line><line x1="20" x2="20" y1="21" y2="16"></line><line x1="20" x2="20" y1="12" y2="3"></line><line x1="2" x2="6" y1="14" y2="14"></line><line x1="10" x2="14" y1="8" y2="8"></line><line x1="18" x2="22" y1="16" y2="16"></line></svg>',
		'user-tie'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle><polygon points="12 11 10.5 16 12 19 13.5 16 12 11"></polygon></svg>',
		'folder-open'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>',
		'print'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
		'download'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
		'paperclip'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>',
	);

	return $icons[ $name ] ?? $icons['arrow-right'];
}
