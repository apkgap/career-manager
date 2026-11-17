<?php
/**
 * Elementor Widget: Career Archive Tabs (ร่วมงานกับเรา).
 *
 * Strict 3-file Elementor Widget (`.php`, `.css`, `.js`) with inline card rendering
 * and dual-rendering synchronization (`render()` + `content_template()`).
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class CAREER_Widget_Archive_Tabs
 */
class CAREER_Widget_Archive_Tabs extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'career-archive-tabs';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Career Job Board (ร่วมงานกับเรา)', 'career-manager' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-post-list';
	}

	/**
	 * Widget categories.
	 *
	 * @return array<int, string>
	 */
	public function get_categories(): array {
		return array( 'career-widgets', 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array<int, string>
	 */
	public function get_keywords(): array {
		return array( 'career', 'job', 'vacancy', 'filter', 'ร่วมงานกับเรา', 'สมัครงาน' );
	}

	/**
	 * Style dependencies.
	 *
	 * @return array<int, string>
	 */
	public function get_style_depends(): array {
		return array( 'career-manager-frontend', 'career-manager-widget-tabs' );
	}

	/**
	 * Script dependencies.
	 *
	 * @return array<int, string>
	 */
	public function get_script_depends(): array {
		return array( 'career-manager-frontend', 'career-manager-widget-tabs' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls(): void {
		// CONTENT SECTION: TITLE
		$this->start_controls_section(
			'section_title',
			array(
				'label' => esc_html__( 'Title & Header', 'career-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Widget Title (Optional)', 'career-manager' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_responsive_control(
			'title_align',
			array(
				'label'     => esc_html__( 'Alignment', 'career-manager' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => 'Left',
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => 'Center',
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => 'Right',
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .career-tabs__title' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// CONTENT SECTION: QUERY
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Query Settings', 'career-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => esc_html__( 'Posts Per Page', 'career-manager' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'career-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => esc_html__( 'Date', 'career-manager' ),
					'title'      => esc_html__( 'Title', 'career-manager' ),
					'rand'       => esc_html__( 'Random', 'career-manager' ),
					'menu_order' => esc_html__( 'Menu Order', 'career-manager' ),
					'ID'         => esc_html__( 'Post ID', 'career-manager' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'career-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'ASC'  => esc_html__( 'ASC', 'career-manager' ),
					'DESC' => esc_html__( 'DESC', 'career-manager' ),
				),
			)
		);

		$this->end_controls_section();

		// CONTENT SECTION: FOOTER
		$this->start_controls_section(
			'section_footer',
			array(
				'label' => esc_html__( 'Footer Button', 'career-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'view_all_text',
			array(
				'label'   => esc_html__( 'Button Text', 'career-manager' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => career_text( 'ดูทั้งหมด', 'View All' ),
			)
		);

		$this->add_control(
			'view_all_link',
			array(
				'label' => esc_html__( 'Button Link', 'career-manager' ),
				'type'  => \Elementor\Controls_Manager::URL,
			)
		);

		$this->end_controls_section();

		// STYLE SECTION: ACCENT & BADGES
		$this->start_controls_section(
			'section_style_tabs',
			array(
				'label' => esc_html__( 'Brand & Badge Color', 'career-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'tab_active_color',
			array(
				'label'     => esc_html__( 'Primary Badge Color', 'career-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#800020',
				'selectors' => array(
					'{{WRAPPER}} .career-board' => '--career-primary: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// STYLE SECTION: CARDS
		$this->start_controls_section(
			'section_style_cards',
			array(
				'label' => esc_html__( 'Card Styling', 'career-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'career-manager' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .career-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'career-manager' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .career-card' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .career-card',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_title_typography',
				'label'    => esc_html__( 'Title Typography', 'career-manager' ),
				'selector' => '{{WRAPPER}} .career-card__title',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render inline card markup for a career post (No external card.php).
	 *
	 * @param int $post_id Career Post ID.
	 * @return string
	 */
	protected function render_card( int $post_id ): string {
		return career_render_card_html( $post_id );
	}

	/**
	 * Frontend PHP render.
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();

		$allowed_orderby = array( 'date', 'title', 'menu_order', 'rand', 'ID' );
		$orderby         = in_array( (string) ( $settings['orderby'] ?? 'date' ), $allowed_orderby, true ) ? (string) $settings['orderby'] : 'date';
		$order           = in_array( strtoupper( (string) ( $settings['order'] ?? 'DESC' ) ), array( 'ASC', 'DESC' ), true ) ? strtoupper( (string) $settings['order'] ) : 'DESC';
		$posts_per_page  = max( 1, min( 100, (int) ( $settings['posts_per_page'] ?? 10 ) ) );

		echo career_render_tabs_component(
			array(
				'title'            => (string) ( $settings['title'] ?? '' ),
				'posts_per_page'   => $posts_per_page,
				'orderby'          => $orderby,
				'order'            => $order,
				'view_all_text'    => (string) ( $settings['view_all_text'] ?? career_text( 'ดูทั้งหมด', 'View All' ) ),
				'view_all_url'     => (string) ( $settings['view_all_link']['url'] ?? '' ),
				'tab_active_color' => (string) ( $settings['tab_active_color'] ?? '#800020' ),
				'instance_id'      => $this->get_id(),
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Elementor Editor Live Preview Template (`content_template()`).
	 * Mirrors the exact DOM hierarchy, `id` before `class`, and BEM selectors of `render()`.
	 */
	protected function content_template(): void {
		?>
		<#
		var widgetId = view.getID();
		var activeColor = settings.tab_active_color || '#800020';
		var samplePosts = [
			{ id: 101, title: 'Secretary', cat: 'Project Dept', type: 'Full-time', urgent: true, closed: true, loc: 'Not specified', date: '25/03/2026' },
			{ id: 102, title: 'Supervisor (Structure/ Architect / Interior/ Mechanical/ Electrical Works)', cat: 'Project Dept', type: 'Full-time', urgent: true, closed: false, loc: 'Not specified', date: '25/03/2026' },
			{ id: 103, title: 'Cost Officer/ Engineer M&E, Civil Cost (Quantity Surveyor/ QS)', cat: 'Cost Dept', type: 'Full-time', urgent: true, closed: false, loc: 'Not specified', date: '25/03/2026' },
			{ id: 104, title: 'Architect', cat: 'Project Dept', type: 'Contract', urgent: true, closed: false, loc: 'Not specified', date: '25/03/2026' }
		];
		#>
		<div id="career-tabs-{{ widgetId }}" class="career-board career-tabs career-tabs-instance" style="--career-primary: {{ activeColor }};">
			<# if ( settings.title ) { #>
				<h2 id="career-tabs-title-{{ widgetId }}" class="career-tabs__title">{{ settings.title }}</h2>
			<# } #>

			<div id="career-board-layout-{{ widgetId }}" class="career-board__layout">
				<div id="career-filter-backdrop-{{ widgetId }}" class="career-filter-backdrop js-career-filter-backdrop js-career-close-filter-modal"></div>

				<aside id="career-filter-panel-{{ widgetId }}" class="career-filter-panel js-career-filter-panel">
					<div id="career-filter-mobile-head-{{ widgetId }}" class="career-filter-panel__mobile-header">
						<div id="career-filter-mobile-title-wrap-{{ widgetId }}" class="career-filter-panel__mobile-title-wrap">
							<h3 id="career-filter-mobile-title-{{ widgetId }}" class="career-filter-panel__mobile-title">Search & Filter Jobs</h3>
						</div>
						<button id="career-filter-mobile-close-{{ widgetId }}" type="button" class="career-filter-panel__mobile-close js-career-close-filter-modal" aria-label="Close Filter">✕</button>
					</div>

					<div id="career-filter-body-{{ widgetId }}" class="career-filter-panel__body">
						<div id="career-filter-search-sec-{{ widgetId }}" class="career-filter-panel__section">
							<h3 id="career-filter-search-heading-{{ widgetId }}" class="career-filter-panel__heading">Search</h3>
							<div id="career-search-box-{{ widgetId }}" class="career-filter-panel__search-box">
								<input id="career-search-input-{{ widgetId }}" type="text" class="career-filter-panel__search-input" placeholder="Search jobs or keywords" />
							</div>
						</div>

						<div id="career-filter-header-{{ widgetId }}" class="career-filter-panel__header">
							<h3 id="career-filter-by-heading-{{ widgetId }}" class="career-filter-panel__heading">Filter By</h3>
							<button id="career-clear-filter-btn-{{ widgetId }}" type="button" class="career-filter-panel__clear-btn">Clear Filter</button>
						</div>

						<div id="career-filter-group-cat-{{ widgetId }}" class="career-filter-panel__group">
							<h4 id="career-filter-title-cat-{{ widgetId }}" class="career-filter-panel__group-title">Category</h4>
							<div id="career-filter-list-cat-{{ widgetId }}" class="career-filter-panel__checklist">
								<label id="career-check-cat-1-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Cost Dept</span></label>
								<label id="career-check-cat-2-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Project Dept</span></label>
							</div>
						</div>

						<div id="career-filter-group-loc-{{ widgetId }}" class="career-filter-panel__group">
							<h4 id="career-filter-title-loc-{{ widgetId }}" class="career-filter-panel__group-title">Location</h4>
							<div id="career-filter-list-loc-{{ widgetId }}" class="career-filter-panel__checklist">
								<label id="career-check-loc-1-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Bangkok</span></label>
								<label id="career-check-loc-2-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Chonburi</span></label>
								<label id="career-check-loc-3-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Rayong</span></label>
							</div>
						</div>

						<div id="career-filter-group-type-{{ widgetId }}" class="career-filter-panel__group">
							<h4 id="career-filter-title-type-{{ widgetId }}" class="career-filter-panel__group-title">Employment Type</h4>
							<div id="career-filter-list-type-{{ widgetId }}" class="career-filter-panel__checklist">
								<label id="career-check-type-1-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Contract</span></label>
								<label id="career-check-type-2-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Full-time</span></label>
								<label id="career-check-type-3-{{ widgetId }}" class="career-filter-panel__checkbox-label"><input type="checkbox" class="career-filter-panel__checkbox" /> <span>Internship</span></label>
							</div>
						</div>
					</div>

					<div id="career-filter-mobile-foot-{{ widgetId }}" class="career-filter-panel__mobile-footer">
						<button id="career-filter-mobile-apply-{{ widgetId }}" type="button" class="career-filter-panel__mobile-apply js-career-close-filter-modal">
							<span>Show Results ({{ samplePosts.length }})</span>
						</button>
					</div>
				</aside>

				<div id="career-content-col-{{ widgetId }}" class="career-board__results">
					<div id="career-results-toolbar-{{ widgetId }}" class="career-board__toolbar">
						<div id="career-results-summary-{{ widgetId }}" class="career-board__summary">
							<span id="career-results-badge-{{ widgetId }}" class="career-board__count-badge">{{ samplePosts.length }}</span>
							<span id="career-results-label-{{ widgetId }}" class="career-board__summary-text">Open Positions Available</span>
						</div>

						<button id="career-mobile-filter-btn-{{ widgetId }}" type="button" class="career-board__filter-trigger js-career-open-filter-modal" aria-controls="career-filter-panel-{{ widgetId }}" aria-expanded="false">
							<span>Filter</span>
						</button>
					</div>

					<div id="career-card-list-{{ widgetId }}" class="career-tabs__card-list">
						<# _.each( samplePosts, function( post ) {
							var cardCls = 'career-card';
							if ( post.urgent ) { cardCls += ' is-urgent'; }
							if ( post.closed ) { cardCls += ' is-closed'; }
						#>
							<article id="career-card-{{ post.id }}" class="{{ cardCls }}">
								<# if ( post.urgent ) { #>
									<div id="career-card-ribbon-{{ post.id }}" class="career-card__urgent-ribbon">
										<span id="career-card-badge-urgent-{{ post.id }}" class="career-card__urgent-ribbon-text">ด่วน</span>
									</div>
								<# } #>

								<div id="career-card-body-{{ post.id }}" class="career-card__body">
									<div id="career-card-badges-{{ post.id }}" class="career-card__badges">
										<span id="career-card-badge-cat-{{ post.id }}" class="career-card__badge career-card__badge--category">{{ post.cat }}</span>
										<# if ( post.type ) { #>
											<span id="career-card-badge-type-{{ post.id }}" class="career-card__badge career-card__badge--type">{{ post.type }}</span>
										<# } #>
									</div>

									<div id="career-card-top-{{ post.id }}" class="career-card__top">
										<h3 id="career-card-title-heading-{{ post.id }}" class="career-card__title">
											<a id="career-card-link-{{ post.id }}" href="#" class="career-card__link">{{ post.title }}</a>
										</h3>
									</div>

									<# if ( post.closed ) { #>
										<div id="career-card-closed-bar-{{ post.id }}" class="career-card__closed-banner">Applications closed</div>
									<# } #>

									<div id="career-card-meta-{{ post.id }}" class="career-card__meta">
										<span id="career-card-meta-loc-{{ post.id }}" class="career-card__meta-item">{{ post.loc }}</span>
										<span id="career-card-meta-sep-{{ post.id }}" class="career-card__meta-sep">•</span>
										<span id="career-card-meta-date-{{ post.id }}" class="career-card__meta-item">{{ post.date }}</span>
										<span id="career-card-meta-cta-{{ post.id }}" class="career-card__meta-more"><span>View Details</span> →</span>
									</div>
								</div>
							</article>
						<# } ); #>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
