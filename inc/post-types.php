<?php
/**
 * Custom Post Type (`career`) & Taxonomies (`career_category`, `career_location`, `career_type`) Registration,
 * Query Sorting, Default Terms Seeder, and Dummy Data Generator.
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register `career` Custom Post Type if not already registered.
 */
function career_register_cpt(): void {
	if ( post_type_exists( 'career' ) ) {
		return;
	}

	$labels = array(
		'name'                  => _x( 'Careers', 'Post Type General Name', 'career-manager' ),
		'singular_name'         => _x( 'Career', 'Post Type Singular Name', 'career-manager' ),
		'menu_name'             => career_text( 'ประกาศรับสมัครงาน', 'Careers' ),
		'name_admin_bar'        => career_text( 'ประกาศรับสมัครงาน', 'Career' ),
		'archives'              => career_text( 'รายการประกาศรับสมัครงาน', 'Career Archives' ),
		'attributes'            => career_text( 'คุณลักษณะ', 'Career Attributes' ),
		'parent_item_colon'     => career_text( 'รายการหลัก:', 'Parent Career:' ),
		'all_items'             => career_text( 'ประกาศทั้งหมด', 'All Careers' ),
		'add_new_item'          => career_text( 'เพิ่มประกาศใหม่', 'Add New Career' ),
		'add_new'               => career_text( 'เพิ่มใหม่', 'Add New' ),
		'new_item'              => career_text( 'ประกาศใหม่', 'New Career' ),
		'edit_item'             => career_text( 'แก้ไขประกาศ', 'Edit Career' ),
		'update_item'           => career_text( 'อัปเดตประกาศ', 'Update Career' ),
		'view_item'             => career_text( 'ดูประกาศ', 'View Career' ),
		'view_items'            => career_text( 'ดูรายการทั้งหมด', 'View Careers' ),
		'search_items'          => career_text( 'ค้นหาประกาศ', 'Search Career' ),
		'not_found'             => career_text( 'ไม่พบข้อมูล', 'Not found' ),
		'not_found_in_trash'    => career_text( 'ไม่พบในถังขยะ', 'Not found in Trash' ),
		'featured_image'        => career_text( 'รูปภาพหน้าปก', 'Featured Image' ),
		'set_featured_image'    => career_text( 'ตั้งค่ารูปภาพหน้าปก', 'Set featured image' ),
		'remove_featured_image' => career_text( 'ลบรูปภาพหน้าปก', 'Remove featured image' ),
		'use_featured_image'    => career_text( 'ใช้เป็นรูปภาพหน้าปก', 'Use as featured image' ),
		'insert_into_item'      => career_text( 'แทรกในเนื้อหา', 'Insert into career' ),
		'uploaded_to_this_item' => career_text( 'อัปโหลดไปยังรายการนี้', 'Uploaded to this career' ),
		'items_list'            => career_text( 'รายการประกาศ', 'Careers list' ),
		'items_list_navigation' => career_text( 'การนำทางรายการประกาศ', 'Careers list navigation' ),
		'filter_items_list'     => career_text( 'กรองรายการประกาศ', 'Filter careers list' ),
	);

	$args = array(
		'label'               => career_text( 'ประกาศรับสมัครงาน', 'Career' ),
		'description'         => career_text( 'รายการประกาศรับสมัครงาน (ร่วมงานกับเรา)', 'Career & Job Opportunities' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'comments' ),
		'taxonomies'          => array( 'career_category', 'career_location', 'career_type' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-businessperson',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => 'career',
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'show_in_rest'        => true,
		'rewrite'             => array(
			'slug'       => 'career',
			'with_front' => false,
		),
	);

	register_post_type( 'career', $args );
}
add_action( 'init', 'career_register_cpt', 5 );

/**
 * Register `career_category` (Category / Department), `career_location` (Location),
 * and `career_type` (Employment Type) Custom Taxonomies.
 */
function career_register_taxonomy(): void {
	// 1. Category / Department (`career_category`)
	if ( ! taxonomy_exists( 'career_category' ) ) {
		$cat_labels = array(
			'name'          => _x( 'Categories (แผนก/หมวดหมู่)', 'Taxonomy General Name', 'career-manager' ),
			'singular_name' => _x( 'Category', 'Taxonomy Singular Name', 'career-manager' ),
			'menu_name'     => career_text( 'หมวดหมู่ / แผนก (Category)', 'Category' ),
			'all_items'     => career_text( 'หมวดหมู่ทั้งหมด', 'All Categories' ),
			'add_new_item'  => career_text( 'เพิ่มหมวดหมู่ใหม่', 'Add New Category' ),
			'edit_item'     => career_text( 'แก้ไขหมวดหมู่', 'Edit Category' ),
		);

		register_taxonomy(
			'career_category',
			array( 'career' ),
			array(
				'labels'            => $cat_labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'career-category',
					'with_front' => false,
				),
			)
		);
	} else {
		register_taxonomy_for_object_type( 'career_category', 'career' );
	}

	// 2. Location (`career_location`)
	if ( ! taxonomy_exists( 'career_location' ) ) {
		$loc_labels = array(
			'name'          => _x( 'Locations (สถานที่ปฏิบัติงาน)', 'Taxonomy General Name', 'career-manager' ),
			'singular_name' => _x( 'Location', 'Taxonomy Singular Name', 'career-manager' ),
			'menu_name'     => career_text( 'สถานที่ (Location)', 'Location' ),
			'all_items'     => career_text( 'สถานที่ทั้งหมด', 'All Locations' ),
			'add_new_item'  => career_text( 'เพิ่มสถานที่ใหม่', 'Add New Location' ),
			'edit_item'     => career_text( 'แก้ไขสถานที่', 'Edit Location' ),
		);

		register_taxonomy(
			'career_location',
			array( 'career' ),
			array(
				'labels'            => $loc_labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'career-location',
					'with_front' => false,
				),
			)
		);
	} else {
		register_taxonomy_for_object_type( 'career_location', 'career' );
	}

	// 3. Employment Type (`career_type`)
	if ( ! taxonomy_exists( 'career_type' ) ) {
		$type_labels = array(
			'name'          => _x( 'Employment Types (ประเภทการจ้าง)', 'Taxonomy General Name', 'career-manager' ),
			'singular_name' => _x( 'Employment Type', 'Taxonomy Singular Name', 'career-manager' ),
			'menu_name'     => career_text( 'รูปแบบงาน (Employment Type)', 'Employment Type' ),
			'all_items'     => career_text( 'รูปแบบงานทั้งหมด', 'All Employment Types' ),
			'add_new_item'  => career_text( 'เพิ่มรูปแบบงานใหม่', 'Add New Employment Type' ),
			'edit_item'     => career_text( 'แก้ไขรูปแบบงาน', 'Edit Employment Type' ),
		);

		register_taxonomy(
			'career_type',
			array( 'career' ),
			array(
				'labels'            => $type_labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'career-type',
					'with_front' => false,
				),
			)
		);
	} else {
		register_taxonomy_for_object_type( 'career_type', 'career' );
	}
}
add_action( 'init', 'career_register_taxonomy', 5 );

/**
 * Seed default Career Categories, Locations, and Employment Types on activation.
 */
function career_seed_default_terms(): void {
	career_register_taxonomy();

	$default_categories = array(
		'cost-dept'    => 'Cost Dept',
		'project-dept' => 'Project Dept',
	);

	foreach ( $default_categories as $slug => $name ) {
		if ( ! term_exists( $slug, 'career_category' ) && ! term_exists( $name, 'career_category' ) ) {
			wp_insert_term( $name, 'career_category', array( 'slug' => $slug ) );
		}
	}

	$default_locations = array(
		'bangkok'  => 'Bangkok',
		'chonburi' => 'Chonburi',
		'rayong'   => 'Rayong',
	);

	foreach ( $default_locations as $slug => $name ) {
		if ( ! term_exists( $slug, 'career_location' ) && ! term_exists( $name, 'career_location' ) ) {
			wp_insert_term( $name, 'career_location', array( 'slug' => $slug ) );
		}
	}

	$default_types = array(
		'contract'   => 'Contract',
		'full-time'  => 'Full-time',
		'internship' => 'Internship',
	);

	foreach ( $default_types as $slug => $name ) {
		if ( ! term_exists( $slug, 'career_type' ) && ! term_exists( $name, 'career_type' ) ) {
			wp_insert_term( $name, 'career_type', array( 'slug' => $slug ) );
		}
	}
}

/**
 * Handle frontend sorting for Career Archive and Career Taxonomy pages.
 *
 * @param \WP_Query $query Main WP_Query instance.
 */
function career_archive_sorting( \WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( is_post_type_archive( 'career' ) || is_tax( array( 'career_category', 'career_location', 'career_type' ) ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
				'ID'         => 'DESC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'career_archive_sorting' );

/**
 * Render "+ สร้างข้อมูลตัวอย่าง" button on Career Admin List Table.
 *
 * @param string $which Table navigation location ('top' or 'bottom').
 */
function career_render_dummy_button( string $which = 'top' ): void {
	if ( 'top' !== $which || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $typenow;
	if ( 'career' !== $typenow ) {
		return;
	}

	$url = wp_nonce_url(
		add_query_arg(
			array(
				'post_type' => 'career',
				'action'    => 'career_generate_dummy',
			),
			admin_url( 'edit.php' )
		),
		'career_generate_dummy_nonce'
	);
	?>
	<div id="career-dummy-action-wrap" class="alignleft actions">
		<a id="career-dummy-btn" href="<?php echo esc_url( $url ); ?>" class="button button-primary" style="background: #800020; border-color: #5c0017; text-shadow: none;">
			<?php echo esc_html( career_text( '+ สร้างข้อมูลตัวอย่าง (ตามต้นแบบ)', '+ Generate Sample Jobs' ) ); ?>
		</a>
	</div>
	<?php
}
add_action( 'manage_posts_extra_tablenav', 'career_render_dummy_button' );

/**
 * Handle Dummy Career Data Generation in Admin (matching the reference design).
 */
function career_handle_dummy_generation(): void {
	if ( ! is_admin() || ! isset( $_GET['action'] ) || 'career_generate_dummy' !== $_GET['action'] ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'คุณไม่มีสิทธิ์ในการดำเนินการนี้', 'career-manager' ) );
	}

	check_admin_referer( 'career_generate_dummy_nonce' );

	career_seed_default_terms();

	$sample_jobs = array(
		array(
			'title'      => 'Secretary',
			'cat'        => 'Project Dept',
			'loc'        => 'Bangkok',
			'type'       => 'Full-time',
			'urgent'     => '1',
			'app_closed' => '1',
			'loc_label'  => 'Not specified',
		),
		array(
			'title'      => 'Supervisor (Structure/ Architect / Interior/ Mechanical/ Electrical Works)',
			'cat'        => 'Project Dept',
			'loc'        => 'Chonburi',
			'type'       => 'Full-time',
			'urgent'     => '1',
			'app_closed' => '0',
			'loc_label'  => 'Not specified',
		),
		array(
			'title'      => 'Cost Officer/ Engineer M&E, Civil Cost (Quantity Surveyor/ QS)',
			'cat'        => 'Cost Dept',
			'loc'        => 'Bangkok',
			'type'       => 'Full-time',
			'urgent'     => '1',
			'app_closed' => '0',
			'loc_label'  => 'Not specified',
		),
		array(
			'title'      => 'Architect',
			'cat'        => 'Project Dept',
			'loc'        => 'Rayong',
			'type'       => 'Contract',
			'urgent'     => '1',
			'app_closed' => '0',
			'loc_label'  => 'Not specified',
		),
		array(
			'title'      => 'Engineer (Civil / Electrical / Mechanical)',
			'cat'        => 'Project Dept',
			'loc'        => 'Bangkok',
			'type'       => 'Full-time',
			'urgent'     => '1',
			'app_closed' => '0',
			'loc_label'  => 'Not specified',
		),
	);

	$default_content = '<p><strong><u>Job Description :</u></strong></p>'
		. '<ul>'
		. '<li>Manage and coordinate general administrative activities and daily operations in the project site office.</li>'
		. '<li>Prepare meeting rooms and ensure that all necessary facilities and documents are available before meetings.</li>'
		. '<li>Prepare and distribute Minutes of Meetings (MOM) to all related parties in a timely manner.</li>'
		. '<li>Register, track, and control all incoming and outgoing project documents.</li>'
		. '<li>Prepare, type, format, and distribute various project-related documents using the company\'s standard templates and procedures.</li>'
		. '<li>Maintain an organized document filing system to ensure that project documents are properly stored, updated, and easily accessible.</li>'
		. '<li>Provide general administrative and document control support to the project team as required.</li>'
		. '</ul>'
		. '<p><strong><u>Qualifications :</u></strong></p>'
		. '<ul>'
		. '<li>Thai national, female, aged 23 years or above.</li>'
		. '<li>Diploma, Higher Vocational Certificate, or Bachelor\'s degree in Business Administration or a related field.</li>'
		. '<li>At least 1 year of working experience in document control, administrative support, or a related role, preferably in construction projects.</li>'
		. '<li>Good organizational skills with strong attention to detail.</li>'
		. '<li>Good communication and coordination skills.</li>'
		. '<li>Proficient in Microsoft Office, particularly Word, Excel, and PowerPoint.</li>'
		. '<li>Willing and able to work in Bangkok and upcountry project locations.</li>'
		. '</ul>';

	foreach ( $sample_jobs as $job ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => $job['title'],
				'post_status'  => 'publish',
				'post_type'    => 'career',
				'post_content' => $default_content,
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( (int) $post_id, 'post_views_count', wp_rand( 120, 650 ) );
			update_post_meta( (int) $post_id, '_career_is_urgent', $job['urgent'] );
			update_post_meta( (int) $post_id, '_career_application_closed', $job['app_closed'] );
			update_post_meta( (int) $post_id, '_career_location_custom', $job['loc_label'] );

			wp_set_object_terms( (int) $post_id, $job['cat'], 'career_category' );
			wp_set_object_terms( (int) $post_id, $job['loc'], 'career_location' );
			wp_set_object_terms( (int) $post_id, $job['type'], 'career_type' );
		}
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=career&dummy_created=1' ) );
	exit;
}
add_action( 'admin_init', 'career_handle_dummy_generation' );
