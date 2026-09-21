<?php
/**
 * Named Elementor widgets (NextGen Tutors category).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra content controls mixin via overrides.
 */
class BI_EL_Widget_Hero extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'hero';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'heading_tag', [
			'label'   => __( 'Heading tag', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3' ],
			'default' => 'h1',
		] );
		$this->add_control( 'page_key', [
			'label'   => __( 'CMS page key', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'home',
		] );
		$this->add_control( 'cta_text', [
			'label'   => __( 'CTA label', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$this->add_control( 'cta_url', [
			'label' => __( 'CTA link', 'beyondinfinity' ),
			'type'  => \Elementor\Controls_Manager::URL,
		] );
		$this->add_control( 'show_search', [
			'label'        => __( 'Show search form', 'beyondinfinity' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
	}
}

class BI_EL_Widget_Find_Tutor extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'find-tutor';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'find_mode', [
			'label'   => __( 'Mode', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'form',
			'options' => [
				'form'         => __( 'Find tutor form', 'beyondinfinity' ),
				'marketplace'  => __( 'Marketplace', 'beyondinfinity' ),
				'match'        => __( 'Smart match', 'beyondinfinity' ),
			],
		] );
	}
}

class BI_EL_Widget_Tutor_Grid extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'tutor-grid';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'limit', [
			'label'   => __( 'Limit', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 24,
			'default' => 6,
		] );
		$this->add_control( 'subject', [
			'label'       => __( 'Subject slug', 'beyondinfinity' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => 'mathematics',
		] );
	}
}

class BI_EL_Widget_Tutor_Card extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'tutor-card';
	}
	protected function register_widget_content_controls() {
		$this->add_control( 'subject', [
			'label' => __( 'Subject slug', 'beyondinfinity' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );
	}
}

class BI_EL_Widget_Subject_Grid extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'subject-grid';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'limit', [
			'label'   => __( 'Limit', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 8,
			'min'     => 1,
			'max'     => 24,
		] );
	}
}

class BI_EL_Widget_Subject_Card extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'subject-card';
	}
}

class BI_EL_Widget_Tutor_Filmstrip extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'tutor-filmstrip';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'film_source', [
			'label'   => __( 'Source', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'tutors',
			'options' => [
				'tutors'   => __( 'Tutors', 'beyondinfinity' ),
				'subjects' => __( 'Subjects', 'beyondinfinity' ),
			],
		] );
		$this->add_control( 'limit', [
			'label'   => __( 'Limit', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 8,
			'min'     => 1,
			'max'     => 16,
		] );
	}
}

class BI_EL_Widget_Testimonials extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'testimonials';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'limit', [
			'label'   => __( 'Limit', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 4,
			'min'     => 1,
			'max'     => 12,
		] );
	}
}

class BI_EL_Widget_Pricing extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'pricing';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'limit', [
			'label'   => __( 'Tiers', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 3,
			'min'     => 1,
			'max'     => 6,
		] );
	}
}

class BI_EL_Widget_Stats extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'stats';
	}
}

class BI_EL_Widget_Faq extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'faq';
	}
}

class BI_EL_Widget_Booking_Cta extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'booking-cta';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'cta_text', [
			'label'   => __( 'Button label', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$this->add_control( 'cta_url', [
			'label' => __( 'Link', 'beyondinfinity' ),
			'type'  => \Elementor\Controls_Manager::URL,
		] );
	}
}

class BI_EL_Widget_Animated_Heading extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'animated-heading';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'heading_tag', [
			'label'   => __( 'Tag', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4' ],
			'default' => 'h2',
		] );
	}
}

class BI_EL_Widget_Gsap_Text_Reveal extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'gsap-text-reveal';
	}
}

class BI_EL_Widget_Tilt_Card extends BI_EL_Widget_Base {
	protected function definition_id() {
		return '3d-tilt-card';
	}
}

class BI_EL_Widget_Scene extends BI_EL_Widget_Base {
	protected function definition_id() {
		return '3d-scene';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_webgl_controls();
	}
	protected function add_webgl_controls() {
		$this->add_control( 'webgl_quality', [
			'label'   => __( 'Quality', 'beyondinfinity' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'medium',
			'options' => [
				'low'    => __( 'Low', 'beyondinfinity' ),
				'medium' => __( 'Medium', 'beyondinfinity' ),
				'high'   => __( 'High', 'beyondinfinity' ),
			],
		] );
		$this->add_control( 'fallback_image', [
			'label' => __( 'CSS fallback image', 'beyondinfinity' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		] );
	}
}

class BI_EL_Widget_Scroll_Mask extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'scroll-mask';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'image', [
			'label' => __( 'Image', 'beyondinfinity' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		] );
	}
}

class BI_EL_Widget_Zoom_Reveal extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'zoom-reveal';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'image', [
			'label' => __( 'Image', 'beyondinfinity' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		] );
	}
}

class BI_EL_Widget_Horizontal_Scroll extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'horizontal-scroll';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'track_items', [
			'label'  => __( 'Panels', 'beyondinfinity' ),
			'type'   => \Elementor\Controls_Manager::REPEATER,
			'fields' => [
				[
					'name'    => 'title',
					'label'   => __( 'Title', 'beyondinfinity' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => '',
				],
				[
					'name'  => 'text',
					'label' => __( 'Text', 'beyondinfinity' ),
					'type'  => \Elementor\Controls_Manager::TEXTAREA,
				],
			],
			'title_field' => '{{{ title }}}',
		] );
	}
}

class BI_EL_Widget_Sticky_Story extends BI_EL_Widget_Base {
	protected function definition_id() {
		return 'sticky-story';
	}
	protected function register_widget_content_controls() {
		parent::register_widget_content_controls();
		$this->add_control( 'story_panels', [
			'label'  => __( 'Story panels', 'beyondinfinity' ),
			'type'   => \Elementor\Controls_Manager::REPEATER,
			'fields' => [
				[
					'name'  => 'title',
					'label' => __( 'Title', 'beyondinfinity' ),
					'type'  => \Elementor\Controls_Manager::TEXT,
				],
				[
					'name'  => 'text',
					'label' => __( 'Text', 'beyondinfinity' ),
					'type'  => \Elementor\Controls_Manager::TEXTAREA,
				],
			],
			'title_field' => '{{{ title }}}',
		] );
	}
}

class BI_EL_Widget_Particle_Background extends BI_EL_Widget_Scene {
	protected function definition_id() {
		return 'particle-background';
	}
}

class BI_EL_Widget_Orb_Background extends BI_EL_Widget_Scene {
	protected function definition_id() {
		return 'orb-background';
	}
}

class BI_EL_Widget_Webgl_Background extends BI_EL_Widget_Scene {
	protected function definition_id() {
		return 'webgl-background';
	}
}

/**
 * Class map for registration.
 *
 * @return class-string[]
 */
function bi_el_ds_widget_classes() {
	return [
		'BI_EL_Widget_Hero',
		'BI_EL_Widget_Find_Tutor',
		'BI_EL_Widget_Tutor_Grid',
		'BI_EL_Widget_Tutor_Card',
		'BI_EL_Widget_Subject_Grid',
		'BI_EL_Widget_Subject_Card',
		'BI_EL_Widget_Tutor_Filmstrip',
		'BI_EL_Widget_Testimonials',
		'BI_EL_Widget_Pricing',
		'BI_EL_Widget_Stats',
		'BI_EL_Widget_Faq',
		'BI_EL_Widget_Booking_Cta',
		'BI_EL_Widget_Animated_Heading',
		'BI_EL_Widget_Gsap_Text_Reveal',
		'BI_EL_Widget_Tilt_Card',
		'BI_EL_Widget_Scene',
		'BI_EL_Widget_Scroll_Mask',
		'BI_EL_Widget_Zoom_Reveal',
		'BI_EL_Widget_Horizontal_Scroll',
		'BI_EL_Widget_Sticky_Story',
		'BI_EL_Widget_Particle_Background',
		'BI_EL_Widget_Orb_Background',
		'BI_EL_Widget_Webgl_Background',
	];
}
