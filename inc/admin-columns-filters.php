<?php
/**
 * Admin List Table Columns, Filters & Settings Submenu for Career Manager Plugin.
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register custom columns for `career` list table.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function career_register_columns( array $columns ): array {
	$columns['career_comments']   = __( 'ความคิดเห็น', 'career-manager' );
	$columns['career_expiration'] = __( 'วันหมดอายุ', 'career-manager' );
	$columns['career_files']      = __( 'ไฟล์แนบ', 'career-manager' );
	$columns['career_views']      = __( 'เข้าชม', 'career-manager' );
	$columns['career_preview']    = __( 'Live Preview', 'career-manager' );
	return $columns;
}
add_filter( 'manage_career_posts_columns', 'career_register_columns' );

/**
 * Render custom column content for `career` list table.
 *
 * @param string $column  Column slug.
 * @param int    $post_id Post ID.
 */
function career_render_columns( string $column, int $post_id ): void {
	if ( 'career_comments' === $column ) {
		$post = get_post( $post_id );
		if ( $post && 'open' === $post->comment_status ) {
			echo '<span style="color:#16a34a; font-weight:700; display:inline-flex; align-items:center; gap:2px;"><span class="dashicons dashicons-admin-comments"></span> เปิดรับ</span>';
		} else {
			echo '<span style="color:#dc2626; font-weight:700; display:inline-flex; align-items:center; gap:2px;"><span class="dashicons dashicons-no"></span> ปิดรับ</span>';
		}
	}

	if ( 'career_expiration' === $column ) {
		$enable_exp = (string) get_post_meta( $post_id, '_career_enable_expiration', true );
		$exp_date   = (string) get_post_meta( $post_id, '_career_expiration_date', true );
		if ( '1' === $enable_exp && '' !== $exp_date ) {
			$formatted_date = date_i18n( 'd/m/Y H:i', (int) strtotime( $exp_date ) );
			$is_expired     = (int) strtotime( $exp_date ) <= (int) current_time( 'timestamp' );
			if ( $is_expired ) {
				echo '<span style="color:#dc2626; font-weight:700; display:inline-flex; align-items:center; gap:2px;"><span class="dashicons dashicons-warning"></span> หมดอายุ (' . esc_html( $formatted_date ) . ')</span>';
			} else {
				echo '<span style="color:#0f72dc; font-weight:700; display:inline-flex; align-items:center; gap:2px;"><span class="dashicons dashicons-clock"></span> สิ้นสุด: ' . esc_html( $formatted_date ) . '</span>';
			}
		} else {
			echo '<span style="color:#94a3b8;">— ถาวร</span>';
		}
	}

	if ( 'career_files' === $column ) {
		$attachments = get_post_meta( $post_id, '_career_file_attachments', true );
		if ( is_array( $attachments ) && ! empty( $attachments ) ) {
			echo '<span style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:10px; font-weight:700; font-size:11px; display:inline-flex; align-items:center; gap:4px;"><span class="dashicons dashicons-paperclip" style="font-size:14px;width:14px;height:14px;"></span> ไฟล์แนบ ' . count( $attachments ) . '</span>';
		} else {
			echo '<span style="color:#94a3b8;">—</span>';
		}
	}

	if ( 'career_views' === $column ) {
		$views = career_get_post_views( $post_id );
		echo '<strong>' . esc_html( number_format_i18n( $views ) ) . '</strong>';
	}

	if ( 'career_preview' === $column ) {
		$preview_link = get_preview_post_link( $post_id );
		$permalink    = ! empty( $preview_link ) ? $preview_link : get_permalink( $post_id );
		echo '<a id="career-col-preview-' . esc_attr( (string) $post_id ) . '" class="button button-small" href="' . esc_url( (string) $permalink ) . '" target="_blank" rel="noopener noreferrer" style="font-weight:700; color:#0f72dc; border-color:#0f72dc; display:inline-flex; align-items:center; gap:3px;"><span class="dashicons dashicons-visibility" style="font-size:13px; width:13px; height:13px; margin-top:2px;"></span> Live Preview</a>';
	}
}
add_action( 'manage_career_posts_custom_column', 'career_render_columns', 10, 2 );

/**
 * Render Career Admin List Table Filters (Category, Date From/To, Status, User Role, Author, Attachment filter).
 *
 * @param string $post_type Current post type slug.
 */
function career_render_list_filters( string $post_type ): void {
	if ( 'career' !== $post_type ) {
		return;
	}

	$filter_date_from  = isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '';
	$filter_date_to    = isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '';
	$filter_status     = isset( $_GET['filter_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_status'] ) ) : '';
	$filter_user_role  = isset( $_GET['filter_user_role'] ) ? sanitize_key( wp_unslash( $_GET['filter_user_role'] ) ) : '';
	$filter_author_id  = isset( $_GET['filter_author_id'] ) ? absint( $_GET['filter_author_id'] ) : 0;
	$filter_media_type = isset( $_GET['filter_media_type'] ) ? sanitize_key( wp_unslash( $_GET['filter_media_type'] ) ) : '';
	$filter_career_cat = isset( $_GET['filter_career_cat'] ) ? absint( $_GET['filter_career_cat'] ) : 0;

	global $wp_roles;
	$roles   = isset( $wp_roles ) ? $wp_roles->get_names() : array();
	$authors = get_users(
		array(
			'capability' => 'edit_posts',
			'fields'     => array( 'ID', 'display_name' ),
		)
	);

	echo '<div id="career-admin-filters-bar" class="career-admin-filters" style="display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap; margin-right:6px;">';

	// 1. Career Category
	$career_cats = get_terms(
		array(
			'taxonomy'   => 'career_category',
			'hide_empty' => false,
		)
	);
	if ( ! empty( $career_cats ) && ! is_wp_error( $career_cats ) ) {
		echo '<select id="career-filter-cat" name="filter_career_cat" style="font-size:12px;">';
		echo '<option value="0">-- หมวดหมู่ประกาศรับสมัครงานทั้งหมด --</option>';
		foreach ( $career_cats as $c_term ) {
			echo '<option value="' . esc_attr( (string) $c_term->term_id ) . '"' . selected( $filter_career_cat, (int) $c_term->term_id, false ) . '>' . esc_html( $c_term->name ) . '</option>';
		}
		echo '</select>';
	}

	// 2. Date From & To
	echo '<input id="career-filter-date-from" type="date" name="filter_date_from" value="' . esc_attr( $filter_date_from ) . '" style="max-width:130px; font-size:12px;" title="ตั้งแต่วันที่ประกาศ" />';
	echo '<input id="career-filter-date-to" type="date" name="filter_date_to" value="' . esc_attr( $filter_date_to ) . '" style="max-width:130px; font-size:12px;" title="ถึงวันที่ประกาศ" />';

	// 3. Display Status
	echo '<select id="career-filter-status" name="filter_status" style="font-size:12px;">';
	echo '<option value="">-- สถานะทั้งหมด --</option>';
	echo '<option value="publish"' . selected( $filter_status, 'publish', false ) . '>เปิดแสดงผล (Publish)</option>';
	echo '<option value="draft"' . selected( $filter_status, 'draft', false ) . '>ฉบับร่าง (Draft)</option>';
	echo '<option value="private"' . selected( $filter_status, 'private', false ) . '>ส่วนตัว (Private)</option>';
	echo '</select>';

	// 4. User Role
	echo '<select id="career-filter-user-role" name="filter_user_role" style="font-size:12px;">';
	echo '<option value="">-- กลุ่มผู้ใช้งาน (Role) --</option>';
	foreach ( $roles as $role_key => $role_name ) {
		echo '<option value="' . esc_attr( (string) $role_key ) . '"' . selected( $filter_user_role, (string) $role_key, false ) . '>' . esc_html( translate_user_role( (string) $role_name ) ) . '</option>';
	}
	echo '</select>';

	// 5. Author
	echo '<select id="career-filter-author" name="filter_author_id" style="font-size:12px;">';
	echo '<option value="0">-- ผู้ลงประกาศทั้งหมด --</option>';
	foreach ( $authors as $auth ) {
		echo '<option value="' . esc_attr( (string) $auth->ID ) . '"' . selected( $filter_author_id, (int) $auth->ID, false ) . '>' . esc_html( (string) $auth->display_name ) . '</option>';
	}
	echo '</select>';

	// 6. File Attachment filter
	echo '<select id="career-filter-media-type" name="filter_media_type" style="font-size:12px;">';
	echo '<option value="">-- ไฟล์แนบทั้งหมด --</option>';
	echo '<option value="file"' . selected( $filter_media_type, 'file', false ) . '>มีไฟล์เอกสารแนบ (*.pdf, *.zip, *.rar)</option>';
	echo '</select>';

	echo '</div>';
}
add_action( 'restrict_manage_posts', 'career_render_list_filters' );

/**
 * Apply Admin List Table Filters to `career` query.
 *
 * @param \WP_Query $query Query object.
 */
function career_apply_list_filters( \WP_Query $query ): void {
	global $pagenow;
	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
		return;
	}

	if ( 'career' !== $query->get( 'post_type' ) ) {
		return;
	}

	$date_from = isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '';
	$date_to   = isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '';

	if ( '' !== $date_from || '' !== $date_to ) {
		$date_query = array( 'inclusive' => true );
		if ( '' !== $date_from ) {
			$date_query['after'] = $date_from;
		}
		if ( '' !== $date_to ) {
			$date_query['before'] = $date_to;
		}
		$query->set( 'date_query', array( $date_query ) );
	}

	if ( ! empty( $_GET['filter_status'] ) ) {
		$status = sanitize_key( wp_unslash( $_GET['filter_status'] ) );
		if ( in_array( $status, array( 'publish', 'draft', 'private', 'future' ), true ) ) {
			$query->set( 'post_status', $status );
		}
	}

	if ( ! empty( $_GET['filter_user_role'] ) ) {
		$role          = sanitize_key( wp_unslash( $_GET['filter_user_role'] ) );
		$users_in_role = get_users(
			array(
				'role'   => $role,
				'fields' => 'ID',
			)
		);
		$query->set( 'author__in', ! empty( $users_in_role ) ? $users_in_role : array( -1 ) );
	}

	if ( ! empty( $_GET['filter_author_id'] ) && absint( $_GET['filter_author_id'] ) > 0 ) {
		$query->set( 'author', absint( $_GET['filter_author_id'] ) );
	}

	if ( isset( $_GET['filter_media_type'] ) && 'file' === sanitize_key( wp_unslash( $_GET['filter_media_type'] ) ) ) {
		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'     => '_career_file_attachments',
			'value'   => '',
			'compare' => '!=',
		);
		$query->set( 'meta_query', $meta_query );
	}

	if ( ! empty( $_GET['filter_career_cat'] ) && absint( $_GET['filter_career_cat'] ) > 0 ) {
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'career_category',
					'field'    => 'term_id',
					'terms'    => absint( $_GET['filter_career_cat'] ),
				),
			)
		);
	}
}
add_action( 'parse_query', 'career_apply_list_filters' );

/**
 * Register Settings & Shortcode Guide Submenu under `edit.php?post_type=career`.
 */
function career_register_settings_menu(): void {
	add_submenu_page(
		'edit.php?post_type=career',
		__( 'ตั้งค่าระบบร่วมงานกับเรา (Career Settings)', 'career-manager' ),
		__( 'ตั้งค่า & Shortcodes', 'career-manager' ),
		'manage_options',
		'career-manager-settings',
		'career_render_settings_page'
	);
}
add_action( 'admin_menu', 'career_register_settings_menu', 30 );

/**
 * Handle saving settings from `career-manager-settings` page.
 */
function career_handle_settings_save(): void {
	if ( ! is_admin() || ! isset( $_POST['career_save_settings'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'คุณไม่มีสิทธิ์ในการตั้งค่าระบบนี้', 'career-manager' ) );
	}

	check_admin_referer( 'career_settings_action', 'career_settings_nonce' );

	$settings = array(
		'primary_color'      => sanitize_hex_color( wp_unslash( $_POST['primary_color'] ?? '#800020' ) ) ?: '#800020',
		'primary_dark_color' => sanitize_hex_color( wp_unslash( $_POST['primary_dark_color'] ?? '#5c0017' ) ) ?: '#5c0017',
		'notify_email'       => sanitize_text_field( wp_unslash( $_POST['notify_email'] ?? (string) get_option( 'admin_email' ) ) ),
		'archive_title_th'   => sanitize_text_field( wp_unslash( $_POST['archive_title_th'] ?? 'ตำแหน่งงานว่าง' ) ),
		'archive_title_en'   => sanitize_text_field( wp_unslash( $_POST['archive_title_en'] ?? 'Job Vacancies' ) ),
		'archive_sub_th'     => sanitize_text_field( wp_unslash( $_POST['archive_sub_th'] ?? 'ประกาศรับสมัครงาน' ) ),
		'archive_sub_en'     => sanitize_text_field( wp_unslash( $_POST['archive_sub_en'] ?? 'Job Announcements' ) ),
		'archive_note_th'    => sanitize_text_field( wp_unslash( $_POST['archive_note_th'] ?? '' ) ),
		'archive_note_en'    => sanitize_text_field( wp_unslash( $_POST['archive_note_en'] ?? '' ) ),
		'posts_per_page'     => max( 1, min( 100, absint( $_POST['posts_per_page'] ?? 10 ) ) ),
		'enable_pdf_preview' => isset( $_POST['enable_pdf_preview'] ) ? '1' : '0',
		'template_mode'      => in_array( $_POST['template_mode'] ?? 'auto', array( 'auto', 'plugin', 'theme' ), true )
			? sanitize_key( wp_unslash( $_POST['template_mode'] ) )
			: 'auto',
	);

	update_option( 'career_manager_settings', $settings );

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type' => 'career',
				'page'      => 'career-manager-settings',
				'saved'     => '1',
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'career_handle_settings_save' );

/**
 * Render Career Settings & Shortcode Guide Page.
 */
function career_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'คุณไม่มีสิทธิ์ในการเข้าถึงหน้านี้', 'career-manager' ) );
	}

	$settings = career_get_settings();
	$saved    = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
	?>
	<div id="career-settings-wrap" class="wrap career-admin-settings" style="max-width: 1020px; margin-top: 20px;">
		<h1 id="career-settings-title" style="display: flex; align-items: center; gap: 10px; font-size: 23px; font-weight: 700; color: #0f294a; margin-bottom: 16px;">
			<span class="dashicons dashicons-businessperson" style="font-size: 28px; width: 28px; height: 28px; color: #0066cc;"></span>
			<?php esc_html_e( 'ระบบร่วมงานกับเรา (Career & Job Vacancies Manager)', 'career-manager' ); ?>
		</h1>

		<?php if ( $saved ) : ?>
			<div id="career-settings-saved-notice" class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'บันทึกการตั้งค่าระบบร่วมงานกับเราเรียบร้อยแล้ว', 'career-manager' ); ?></strong></p>
			</div>
		<?php endif; ?>

		<!-- Quick Usage & Shortcodes Card -->
		<div id="career-usage-card" class="career-admin-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
			<h2 style="margin-top: 0; font-size: 16px; color: #0f294a; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-shortcode" style="color: #0066cc;"></span>
				<?php esc_html_e( 'วิธีนำไปใช้งานในโปรเจกต์อื่น (Shortcodes, Elementor Widget & Archive URL)', 'career-manager' ); ?>
			</h2>
			<p style="color: #475569; font-size: 13px; margin-bottom: 14px;">
				<?php esc_html_e( 'ปลั๊กอินนี้ออกแบบแบบ Standalone ครบวงจร สามารถคัดลอกโฟลเดอร์ career-manager ไปวางใน wp-content/plugins/ ของโปรเจกต์อื่นแล้วเปิดใช้งานได้ทันที:', 'career-manager' ); ?>
			</p>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 14px;">
				<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
					<strong style="display: block; font-size: 13px; color: #0f294a; margin-bottom: 6px;">1. Shortcode หน้าแท็บประกาศรับสมัครงาน (ค้นหา + กรองหมวดหมู่ + แบ่งหน้า)</strong>
					<code style="display: block; background: #ffffff; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; color: #0066cc; font-weight: 700; font-size: 12px;">[career_tabs title="ประกาศสมัครงาน" posts_per_page="10"]</code>
				</div>
				<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
					<strong style="display: block; font-size: 13px; color: #0f294a; margin-bottom: 6px;">2. Shortcode กล่องดาวน์โหลดไฟล์แนบ + ดูตัวอย่าง PDF</strong>
					<code style="display: block; background: #ffffff; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; color: #0066cc; font-weight: 700; font-size: 12px;">[career_download_file]</code>
				</div>
				<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
					<strong style="display: block; font-size: 13px; color: #0f294a; margin-bottom: 6px;">3. Elementor Widget & ลิงก์หน้ารวมประกาศงาน</strong>
					<div style="font-size: 12px; color: #475569; line-height: 1.6;">
						• Elementor Widget: <strong>Career Archive Tabs (ร่วมงานกับเรา)</strong><br />
						• Archive URL: <a href="<?php echo esc_url( (string) get_post_type_archive_link( 'career' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) get_post_type_archive_link( 'career' ) ); ?></a>
					</div>
				</div>
			</div>
		</div>

		<!-- Settings Form -->
		<form id="career-settings-form" method="post" action="" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
			<?php wp_nonce_field( 'career_settings_action', 'career_settings_nonce' ); ?>
			<input type="hidden" name="career_save_settings" value="1" />

			<h2 style="margin-top: 0; font-size: 16px; color: #0f294a; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
				<?php esc_html_e( 'ตั้งค่าการแสดงผลและสีของธีม (Appearance & Template Settings)', 'career-manager' ); ?>
			</h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="career-primary-color"><?php esc_html_e( 'สีหลัก (Primary Color)', 'career-manager' ); ?></label></th>
					<td>
						<input id="career-primary-color" type="color" name="primary_color" value="<?php echo esc_attr( (string) $settings['primary_color'] ); ?>" />
						<code style="margin-left: 8px;"><?php echo esc_html( (string) $settings['primary_color'] ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="career-primary-dark-color"><?php esc_html_e( 'สีเมื่อชี้เมาส์ (Hover Color)', 'career-manager' ); ?></label></th>
					<td>
						<input id="career-primary-dark-color" type="color" name="primary_dark_color" value="<?php echo esc_attr( (string) $settings['primary_dark_color'] ); ?>" />
						<code style="margin-left: 8px;"><?php echo esc_html( (string) $settings['primary_dark_color'] ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="career-notify-email"><?php esc_html_e( 'อีเมลรับแจ้งเตือนใบสมัครงาน (HR Notification Email)', 'career-manager' ); ?></label></th>
					<td>
						<input id="career-notify-email" type="text" name="notify_email" value="<?php echo esc_attr( (string) ( $settings['notify_email'] ?? get_option( 'admin_email' ) ) ); ?>" class="regular-text" placeholder="hr@example.com, recruit@example.com" />
						<p class="description"><?php esc_html_e( 'ระบบจะส่งอีเมลพร้อมแนบไฟล์ Resume ไปยังอีเมลนี้ทันทีที่มีผู้กดสมัครงาน (ใส่หลายอีเมลคั่นด้วยเครื่องหมายจุลภาค , ได้)', 'career-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="career-template-mode"><?php esc_html_e( 'โหมดเทมเพลตหน้าเว็บ (Template Mode)', 'career-manager' ); ?></label></th>
					<td>
						<select id="career-template-mode" name="template_mode" style="min-width: 340px;">
							<option value="auto" <?php selected( $settings['template_mode'], 'auto' ); ?>><?php esc_html_e( 'อัตโนมัติ (ใช้เทมเพลตของธีมถ้ามี หากไม่มีใช้เทมเพลตของปลั๊กอิน)', 'career-manager' ); ?></option>
							<option value="plugin" <?php selected( $settings['template_mode'], 'plugin' ); ?>><?php esc_html_e( 'บังคับใช้เทมเพลตของปลั๊กอินเสมอ (เหมาะสำหรับโปรเจกต์ใหม่)', 'career-manager' ); ?></option>
							<option value="theme" <?php selected( $settings['template_mode'], 'theme' ); ?>><?php esc_html_e( 'ใช้เทมเพลตของธีมเท่านั้น', 'career-manager' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="career-posts-per-page"><?php esc_html_e( 'จำนวนรายการต่อหน้า (Posts Per Page)', 'career-manager' ); ?></label></th>
					<td>
						<input id="career-posts-per-page" type="number" min="1" max="100" name="posts_per_page" value="<?php echo esc_attr( (string) $settings['posts_per_page'] ); ?>" class="small-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="career-archive-title-th"><?php esc_html_e( 'หัวข้อหน้าประกาศงาน (TH / EN)', 'career-manager' ); ?></label></th>
					<td>
						<input id="career-archive-title-th" type="text" name="archive_title_th" value="<?php echo esc_attr( (string) $settings['archive_title_th'] ); ?>" class="regular-text" placeholder="ตำแหน่งงานว่าง" style="margin-bottom: 6px;" /><br />
						<input id="career-archive-title-en" type="text" name="archive_title_en" value="<?php echo esc_attr( (string) $settings['archive_title_en'] ); ?>" class="regular-text" placeholder="Job Vacancies" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="career-archive-note-th"><?php esc_html_e( 'คำอธิบายเพิ่มเติมใต้หัวข้อ (TH / EN)', 'career-manager' ); ?></label></th>
					<td>
						<input id="career-archive-note-th" type="text" name="archive_note_th" value="<?php echo esc_attr( (string) $settings['archive_note_th'] ); ?>" class="large-text" style="margin-bottom: 6px;" /><br />
						<input id="career-archive-note-en" type="text" name="archive_note_en" value="<?php echo esc_attr( (string) $settings['archive_note_en'] ); ?>" class="large-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'เปิดใช้งานปุ่มดูตัวอย่าง PDF (PDF Preview Modal)', 'career-manager' ); ?></th>
					<td>
						<label for="career-enable-pdf-preview">
							<input id="career-enable-pdf-preview" type="checkbox" name="enable_pdf_preview" value="1" <?php checked( $settings['enable_pdf_preview'], '1' ); ?> />
							<?php esc_html_e( 'แสดงปุ่ม "ดูตัวอย่าง (Preview)" และหน้าต่างอ่านไฟล์ PDF บนหน้าเว็บอัตโนมัติ', 'career-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'บันทึกการตั้งค่า (Save Settings)', 'career-manager' ) ); ?>
		</form>
	</div>
	<?php
}
