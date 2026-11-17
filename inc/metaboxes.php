<?php
/**
 * Admin Metaboxes & Expiration Cron for Career Post Type (`career`).
 *
 * Includes:
 * 1. Job Card Attributes Metabox (`_career_is_urgent`, `_career_application_closed`, `_career_location_custom`)
 * 2. Live Preview Button Metabox
 * 3. Comment Status Toggle Metabox (Open / Closed)
 * 4. Expiration Date Metabox (`_career_enable_expiration`, `_career_expiration_date`, `_career_expiration_action`)
 * 5. Multi-File Attachments & Direct / Force Download Link Generator (`_career_file_attachments`, `_career_pdf_preview`)
 * 6. Additional Upload MIME Types (`zip`, `rar`, `7z`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`)
 * 7. Hourly WP-Cron & Frontend 404 Guard for Expired Career Posts
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Allow common archive & document MIME types for Career attachments.
 *
 * @param array<string, string> $mimes Allowed MIME types.
 * @return array<string, string>
 */
function career_allow_upload_mimes( array $mimes ): array {
	$mimes['zip']  = 'application/zip';
	$mimes['rar']  = 'application/x-rar-compressed';
	$mimes['7z']   = 'application/x-7z-compressed';
	$mimes['pdf']  = 'application/pdf';
	$mimes['doc']  = 'application/msword';
	$mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
	$mimes['xls']  = 'application/vnd.ms-excel';
	$mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	$mimes['ppt']  = 'application/vnd.ms-powerpoint';
	$mimes['pptx'] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
	return $mimes;
}
add_filter( 'upload_mimes', 'career_allow_upload_mimes' );

/**
 * Register Career metaboxes.
 */
function career_register_metaboxes(): void {
	add_meta_box(
		'career_job_attributes_mb',
		__( 'ตั้งค่าป้ายสถานะและสถานที่บนการ์ดงาน (Job Card Badges & Status)', 'career-manager' ),
		'career_render_job_attributes_metabox',
		'career',
		'normal',
		'high'
	);

	add_meta_box(
		'career_live_preview_mb',
		__( 'ดูตัวอย่างหน้าเว็บไซต์จริง (Live Preview)', 'career-manager' ),
		'career_render_live_preview_metabox',
		'career',
		'side',
		'high'
	);

	add_meta_box(
		'career_comment_status_mb',
		__( 'ตั้งค่าการแสดงความคิดเห็น (Comment Settings)', 'career-manager' ),
		'career_render_comment_status_metabox',
		'career',
		'side',
		'default'
	);

	add_meta_box(
		'career_expiration_date_mb',
		__( 'กำหนดวันสิ้นสุดการเผยแพร่ (Expiration Date)', 'career-manager' ),
		'career_render_expiration_date_metabox',
		'career',
		'side',
		'default'
	);

	add_meta_box(
		'career_file_attachments_mb',
		__( 'ไฟล์แนบและลิงก์ดาวน์โหลด (File Attachments & Generated Link)', 'career-manager' ),
		'career_render_file_attachments_metabox',
		'career',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'career_register_metaboxes' );

/**
 * Render Job Card Attributes Metabox (Urgent badge, Applications closed banner, Location label override).
 *
 * @param \WP_Post $post Current post object.
 */
function career_render_job_attributes_metabox( \WP_Post $post ): void {
	$is_urgent     = (string) get_post_meta( $post->ID, '_career_is_urgent', true );
	$is_app_closed = (string) get_post_meta( $post->ID, '_career_application_closed', true );
	$loc_custom    = (string) get_post_meta( $post->ID, '_career_location_custom', true );
	$notify_email  = (string) get_post_meta( $post->ID, '_career_notify_email', true );
	?>
	<div id="career-job-attributes-box" class="career-job-attributes-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; padding: 8px 0;">
		<div id="career-attr-urgent-card" style="background: #fff5f5; border: 1px solid #fecdd3; border-radius: 10px; padding: 14px 16px;">
			<label id="career-attr-urgent-label" for="career-is-urgent-checkbox" style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #881337; cursor: pointer;">
				<input id="career-is-urgent-checkbox" type="checkbox" name="career_is_urgent" value="1" <?php checked( $is_urgent, '1' ); ?> />
				<span><?php esc_html_e( 'แสดงป้าย Tag "ด่วน" บนการ์ดงาน', 'career-manager' ); ?></span>
			</label>
			<p style="margin: 6px 0 0; font-size: 12px; color: #9f1239;">
				<?php esc_html_e( 'แสดงป้าย Tag สีแดงคำว่า "ด่วน" คู่กับชื่อแผนกบนการ์ดประกาศงาน', 'career-manager' ); ?>
			</p>
		</div>

		<div id="career-attr-closed-card" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px 16px;">
			<label id="career-attr-closed-label" for="career-app-closed-checkbox" style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #334155; cursor: pointer;">
				<input id="career-app-closed-checkbox" type="checkbox" name="career_application_closed" value="1" <?php checked( $is_app_closed, '1' ); ?> />
				<span><?php esc_html_e( 'ปิดรับสมัครแล้ว (Applications closed)', 'career-manager' ); ?></span>
			</label>
			<p style="margin: 6px 0 0; font-size: 12px; color: #64748b;">
				<?php esc_html_e( 'ปิดปุ่ม Apply Now เป็นสีเทา และแสดงข้อความว่าไม่เปิดรับสมัครในขณะนี้', 'career-manager' ); ?>
			</p>
		</div>

		<div id="career-attr-location-card" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px 16px;">
			<label id="career-attr-location-label" for="career-location-custom-input" style="display: block; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
				<?php esc_html_e( 'ข้อความสถานที่บนการ์ด (Location Text Override):', 'career-manager' ); ?>
			</label>
			<input id="career-location-custom-input" type="text" name="career_location_custom" value="<?php echo esc_attr( $loc_custom ); ?>" placeholder="<?php esc_attr_e( 'เช่น Not specified หรือ Bangkok', 'career-manager' ); ?>" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;" />
		</div>

		<div id="career-attr-email-card" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 14px 16px;">
			<label id="career-attr-email-label" for="career-notify-email-input" style="display: block; font-weight: 700; color: #0369a1; margin-bottom: 6px;">
				<?php esc_html_e( 'อีเมลรับใบสมัครเฉพาะตำแหน่งนี้ (HR Email):', 'career-manager' ); ?>
			</label>
			<input id="career-notify-email-input" type="text" name="career_notify_email" value="<?php echo esc_attr( $notify_email ); ?>" placeholder="<?php esc_attr_e( 'ว่างไว้จะใช้อีเมลหลักในหน้าตั้งค่า (คั่นด้วย , ได้)', 'career-manager' ); ?>" style="width: 100%; padding: 6px 10px; border-radius: 6px; border: 1px solid #7dd3fc; font-size: 13px;" />
		</div>
	</div>
	<?php
}

/**
 * Render Live Preview Metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function career_render_live_preview_metabox( \WP_Post $post ): void {
	$preview_link = get_preview_post_link( $post );
	$permalink    = ! empty( $preview_link ) ? $preview_link : get_permalink( $post->ID );
	?>
	<div id="career-live-preview-box" class="career-preview-wrap" style="text-align: center; padding: 8px 0;">
		<a id="career-live-preview-btn" class="button button-primary button-large" href="<?php echo esc_url( (string) $permalink ); ?>" target="_blank" rel="noopener noreferrer" style="width: 100%; text-align: center; font-weight: 700; background: #800020; border-color: #5c0017;">
			[Live Preview] <?php esc_html_e( 'ดูตัวอย่างบนหน้าเว็บไซต์จริง', 'career-manager' ); ?>
		</a>
		<p id="career-live-preview-note" class="description" style="margin: 8px 0 0; font-size: 11px; color: #64748b;">
			<?php esc_html_e( 'เปิดหน้าประกาศรับสมัครงานในแท็บใหม่เพื่อดูการแสดงผลจริง', 'career-manager' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Render Comment Status Toggle Metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function career_render_comment_status_metabox( \WP_Post $post ): void {
	wp_nonce_field( 'career_save_meta', 'career_meta_nonce' );
	$comment_status = $post->comment_status;
	?>
	<div id="career-comment-status-wrap" class="career-metabox-section" style="padding: 6px 0;">
		<label id="career-comment-status-heading" class="career-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">
			<?php esc_html_e( 'สถานะการรับความคิดเห็น:', 'career-manager' ); ?>
		</label>
		<label id="career-comment-open-label" class="career-radio-label" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; cursor: pointer;">
			<input id="career-comment-open" type="radio" name="career_comment_status" value="open" <?php checked( $comment_status, 'open' ); ?> />
			<span style="color: #16a34a; font-weight: 600;">[เปิด] <?php esc_html_e( 'เปิดแสดงความคิดเห็น (Allow Comments)', 'career-manager' ); ?></span>
		</label>
		<label id="career-comment-closed-label" class="career-radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
			<input id="career-comment-closed" type="radio" name="career_comment_status" value="closed" <?php checked( $comment_status, 'closed' ); ?> />
			<span style="color: #dc2626; font-weight: 600;">[ปิด] <?php esc_html_e( 'ปิดแสดงความคิดเห็น (Disable Comments)', 'career-manager' ); ?></span>
		</label>
	</div>
	<?php
}

/**
 * Render Expiration Date Metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function career_render_expiration_date_metabox( \WP_Post $post ): void {
	$enable_exp = (string) get_post_meta( $post->ID, '_career_enable_expiration', true );
	$exp_date   = (string) get_post_meta( $post->ID, '_career_expiration_date', true );
	$exp_action = (string) get_post_meta( $post->ID, '_career_expiration_action', true );
	if ( '' === $exp_action ) {
		$exp_action = 'draft';
	}
	?>
	<div id="career-expiration-box" class="career-metabox-section" style="padding: 6px 0;">
		<label id="career-enable-exp-label" for="career-enable-exp-toggle" class="career-checkbox-label" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-weight: 600; cursor: pointer;">
			<input id="career-enable-exp-toggle" type="checkbox" name="career_enable_expiration" value="1" <?php checked( $enable_exp, '1' ); ?> />
			<span><?php esc_html_e( 'เปิดใช้งานวันสิ้นสุดการเผยแพร่', 'career-manager' ); ?></span>
		</label>

		<div id="career-expiration-fields-wrap" class="career-exp-fields" style="display: <?php echo ( '1' === $enable_exp ) ? 'block' : 'none'; ?>; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
			<label id="career-exp-date-label" for="career-expiration-date-input" class="career-sublabel" style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">
				<?php esc_html_e( 'วันและเวลาที่สิ้นสุดการเผยแพร่:', 'career-manager' ); ?>
			</label>
			<input id="career-expiration-date-input" type="datetime-local" name="career_expiration_date" value="<?php echo esc_attr( $exp_date ); ?>" style="width: 100%; margin-bottom: 10px; padding: 6px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;" />

			<label id="career-exp-action-label" for="career-expiration-action-select" class="career-sublabel" style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">
				<?php esc_html_e( 'การทำงานเมื่อถึงกำหนดเวลา:', 'career-manager' ); ?>
			</label>
			<select id="career-expiration-action-select" name="career_expiration_action" style="width: 100%; padding: 6px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
				<option value="draft" <?php selected( $exp_action, 'draft' ); ?>><?php esc_html_e( 'เปลี่ยนสถานะเป็น ฉบับร่าง (Draft)', 'career-manager' ); ?></option>
				<option value="private" <?php selected( $exp_action, 'private' ); ?>><?php esc_html_e( 'เปลี่ยนสถานะเป็น ส่วนตัว (Private)', 'career-manager' ); ?></option>
			</select>
		</div>
	</div>
	<?php
}

/**
 * Render File Attachments & Generated Download Link Metabox.
 *
 * @param \WP_Post $post Current post object.
 */
function career_render_file_attachments_metabox( \WP_Post $post ): void {
	$attachments     = get_post_meta( $post->ID, '_career_file_attachments', true );
	$attachments     = is_array( $attachments ) ? $attachments : array();
	$shortcode       = '[career_download_file id="' . $post->ID . '"]';
	$pdf_preview_opt = (string) get_post_meta( $post->ID, '_career_pdf_preview', true );
	if ( '' === $pdf_preview_opt ) {
		$pdf_preview_opt = 'default';
	}
	?>
	<div id="career-file-attachments-container" class="career-attachments-container">
		<div id="career-shortcode-banner" class="career-shortcode-banner" style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
			<div>
				<strong style="font-size: 13px; color: #0369a1; display: flex; align-items: center; gap: 4px;">
					<span class="dashicons dashicons-shortcode" style="color: #0f72dc;"></span>
					<?php esc_html_e( 'Shortcode สำหรับแสดงกล่องดาวน์โหลดไฟล์แนบบนหน้าเว็บ:', 'career-manager' ); ?>
				</strong>
				<div style="font-size: 11px; color: #0284c7;">
					<?php esc_html_e( 'ระบบจะแสดงกล่องดาวน์โหลดอัตโนมัติในหน้าประกาศงาน หรือวาง Shortcode นี้ในเนื้อหาได้ทันที', 'career-manager' ); ?>
				</div>
			</div>
			<div style="display: flex; align-items: center; gap: 8px;">
				<input id="career-shortcode-input" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>" class="career-file-url-input" style="background: #ffffff; font-family: monospace; font-size: 12px; font-weight: 700; color: #0f72dc; padding: 6px 12px; border-radius: 6px; border: 1px solid #7dd3fc; width: 260px;" />
				<button id="career-copy-shortcode-btn" type="button" class="button button-secondary career-copy-url-btn" style="font-weight: 700; color: #0f72dc; border-color: #0f72dc;">
					<span class="dashicons dashicons-admin-page" style="line-height: 1.3;"></span> [Copy] <?php esc_html_e( 'คัดลอก Shortcode', 'career-manager' ); ?>
				</button>
			</div>
		</div>

		<div id="career-pdf-preview-setting-row" class="career-pdf-setting" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
			<div>
				<strong style="font-size: 13px; color: #0f294a; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-pdf" style="color: #0f72dc;"></span>
					<?php esc_html_e( 'การแสดงตัวอย่างเอกสาร PDF บนหน้าเว็บ (Live PDF Preview):', 'career-manager' ); ?>
				</strong>
				<div style="font-size: 12px; color: #64748b; margin-top: 2px;">
					<?php esc_html_e( 'กำหนดให้แสดงปุ่มและหน้าต่างตัวอย่างเอกสาร PDF ในหน้าประกาศรับสมัครงานนี้หรือไม่', 'career-manager' ); ?>
				</div>
			</div>
			<div>
				<select id="career-pdf-preview-select" name="career_pdf_preview" style="min-width: 260px; font-size: 13px; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
					<option value="default" <?php selected( $pdf_preview_opt, 'default' ); ?>><?php esc_html_e( 'ตามค่าเริ่มต้นของระบบ (Global Setting)', 'career-manager' ); ?></option>
					<option value="1" <?php selected( $pdf_preview_opt, '1' ); ?>><?php esc_html_e( 'เปิดแสดงตัวอย่าง PDF (Always Enabled)', 'career-manager' ); ?></option>
					<option value="0" <?php selected( $pdf_preview_opt, '0' ); ?>><?php esc_html_e( 'ปิดแสดงตัวอย่าง PDF (Disabled)', 'career-manager' ); ?></option>
				</select>
			</div>
		</div>

		<p style="margin-top: 0; color: #64748b; font-size: 13px;">
			<?php esc_html_e( 'อัปโหลดหรือเลือกไฟล์เอกสารรับสมัครงาน เช่น *.pdf, *.rar, *.zip, *.docx, *.xlsx เพื่อแนบในประกาศ และสร้างลิงก์ดาวน์โหลดอัตโนมัติ', 'career-manager' ); ?>
		</p>

		<div id="career-files-list-wrap" class="career-files-list-wrap" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
			<?php foreach ( $attachments as $index => $file ) : ?>
				<?php
				$file_id      = isset( $file['id'] ) ? absint( $file['id'] ) : 0;
				$file_title   = isset( $file['title'] ) ? (string) $file['title'] : '';
				$file_url     = isset( $file['url'] ) ? (string) $file['url'] : '';
				$file_ext     = strtoupper( isset( $file['ext'] ) ? (string) $file['ext'] : 'FILE' );
				$file_size    = isset( $file['size'] ) ? (string) $file['size'] : '';
				$download_url = $file_id > 0 ? site_url( '/?career_download_file=' . $file_id ) : $file_url;
				?>
				<div id="career-file-item-row-<?php echo esc_attr( (string) $index ); ?>" class="career-file-item-row" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
					<div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
						<div style="display: flex; align-items: center; gap: 10px;">
							<span style="background: #0f72dc; color: #ffffff; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; text-transform: uppercase;">
								<?php echo esc_html( $file_ext ); ?>
							</span>
							<strong style="font-size: 14px; color: #0f294a;"><?php echo esc_html( $file_title ); ?></strong>
							<?php if ( '' !== $file_size ) : ?>
								<span style="font-size: 12px; color: #64748b;">(<?php echo esc_html( $file_size ); ?>)</span>
							<?php endif; ?>
						</div>
						<div style="display: flex; align-items: center; gap: 8px;">
							<?php if ( 'PDF' === $file_ext && '' !== $file_url ) : ?>
								<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="color: #0f72dc; border-color: #0f72dc; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
									<span class="dashicons dashicons-pdf" style="font-size: 15px; width: 15px; height: 15px; line-height: 1.3;"></span>
									<?php esc_html_e( 'ดูตัวอย่าง PDF', 'career-manager' ); ?>
								</a>
							<?php endif; ?>
							<button type="button" class="button button-link-delete career-remove-file-btn" style="color: #dc2626; font-weight: 600;">
								<?php esc_html_e( 'ลบไฟล์แนบนี้', 'career-manager' ); ?>
							</button>
						</div>
					</div>

					<div style="display: flex; flex-direction: column; gap: 8px;">
						<div style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
							<span style="font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; width: 170px;"><?php esc_html_e( 'Generated Direct Link:', 'career-manager' ); ?></span>
							<input type="text" readonly value="<?php echo esc_url( $file_url ); ?>" class="career-file-url-input" style="flex: 1; background: #ffffff; font-family: monospace; font-size: 12px; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1;" />
							<button type="button" class="button button-secondary career-copy-url-btn" style="font-weight: 700; color: #0f72dc; border-color: #0f72dc;">
								[Copy] <?php esc_html_e( 'คัดลอกลิงก์ตรง (Direct URL)', 'career-manager' ); ?>
							</button>
						</div>

						<div style="display: flex; align-items: center; gap: 8px; background: #f0f7ff; padding: 8px 12px; border-radius: 8px; border: 1px solid #bae6fd;">
							<span style="font-size: 12px; font-weight: 700; color: #0369a1; white-space: nowrap; width: 170px;"><?php esc_html_e( 'Generated Download Link:', 'career-manager' ); ?></span>
							<input type="text" readonly value="<?php echo esc_url( $download_url ); ?>" class="career-file-url-input" style="flex: 1; background: #ffffff; font-family: monospace; font-size: 12px; padding: 4px 8px; border-radius: 6px; border: 1px solid #7dd3fc; color: #0f72dc; font-weight: 700;" />
							<button type="button" class="button button-secondary career-copy-url-btn" style="font-weight: 700; color: #0f72dc; border-color: #0f72dc;">
								<span class="dashicons dashicons-download" style="line-height: 1.3;"></span> [Copy] <?php esc_html_e( 'คัดลอกลิงก์ดาวน์โหลด (Force Download)', 'career-manager' ); ?>
							</button>
						</div>
					</div>

					<input type="hidden" name="career_file_attachments[<?php echo esc_attr( (string) $index ); ?>][id]" value="<?php echo esc_attr( (string) $file_id ); ?>" />
					<input type="hidden" name="career_file_attachments[<?php echo esc_attr( (string) $index ); ?>][title]" value="<?php echo esc_attr( $file_title ); ?>" />
					<input type="hidden" name="career_file_attachments[<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_url( $file_url ); ?>" />
					<input type="hidden" name="career_file_attachments[<?php echo esc_attr( (string) $index ); ?>][ext]" value="<?php echo esc_attr( strtolower( $file_ext ) ); ?>" />
					<input type="hidden" name="career_file_attachments[<?php echo esc_attr( (string) $index ); ?>][size]" value="<?php echo esc_attr( $file_size ); ?>" />
				</div>
			<?php endforeach; ?>
		</div>

		<button id="career-add-file-btn" type="button" class="button button-primary button-large" style="font-weight: 700;">
			<span class="dashicons dashicons-upload" style="line-height: 1.3; font-size: 16px;"></span>
			<?php esc_html_e( '+ เพิ่มไฟล์แนบ / อัปโหลดไฟล์ (*.pdf, *.rar, *.zip, *.docx...)', 'career-manager' ); ?>
		</button>
	</div>
	<?php
}

/**
 * Save Career Metabox Data.
 *
 * @param int $post_id Post ID.
 */
function career_save_metabox_data( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'career' !== get_post_type( $post_id ) ) {
		return;
	}

	$valid_nonce = isset( $_POST['career_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['career_meta_nonce'] ) ), 'career_save_meta' );
	if ( ! $valid_nonce ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// 0. Job Card Badges & Location Override
	$is_urgent     = isset( $_POST['career_is_urgent'] ) ? '1' : '0';
	$is_app_closed = isset( $_POST['career_application_closed'] ) ? '1' : '0';
	update_post_meta( $post_id, '_career_is_urgent', $is_urgent );
	update_post_meta( $post_id, '_career_application_closed', $is_app_closed );

	if ( isset( $_POST['career_location_custom'] ) ) {
		update_post_meta( $post_id, '_career_location_custom', sanitize_text_field( wp_unslash( $_POST['career_location_custom'] ) ) );
	}

	if ( isset( $_POST['career_notify_email'] ) ) {
		update_post_meta( $post_id, '_career_notify_email', sanitize_text_field( wp_unslash( $_POST['career_notify_email'] ) ) );
	}

	// 1. Comment Status
	if ( isset( $_POST['career_comment_status'] ) ) {
		$status = sanitize_key( wp_unslash( $_POST['career_comment_status'] ) );
		if ( in_array( $status, array( 'open', 'closed' ), true ) ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'comment_status' => $status ), array( 'ID' => $post_id ) );
		}
	}

	// 2. Expiration Date
	$enable_exp = isset( $_POST['career_enable_expiration'] ) ? '1' : '0';
	update_post_meta( $post_id, '_career_enable_expiration', $enable_exp );

	if ( isset( $_POST['career_expiration_date'] ) ) {
		update_post_meta( $post_id, '_career_expiration_date', sanitize_text_field( wp_unslash( $_POST['career_expiration_date'] ) ) );
	}

	if ( isset( $_POST['career_expiration_action'] ) ) {
		$exp_act = sanitize_key( wp_unslash( $_POST['career_expiration_action'] ) );
		if ( in_array( $exp_act, array( 'draft', 'private' ), true ) ) {
			update_post_meta( $post_id, '_career_expiration_action', $exp_act );
		}
	}

	// 3. File Attachments
	if ( isset( $_POST['career_file_attachments'] ) && is_array( $_POST['career_file_attachments'] ) ) {
		$clean_files = array();
		foreach ( wp_unslash( $_POST['career_file_attachments'] ) as $file ) {
			if ( is_array( $file ) && ! empty( $file['url'] ) ) {
				$clean_files[] = array(
					'id'    => absint( $file['id'] ?? 0 ),
					'title' => sanitize_text_field( (string) ( $file['title'] ?? '' ) ),
					'url'   => esc_url_raw( (string) ( $file['url'] ?? '' ) ),
					'ext'   => sanitize_key( (string) ( $file['ext'] ?? 'file' ) ),
					'size'  => sanitize_text_field( (string) ( $file['size'] ?? '' ) ),
				);
			}
		}
		update_post_meta( $post_id, '_career_file_attachments', $clean_files );
	} else {
		delete_post_meta( $post_id, '_career_file_attachments' );
	}

	// 4. PDF Preview Setting
	if ( isset( $_POST['career_pdf_preview'] ) ) {
		$pdf_preview_val = sanitize_text_field( wp_unslash( $_POST['career_pdf_preview'] ) );
		if ( in_array( $pdf_preview_val, array( 'default', '1', '0' ), true ) ) {
			update_post_meta( $post_id, '_career_pdf_preview', $pdf_preview_val );
		}
	}
}
add_action( 'save_post', 'career_save_metabox_data' );

/**
 * Hourly Cron Task to Auto-Expire Career Posts.
 */
function career_check_expired_posts(): void {
	global $wpdb;
	$now_iso = current_time( 'Y-m-d\TH:i' );

	$expired_posts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, pm_act.meta_value as action
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm_exp ON p.ID = pm_exp.post_id AND pm_exp.meta_key = '_career_enable_expiration' AND pm_exp.meta_value = '1'
			 INNER JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = '_career_expiration_date'
			 LEFT JOIN {$wpdb->postmeta} pm_act ON p.ID = pm_act.post_id AND pm_act.meta_key = '_career_expiration_action'
			 WHERE p.post_type = 'career'
			   AND p.post_status = 'publish'
			   AND pm_date.meta_value != ''
			   AND pm_date.meta_value <= %s",
			$now_iso
		)
	);

	if ( ! empty( $expired_posts ) ) {
		foreach ( $expired_posts as $item ) {
			$new_status = in_array( $item->action, array( 'draft', 'private' ), true ) ? $item->action : 'draft';
			wp_update_post(
				array(
					'ID'          => (int) $item->ID,
					'post_status' => $new_status,
				)
			);
		}
	}
}
add_action( 'career_check_expired_cron', 'career_check_expired_posts' );

/**
 * Exclude expired career items from frontend queries.
 *
 * @param \WP_Query $query Query object.
 */
function career_filter_expired_frontend( \WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( 'career' === $post_type || is_post_type_archive( 'career' ) || is_tax( array( 'career_category', 'career_location', 'career_type' ) ) ) {
		$now_iso    = current_time( 'Y-m-d\TH:i' );
		$meta_query = (array) $query->get( 'meta_query' );

		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => '_career_enable_expiration',
				'value'   => '1',
				'compare' => '!=',
			),
			array(
				'key'     => '_career_enable_expiration',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_career_expiration_date',
				'value'   => $now_iso,
				'compare' => '>',
			),
		);

		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'career_filter_expired_frontend' );

/**
 * Enforce 404 Not Found on expired single career posts for visitors.
 */
function career_enforce_expired_404(): void {
	if ( is_singular( 'career' ) && ! current_user_can( 'edit_posts' ) ) {
		$post_id    = get_queried_object_id();
		$enable_exp = (string) get_post_meta( $post_id, '_career_enable_expiration', true );
		$exp_date   = (string) get_post_meta( $post_id, '_career_expiration_date', true );
		$is_expired = ( '1' === $enable_exp && '' !== $exp_date && strtotime( $exp_date ) <= (int) current_time( 'timestamp' ) );

		if ( $is_expired ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			$tpl_404 = get_query_template( '404' );
			if ( $tpl_404 ) {
				include $tpl_404;
			}
			exit;
		}
	}
}
add_action( 'template_redirect', 'career_enforce_expired_404' );
