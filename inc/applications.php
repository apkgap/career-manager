<?php
/**
 * Job Application System:
 * 1. Custom Post Type `career_application` (Submenu under `career` menu) for logging applications
 * 2. Admin Metabox & Custom Columns to view applicant details, resume link, and status
 * 3. AJAX Submission Handler (`career_submit_application`) with Nonce, Validation, File Upload (Resume/CV),
 *    CPT Log Creation, and Email Notification (`wp_mail()` with attachment + applicant confirmation)
 * 4. Frontend Apply Now Modal Dialog
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register `career_application` Custom Post Type to log submitted job applications.
 */
function career_register_application_cpt(): void {
	if ( post_type_exists( 'career_application' ) ) {
		return;
	}

	$labels = array(
		'name'               => _x( 'Job Applications (ใบสมัครงาน)', 'Post Type General Name', 'career-manager' ),
		'singular_name'      => _x( 'Job Application', 'Post Type Singular Name', 'career-manager' ),
		'menu_name'          => career_text( 'รายการใบสมัครงาน (Logs)', 'Applications Log' ),
		'all_items'          => career_text( 'รายการใบสมัครงาน (Logs)', 'Applications Log' ),
		'view_item'          => career_text( 'ดูใบสมัครงาน', 'View Application' ),
		'edit_item'          => career_text( 'รายละเอียดใบสมัครงาน', 'Application Details' ),
		'search_items'       => career_text( 'ค้นหาผู้สมัคร', 'Search Applications' ),
		'not_found'          => career_text( 'ยังไม่มีรายการใบสมัครงาน', 'No applications found' ),
		'not_found_in_trash' => career_text( 'ไม่พบใบสมัครงานในถังขยะ', 'No applications found in Trash' ),
	);

	$args = array(
		'label'               => career_text( 'ใบสมัครงาน', 'Job Applications' ),
		'labels'              => $labels,
		'supports'            => array( 'title' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=career',
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
		'capabilities'        => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap'        => true,
	);

	register_post_type( 'career_application', $args );
}
add_action( 'init', 'career_register_application_cpt', 6 );

/**
 * Register custom columns for `career_application` Admin List Table.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function career_application_columns( array $columns ): array {
	$new_cols = array(
		'cb'               => $columns['cb'] ?? '<input type="checkbox" />',
		'title'            => __( 'ชื่อผู้สมัคร (Applicant)', 'career-manager' ),
		'app_job_position' => __( 'ตำแหน่งที่สมัคร (Job Position)', 'career-manager' ),
		'app_email'        => __( 'อีเมล (Email)', 'career-manager' ),
		'app_phone'        => __( 'เบอร์โทรศัพท์ (Phone)', 'career-manager' ),
		'app_resume'       => __( 'ไฟล์เรซูเม่ (Resume/CV)', 'career-manager' ),
		'app_status'       => __( 'สถานะ (Status)', 'career-manager' ),
		'date'             => __( 'วันที่ส่งใบสมัคร (Submitted Date)', 'career-manager' ),
	);
	return $new_cols;
}
add_filter( 'manage_career_application_posts_columns', 'career_application_columns' );

/**
 * Render custom column content for `career_application` Admin List Table.
 *
 * @param string $column  Column slug.
 * @param int    $post_id Application Post ID.
 */
function career_application_render_columns( string $column, int $post_id ): void {
	if ( 'app_job_position' === $column ) {
		$job_id    = (int) get_post_meta( $post_id, '_career_app_job_id', true );
		$job_title = (string) get_post_meta( $post_id, '_career_app_job_title', true );
		if ( $job_id > 0 && get_post( $job_id ) ) {
			echo '<a href="' . esc_url( (string) get_edit_post_link( $job_id ) ) . '" style="font-weight:700; color:#800020;">' . esc_html( $job_title ?: get_the_title( $job_id ) ) . '</a>';
		} else {
			echo '<strong>' . esc_html( $job_title ?: '—' ) . '</strong>';
		}
	}

	if ( 'app_email' === $column ) {
		$email = (string) get_post_meta( $post_id, '_career_app_email', true );
		if ( '' !== $email ) {
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		} else {
			echo '—';
		}
	}

	if ( 'app_phone' === $column ) {
		$phone = (string) get_post_meta( $post_id, '_career_app_phone', true );
		echo esc_html( '' !== $phone ? $phone : '—' );
	}

	if ( 'app_resume' === $column ) {
		$resume_url = (string) get_post_meta( $post_id, '_career_app_resume_url', true );
		if ( '' !== $resume_url ) {
			echo '<a class="button button-small" href="' . esc_url( $resume_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#800020; border-color:#800020; font-weight:600;">เปิดไฟล์แนบ / Resume</a>';
		} else {
			echo '<span style="color:#94a3b8;">ไม่มีไฟล์แนบ</span>';
		}
	}

	if ( 'app_status' === $column ) {
		$status = (string) get_post_meta( $post_id, '_career_app_status', true ) ?: 'new';
		$badges = array(
			'new'         => array( 'label' => 'ใบสมัครใหม่ (New)', 'bg' => '#fef2f2', 'color' => '#991b1b' ),
			'reviewed'    => array( 'label' => 'ตรวจสอบแล้ว (Reviewed)', 'bg' => '#eff6ff', 'color' => '#1d4ed8' ),
			'shortlisted' => array( 'label' => 'นัดสัมภาษณ์ (Shortlisted)', 'bg' => '#f0fdf4', 'color' => '#15803d' ),
			'rejected'    => array( 'label' => 'ไม่ผ่าน (Rejected)', 'bg' => '#f1f5f9', 'color' => '#475569' ),
		);
		$b      = $badges[ $status ] ?? $badges['new'];
		echo '<span style="background:' . esc_attr( $b['bg'] ) . '; color:' . esc_attr( $b['color'] ) . '; padding:4px 10px; border-radius:9999px; font-weight:700; font-size:11px; display:inline-block;">' . esc_html( $b['label'] ) . '</span>';
	}
}
add_action( 'manage_career_application_posts_custom_column', 'career_application_render_columns', 10, 2 );

/**
 * Filter `career_application` list table by Job Position in Admin.
 *
 * @param string $post_type Current post type.
 */
function career_application_admin_filters( string $post_type ): void {
	if ( 'career_application' !== $post_type ) {
		return;
	}

	$selected_job = isset( $_GET['filter_app_job_id'] ) ? absint( $_GET['filter_app_job_id'] ) : 0;
	$jobs         = get_posts(
		array(
			'post_type'      => 'career',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	echo '<select id="career-filter-app-job" name="filter_app_job_id">';
	echo '<option value="0">' . esc_html__( '-- กรองตามตำแหน่งงานทั้งหมด --', 'career-manager' ) . '</option>';
	foreach ( $jobs as $j ) {
		echo '<option value="' . esc_attr( (string) $j->ID ) . '"' . selected( $selected_job, (int) $j->ID, false ) . '>' . esc_html( $j->post_title ) . '</option>';
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'career_application_admin_filters' );

/**
 * Apply Job Position filter on `career_application` Admin List Table.
 *
 * @param \WP_Query $query Query instance.
 */
function career_application_apply_admin_filters( \WP_Query $query ): void {
	global $pagenow;
	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
		return;
	}

	if ( 'career_application' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! empty( $_GET['filter_app_job_id'] ) && absint( $_GET['filter_app_job_id'] ) > 0 ) {
		$query->set( 'meta_key', '_career_app_job_id' );
		$query->set( 'meta_value', absint( $_GET['filter_app_job_id'] ) );
	}
}
add_action( 'parse_query', 'career_application_apply_admin_filters' );

/**
 * Register Metabox to view & manage Application details inside `career_application` edit screen.
 */
function career_register_application_metaboxes(): void {
	add_meta_box(
		'career_application_details_mb',
		__( 'ข้อมูลผู้สมัครงานและไฟล์แนบ (Applicant Information & Resume)', 'career-manager' ),
		'career_render_application_details_metabox',
		'career_application',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'career_register_application_metaboxes' );

/**
 * Render Application Details Metabox in Admin.
 *
 * @param \WP_Post $post Current application post object.
 */
function career_render_application_details_metabox( \WP_Post $post ): void {
	wp_nonce_field( 'career_save_app_status', 'career_app_status_nonce' );

	$job_id     = (int) get_post_meta( $post->ID, '_career_app_job_id', true );
	$job_title  = (string) get_post_meta( $post->ID, '_career_app_job_title', true );
	$full_name  = (string) get_post_meta( $post->ID, '_career_app_name', true );
	$email      = (string) get_post_meta( $post->ID, '_career_app_email', true );
	$phone      = (string) get_post_meta( $post->ID, '_career_app_phone', true );
	$salary     = (string) get_post_meta( $post->ID, '_career_app_salary', true );
	$message    = (string) get_post_meta( $post->ID, '_career_app_message', true );
	$resume_url = (string) get_post_meta( $post->ID, '_career_app_resume_url', true );
	$status     = (string) get_post_meta( $post->ID, '_career_app_status', true ) ?: 'new';
	$submitted  = get_the_date( 'd/m/Y H:i:s', $post->ID );
	?>
	<div id="career-app-detail-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
		<div style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding-bottom:14px; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
			<div>
				<span style="font-size:12px; color:#64748b; display:block;"><?php esc_html_e( 'ตำแหน่งที่สมัคร (Applied Position):', 'career-manager' ); ?></span>
				<strong style="font-size:18px; color:#800020;"><?php echo esc_html( $job_title ?: '—' ); ?></strong>
				<?php if ( $job_id > 0 && get_post( $job_id ) ) : ?>
					<a href="<?php echo esc_url( (string) get_permalink( $job_id ) ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left:8px; font-size:12px;"><?php esc_html_e( '(ดูหน้าประกาศงาน)', 'career-manager' ); ?></a>
				<?php endif; ?>
			</div>

			<div style="display:flex; align-items:center; gap:8px;">
				<label for="career-app-status-select" style="font-weight:700; color:#1e293b;"><?php esc_html_e( 'สถานะใบสมัคร:', 'career-manager' ); ?></label>
				<select id="career-app-status-select" name="career_app_status" style="padding:6px 12px; border-radius:6px; border:1px solid #cbd5e1; font-weight:600;">
					<option value="new" <?php selected( $status, 'new' ); ?>><?php esc_html_e( 'ใบสมัครใหม่ (New)', 'career-manager' ); ?></option>
					<option value="reviewed" <?php selected( $status, 'reviewed' ); ?>><?php esc_html_e( 'ตรวจสอบแล้ว (Reviewed)', 'career-manager' ); ?></option>
					<option value="shortlisted" <?php selected( $status, 'shortlisted' ); ?>><?php esc_html_e( 'นัดสัมภาษณ์ (Shortlisted)', 'career-manager' ); ?></option>
					<option value="rejected" <?php selected( $status, 'rejected' ); ?>><?php esc_html_e( 'ไม่ผ่าน (Rejected)', 'career-manager' ); ?></option>
				</select>
			</div>
		</div>

		<table class="form-table" role="presentation" style="margin-top:0;">
			<tr>
				<th scope="row" style="width:200px;"><?php esc_html_e( 'ชื่อ - นามสกุล (Full Name):', 'career-manager' ); ?></th>
				<td><strong><?php echo esc_html( $full_name ); ?></strong></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'อีเมล (Email):', 'career-manager' ); ?></th>
				<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'เบอร์โทรศัพท์ (Phone):', 'career-manager' ); ?></th>
				<td><a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a></td>
			</tr>
			<?php if ( '' !== $salary ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'เงินเดือนที่คาดหวัง (Expected Salary):', 'career-manager' ); ?></th>
					<td><?php echo esc_html( $salary ); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'วันและเวลาที่ส่งใบสมัคร:', 'career-manager' ); ?></th>
				<td><?php echo esc_html( $submitted ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'รายละเอียดเพิ่มเติม / แนะนำตัว:', 'career-manager' ); ?></th>
				<td>
					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; white-space:pre-wrap; line-height:1.6;">
						<?php echo '' !== $message ? esc_html( $message ) : '—'; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'ไฟล์เรซูเม่ / เอกสารแนบ (Resume/CV):', 'career-manager' ); ?></th>
				<td>
					<?php if ( '' !== $resume_url ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( $resume_url ); ?>" target="_blank" rel="noopener noreferrer" style="background:#800020; border-color:#5c0017; font-weight:600;">
							<?php esc_html_e( 'เปิดดู / ดาวน์โหลดไฟล์เรซูเม่', 'career-manager' ); ?>
						</a>
					<?php else : ?>
						<span style="color:#64748b;"><?php esc_html_e( 'ผู้สมัครไม่ได้แนบไฟล์', 'career-manager' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

/**
 * Save Application Status when updated in Admin.
 *
 * @param int $post_id Application Post ID.
 */
function career_save_application_status( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'career_application' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['career_app_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['career_app_status_nonce'] ) ), 'career_save_app_status' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['career_app_status'] ) ) {
		$status  = sanitize_key( wp_unslash( $_POST['career_app_status'] ) );
		$allowed = array( 'new', 'reviewed', 'shortlisted', 'rejected' );
		if ( in_array( $status, $allowed, true ) ) {
			update_post_meta( $post_id, '_career_app_status', $status );
		}
	}
}
add_action( 'save_post', 'career_save_application_status' );

/**
 * AJAX Handler: Submit Job Application (`career_submit_application`).
 *
 * 1. Validates Nonce & Job Status (checks if job exists and is accepting applications)
 * 2. Sanitizes & validates applicant Name, Email (`is_email()`), Phone (`0-9 + - ( )`), Message
 * 3. Handles optional Resume/CV upload (`pdf`, `doc`, `docx`, `jpg`, `png`, `zip`) via `wp_check_filetype_and_ext()` + `wp_handle_upload()`
 * 4. Inserts a new `career_application` post log with metadata
 * 5. Sends HTML Email notification to HR / configured recipient (with Resume attached) + confirmation email to applicant
 */
function career_ajax_submit_application(): void {
	check_ajax_referer( 'career_apply_nonce', 'nonce' );

	$job_id  = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
	$name    = isset( $_POST['applicant_name'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_name'] ) ) : '';
	$email   = isset( $_POST['applicant_email'] ) ? sanitize_email( wp_unslash( $_POST['applicant_email'] ) ) : '';
	$phone   = isset( $_POST['applicant_phone'] ) ? preg_replace( '/[^0-9+\-\(\)\s]/', '', sanitize_text_field( wp_unslash( $_POST['applicant_phone'] ) ) ) : '';
	$salary  = isset( $_POST['applicant_salary'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_salary'] ) ) : '';
	$message = isset( $_POST['applicant_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['applicant_message'] ) ) : '';
	$consent = isset( $_POST['applicant_consent'] ) ? sanitize_key( wp_unslash( $_POST['applicant_consent'] ) ) : '';

	$phone = is_string( $phone ) ? trim( $phone ) : '';

	if ( $job_id <= 0 || 'career' !== get_post_type( $job_id ) ) {
		wp_send_json_error(
			array( 'message' => career_text( 'ไม่พบตำแหน่งงานที่ต้องการสมัคร', 'Invalid job position.' ) ),
			400
		);
	}

	$is_closed = '1' === (string) get_post_meta( $job_id, '_career_application_closed', true );
	if ( $is_closed ) {
		wp_send_json_error(
			array( 'message' => career_text( 'ตำแหน่งงานนี้ปิดรับสมัครแล้ว', 'This position is not currently accepting applications.' ) ),
			400
		);
	}

	if ( '' === $name || '' === $email || '' === $phone ) {
		wp_send_json_error(
			array( 'message' => career_text( 'กรุณากรอกชื่อ-นามสกุล อีเมล และเบอร์โทรศัพท์ให้ครบถ้วน', 'Please fill in your full name, email, and phone number.' ) ),
			400
		);
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error(
			array( 'message' => career_text( 'รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 'Please enter a valid email address.' ) ),
			400
		);
	}

	if ( '1' !== $consent ) {
		wp_send_json_error(
			array( 'message' => career_text( 'กรุณากดยอมรับเงื่อนไขการส่งข้อมูลสมัครงาน', 'Please accept the application terms before submitting.' ) ),
			400
		);
	}

	$resume_url  = '';
	$resume_path = '';

	// Handle Optional Resume / CV Upload
	if ( ! empty( $_FILES['applicant_resume'] ) && ! empty( $_FILES['applicant_resume']['name'] ) ) {
		$file = $_FILES['applicant_resume']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			wp_send_json_error(
				array( 'message' => career_text( 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์แนบ กรุณาลองใหม่อีกครั้ง', 'File upload error. Please try again.' ) ),
				400
			);
		}

		// Max 10 MB
		$max_bytes = 10 * 1024 * 1024;
		if ( isset( $file['size'] ) && (int) $file['size'] > $max_bytes ) {
			wp_send_json_error(
				array( 'message' => career_text( 'ขนาดไฟล์แนบต้องไม่เกิน 10 MB', 'Resume file size must not exceed 10 MB.' ) ),
				400
			);
		}

		$allowed_mimes = array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'zip'  => 'application/zip',
		);

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
		if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
			wp_send_json_error(
				array( 'message' => career_text( 'รองรับเฉพาะไฟล์ .pdf, .doc, .docx, .jpg, .png หรือ .zip เท่านั้น', 'Only PDF, DOC, DOCX, JPG, PNG, or ZIP files are allowed.' ) ),
				400
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$uploaded = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $allowed_mimes,
			)
		);

		if ( isset( $uploaded['error'] ) ) {
			wp_send_json_error(
				array( 'message' => sanitize_text_field( (string) $uploaded['error'] ) ),
				400
			);
		}

		$resume_url  = isset( $uploaded['url'] ) ? esc_url_raw( (string) $uploaded['url'] ) : '';
		$resume_path = isset( $uploaded['file'] ) ? (string) $uploaded['file'] : '';
	}

	$job_title = get_the_title( $job_id );
	$log_title = sprintf( '%s — %s', $name, $job_title );

	// 1. Insert Application Log into `career_application` CPT
	$app_id = wp_insert_post(
		array(
			'post_type'   => 'career_application',
			'post_status' => 'publish',
			'post_title'  => $log_title,
		),
		true
	);

	if ( is_wp_error( $app_id ) || ! $app_id ) {
		wp_send_json_error(
			array( 'message' => career_text( 'ไม่สามารถบันทึกข้อมูลใบสมัครได้ กรุณาลองใหม่อีกครั้ง', 'Could not save your application. Please try again.' ) ),
			500
		);
	}

	update_post_meta( (int) $app_id, '_career_app_job_id', $job_id );
	update_post_meta( (int) $app_id, '_career_app_job_title', $job_title );
	update_post_meta( (int) $app_id, '_career_app_name', $name );
	update_post_meta( (int) $app_id, '_career_app_email', $email );
	update_post_meta( (int) $app_id, '_career_app_phone', $phone );
	update_post_meta( (int) $app_id, '_career_app_salary', $salary );
	update_post_meta( (int) $app_id, '_career_app_message', $message );
	update_post_meta( (int) $app_id, '_career_app_resume_url', $resume_url );
	update_post_meta( (int) $app_id, '_career_app_status', 'new' );

	// 2. Send Email Notification to HR / Admin Recipient
	$settings        = career_get_settings();
	$job_notify_mail = trim( (string) get_post_meta( $job_id, '_career_notify_email', true ) );
	$global_mail     = trim( (string) ( $settings['notify_email'] ?? '' ) );
	$admin_mail      = (string) get_option( 'admin_email' );

	$recipient_raw = $job_notify_mail ?: ( $global_mail ?: $admin_mail );
	$recipients    = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', $recipient_raw ) ) ), 'is_email' );
	if ( empty( $recipients ) && is_email( $admin_mail ) ) {
		$recipients = array( $admin_mail );
	}

	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject   = sprintf( '[%s] ใบสมัครงานใหม่: %s — %s', $site_name, $job_title, $name );

	$admin_edit_url = admin_url( 'post.php?post=' . (int) $app_id . '&action=edit' );

	ob_start();
	?>
	<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 620px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
		<div style="background: #800020; color: #ffffff; padding: 18px 24px;">
			<h2 style="margin: 0; font-size: 18px;">ใบสมัครงานใหม่ (New Job Application)</h2>
			<p style="margin: 4px 0 0; font-size: 13px; opacity: 0.9;">ตำแหน่ง: <?php echo esc_html( $job_title ); ?></p>
		</div>
		<div style="padding: 20px 24px; background: #ffffff; color: #1e293b; font-size: 14px; line-height: 1.6;">
			<p style="margin-top: 0;">มีผู้ส่งใบสมัครงานใหม่ผ่านทางเว็บไซต์ โดยมีรายละเอียดดังนี้:</p>
			<table style="width: 100%; border-collapse: collapse; margin: 14px 0;">
				<tr>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #64748b; width: 170px;"><strong>ตำแหน่งที่สมัคร:</strong></td>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><strong><?php echo esc_html( $job_title ); ?></strong></td>
				</tr>
				<tr>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;"><strong>ชื่อ - นามสกุล:</strong></td>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><?php echo esc_html( $name ); ?></td>
				</tr>
				<tr>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;"><strong>อีเมล:</strong></td>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
				</tr>
				<tr>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;"><strong>เบอร์โทรศัพท์:</strong></td>
					<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><?php echo esc_html( $phone ); ?></td>
				</tr>
				<?php if ( '' !== $salary ) : ?>
					<tr>
						<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;"><strong>เงินเดือนที่คาดหวัง:</strong></td>
						<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><?php echo esc_html( $salary ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( '' !== $resume_url ) : ?>
					<tr>
						<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;"><strong>ไฟล์เรซูเม่ (Resume):</strong></td>
						<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><a href="<?php echo esc_url( $resume_url ); ?>"><?php echo esc_html( basename( $resume_url ) ); ?></a></td>
					</tr>
				<?php endif; ?>
			</table>

			<?php if ( '' !== $message ) : ?>
				<div style="margin-top: 14px;">
					<strong style="color: #475569; display: block; margin-bottom: 6px;">รายละเอียดเพิ่มเติม / แนะนำตัว:</strong>
					<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; white-space: pre-wrap;"><?php echo esc_html( $message ); ?></div>
				</div>
			<?php endif; ?>

			<div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
				<a href="<?php echo esc_url( $admin_edit_url ); ?>" style="display: inline-block; background: #800020; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; font-size: 13px;">ดูใบสมัครในระบบหลังบ้าน (Application Log)</a>
			</div>
		</div>
	</div>
	<?php
	$email_body = (string) ob_get_clean();

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$attachments = array();
	if ( '' !== $resume_path && file_exists( $resume_path ) ) {
		$attachments[] = $resume_path;
	}

	if ( ! empty( $recipients ) ) {
		wp_mail( $recipients, $subject, $email_body, $headers, $attachments );
	}

	// Optional confirmation email to applicant
	$applicant_subject = sprintf( '[%s] ยืนยันการรับใบสมัครงานตำแหน่ง %s', $site_name, $job_title );
	$applicant_body    = sprintf(
		'<p>เรียนคุณ <strong>%s</strong>,</p><p>ระบบได้รับข้อมูลการสมัครงานสำหรับตำแหน่ง <strong>%s</strong> ของท่านเรียบร้อยแล้ว เจ้าหน้าที่ฝ่ายทรัพยากรบุคคลจะพิจารณาข้อมูลและติดต่อกลับหาท่านโดยเร็วที่สุด</p><p>ขอแสดงความนับถือ,<br/>%s</p>',
		esc_html( $name ),
		esc_html( $job_title ),
		esc_html( $site_name )
	);
	wp_mail( $email, $applicant_subject, $applicant_body, array( 'Content-Type: text/html; charset=UTF-8' ) );

	wp_send_json_success(
		array(
			'application_id' => (int) $app_id,
			'message'        => career_text(
				'ส่งใบสมัครงานเรียบร้อยแล้ว! ระบบได้บันทึกข้อมูลและส่งอีเมลแจ้งเจ้าหน้าที่เรียบร้อยแล้ว',
				'Your application has been submitted successfully!'
			),
		)
	);
}
add_action( 'wp_ajax_career_submit_application', 'career_ajax_submit_application' );
add_action( 'wp_ajax_nopriv_career_submit_application', 'career_ajax_submit_application' );

/**
 * Render the Apply Now Popup Modal on Single Career Pages.
 */
function career_render_apply_modal_footer(): void {
	if ( ! is_singular( 'career' ) ) {
		return;
	}

	$post_id       = (int) get_queried_object_id();
	$job_title     = get_the_title( $post_id );
	$is_app_closed = '1' === (string) get_post_meta( $post_id, '_career_application_closed', true );

	if ( $is_app_closed ) {
		return;
	}
	?>
	<div id="career-apply-modal" class="career-apply-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="career-apply-modal-title">
		<div id="career-apply-modal-backdrop" class="career-apply-modal__backdrop js-career-close-apply-modal"></div>
		<div id="career-apply-modal-dialog" class="career-apply-modal__dialog">
			<div id="career-apply-modal-header" class="career-apply-modal__header">
				<div id="career-apply-modal-title-wrap" class="career-apply-modal__title-wrap">
					<span id="career-apply-modal-badge" class="career-apply-modal__badge"><?php echo esc_html( career_text( 'Apply for Position', 'Apply for Position' ) ); ?></span>
					<h3 id="career-apply-modal-title" class="career-apply-modal__title"><?php echo esc_html( $job_title ); ?></h3>
				</div>
				<button id="career-apply-modal-close-btn" type="button" class="career-apply-modal__close js-career-close-apply-modal" aria-label="<?php esc_attr_e( 'Close', 'career-manager' ); ?>">✕</button>
			</div>

			<form id="career-apply-form" class="career-apply-modal__form" enctype="multipart/form-data" novalidate>
				<input type="hidden" name="action" value="career_submit_application" />
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'career_apply_nonce' ) ); ?>" />
				<input id="career-apply-job-id" type="hidden" name="job_id" value="<?php echo esc_attr( (string) $post_id ); ?>" />

				<div id="career-apply-alert" class="career-apply-modal__alert is-hidden" role="alert"></div>

				<div id="career-apply-fields-grid" class="career-apply-modal__grid">
					<div id="career-field-name-wrap" class="career-apply-modal__field career-apply-modal__field--full">
						<label id="career-label-name" for="career-applicant-name" class="career-apply-modal__label">
							<?php echo esc_html( career_text( 'ชื่อ - นามสกุล (Full Name)', 'Full Name' ) ); ?> <span class="required">*</span>
						</label>
						<input id="career-applicant-name" type="text" name="applicant_name" class="career-apply-modal__input" required placeholder="<?php echo esc_attr( career_text( 'ระบุชื่อและนามสกุลของคุณ', 'Enter your full name' ) ); ?>" />
					</div>

					<div id="career-field-email-wrap" class="career-apply-modal__field">
						<label id="career-label-email" for="career-applicant-email" class="career-apply-modal__label">
							<?php echo esc_html( career_text( 'อีเมล (Email Address)', 'Email Address' ) ); ?> <span class="required">*</span>
						</label>
						<input id="career-applicant-email" type="email" name="applicant_email" class="career-apply-modal__input" required placeholder="name@example.com" />
					</div>

					<div id="career-field-phone-wrap" class="career-apply-modal__field">
						<label id="career-label-phone" for="career-applicant-phone" class="career-apply-modal__label">
							<?php echo esc_html( career_text( 'เบอร์โทรศัพท์ (Phone Number)', 'Phone Number' ) ); ?> <span class="required">*</span>
						</label>
						<input id="career-applicant-phone" type="tel" name="applicant_phone" class="career-apply-modal__input" required placeholder="08X-XXX-XXXX" />
					</div>

					<div id="career-field-salary-wrap" class="career-apply-modal__field career-apply-modal__field--full">
						<label id="career-label-salary" for="career-applicant-salary" class="career-apply-modal__label">
							<?php echo esc_html( career_text( 'เงินเดือนที่คาดหวัง (Expected Salary)', 'Expected Salary (Optional)' ) ); ?>
						</label>
						<input id="career-applicant-salary" type="text" name="applicant_salary" class="career-apply-modal__input" placeholder="<?php echo esc_attr( career_text( 'เช่น 35,000 บาท หรือ ตามตกลง', 'e.g. 35,000 THB or Negotiable' ) ); ?>" />
					</div>

					<div id="career-field-resume-wrap" class="career-apply-modal__field career-apply-modal__field--full">
						<label id="career-label-resume" for="career-applicant-resume" class="career-apply-modal__label">
							<?php echo esc_html( career_text( 'แนบไฟล์เรซูเม่ / CV (Resume / Portfolio)', 'Attach Resume / CV (.pdf, .doc, .docx, .zip - Max 10MB)' ) ); ?>
						</label>
						<input id="career-applicant-resume" type="file" name="applicant_resume" class="career-apply-modal__file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" />
					</div>

					<div id="career-field-message-wrap" class="career-apply-modal__field career-apply-modal__field--full">
						<label id="career-label-message" for="career-applicant-message" class="career-apply-modal__label">
							<?php echo esc_html( career_text( 'รายละเอียดเพิ่มเติม / แนะนำตัว (Message / Cover Letter)', 'Additional Details / Cover Letter' ) ); ?>
						</label>
						<textarea id="career-applicant-message" name="applicant_message" rows="4" class="career-apply-modal__textarea" placeholder="<?php echo esc_attr( career_text( 'ระบุประสบการณ์ทำงาน หรือข้อความถึงฝ่ายบุคคล...', 'Briefly introduce yourself or share relevant experience...' ) ); ?>"></textarea>
					</div>

					<div id="career-field-consent-wrap" class="career-apply-modal__field career-apply-modal__field--full">
						<label id="career-label-consent" for="career-applicant-consent" class="career-apply-modal__consent-label">
							<input id="career-applicant-consent" type="checkbox" name="applicant_consent" value="1" required />
							<span>
								<?php echo esc_html( career_text( 'ข้าพเจ้ายืนยันว่าข้อมูลข้างต้นเป็นความจริง และยินยอมให้บริษัทจัดเก็บข้อมูลเพื่อใช้ในการพิจารณารับสมัครงาน', 'I confirm that the information provided is accurate and consent to the processing of my application data.' ) ); ?>
								<span class="required">*</span>
							</span>
						</label>
					</div>
				</div>

				<div id="career-apply-modal-footer" class="career-apply-modal__footer">
					<button id="career-apply-cancel-btn" type="button" class="career-apply-modal__btn career-apply-modal__btn--cancel js-career-close-apply-modal">
						<?php echo esc_html( career_text( 'ยกเลิก (Cancel)', 'Cancel' ) ); ?>
					</button>
					<button id="career-apply-submit-btn" type="submit" class="career-apply-modal__btn career-apply-modal__btn--submit">
						<span id="career-apply-submit-text"><?php echo esc_html( career_text( 'ส่งใบสมัครงาน (Submit Application)', 'Submit Application' ) ); ?></span>
					</button>
				</div>
			</form>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'career_render_apply_modal_footer', 95 );

