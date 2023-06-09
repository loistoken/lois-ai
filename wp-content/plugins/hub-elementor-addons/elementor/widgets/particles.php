<?php
namespace LiquidElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Schemes\Color;
use Elementor\Schemes\Typography;
use Elementor\Utils;
use Elementor\Control_Media;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor heading widget.
 *
 * Elementor widget that displays an eye-catching headlines.
 *
 * @since 1.0.0
 */
class LD_Particles extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve heading widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ld_particles';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve heading widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Liquid Particles', 'hub-elementor-addons' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve heading widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-star lqd-element';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the heading widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'hub-core' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'particles', 'animation' ];
	}

	/**
	 * Retrieve the list of scripts the counter widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {

		if ( liquid_helper()->liquid_elementor_script_depends() ){
			return [ 'jquery-particles' ];
		} else {
			return [''];
		}
		
	}

	/**
	 * Register heading widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'general_section',
			array(
				'label' => __( 'Particles', 'hub-elementor-addons' ),
			)
		);

		$this->add_control(
			'particle_id',
			[
				'label' => __( 'Particle ID', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => uniqid('lqd-particle-'),
				'description' => wp_kses_post( __( 'Enter particle ID (Note: make sure it is unique and valid according to <a href="%s" target="_blank">w3c specification</a>).', 'hub-elementor-addons' ) ),
			]
		);

		$this->add_control(
			'as_bg',
			[
				'label' => __( 'Use as background?', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'visible_on_hover',
			[
				'label' => __( 'Visible when hovering parent column?', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_block' => true,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'height',
			[
				'label' => __( 'Height', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'px, vh, % etc.', 'hub-elementor-addons' ),
				'default' => '450px',
				'separator' => 'before',
				'render_type' => 'template',
				'selectors' => [
					'{{WRAPPER}} .ld-particles-container' => 'height: {{VALUE}}'
				]
			]
		);

		$this->end_controls_section();

		// Particles Section
		$this->start_controls_section(
			'Particles_section',
			array(
				'label' => __( 'Particles Options', 'hub-elementor-addons' ),
			)
		);

		$this->add_control(
			'number',
			[
				'label' => __( 'Number', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'Number of the particles', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'enable_density',
			[
				'label' => __( 'Enable Density?', 'hub-elementor-addons' ),
				'description' => __( 'Will enable density factor', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'density',
			[
				'label' => __( 'Density', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'Density of the particles', 'hub-elementor-addons' ),
				'condition' => array(
					'enable_density' => 'yes'
				)
			]
		);

		$this->add_control(
			'color_type',
			[
				'label' => __( 'Color Variations', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'single_color',
				'options' => [
					'single_color' => __( 'Single Color', 'hub-elementor-addons' ),
					'multi_color' => __( 'Multi Color', 'hub-elementor-addons' ),
					'random_color' => __( 'Random Color', 'hub-elementor-addons' ),
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'scolor',
			[
				'label' => __( 'Color', 'hub-elementor-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'color: {{VALUE}}'
				],
			]
		);

		$this->add_control(
			'multi_color_values',
			[
				'label' => __( 'Multi Colors', 'hub-elementor-addons' ),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ scolor }}}',
				'condition' => array(
					'color_type' => 'multi_color'
				)
			]
		);

		$this->add_control(
			'color',
			[
				'label' => __( 'Color', 'hub-elementor-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					//'{{WRAPPER}} {{CURRENT_ITEM}}' => 'color: {{VALUE}}'
				],
				'condition' => array(
					'color_type' => 'single_color',
				)
			]
		);

		$this->add_control(
			'shape_type',
			[
				'label' => __( 'Shape type', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple' => true,
				'options' => [
					'circle'  => __( 'Circle', 'hub-elementor-addons' ),
					'edge'  => __( 'Edge', 'hub-elementor-addons' ),
					'triangle'  => __( 'Triangle', 'hub-elementor-addons' ),
					'polygon'  => __( 'Polygon', 'hub-elementor-addons' ),
					'star'  => __( 'Star', 'hub-elementor-addons' ),
					'image'  => __( 'Image', 'hub-elementor-addons' ),
				],
				'default' => [ 'circle' ],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'stroke_width',
			[
				'label' => __( 'Stroke Width', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 2', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'stroke_color',
			[
				'label' => __( 'Stroke color', 'hub-elementor-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					//'{{WRAPPER}} {{CURRENT_ITEM}}' => 'color: {{VALUE}}'
				],
			]
		);

		$this->add_control(
			'nb_sides',
			[
				'label' => __( 'Polygon Number Sides', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'Add polygons number sides', 'hub-elementor-addons' ),
				'condition' => [
					'shape_type' => 'shape_type',
				]
			]
		);

		$this->add_control(
			'image',
			[
				'label' => __( 'Image', 'hub-elementor-addons' ),
				'type' => Controls_Manager::MEDIA,
				'condition' => [
					'shape_type' => 'image'
				],
			]
		);

		$this->add_control(
			'image_width',
			[
				'label' => __( 'Image Width', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'Add Image Width', 'hub-elementor-addons' ),
				'condition' => [
					'shape_type' => 'image',
				]
			]
		);
	
		$this->add_control(
			'image_height',
			[
				'label' => __( 'Image Height', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'Add Image Height', 'hub-elementor-addons' ),
				'condition' => [
					'shape_type' => 'image',
				]
			]
		);

		$this->add_control(
			'opacity',
			[
				'label' => __( 'Opacity', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1,
						'step' => 0.05,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
			]
		);

		$this->add_control(
			'enable_random_opacity',
			[
				'label' => __( 'Enable Random Opacity', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		
		$this->add_control(
			'enable_anim_opacity',
			[
				'label' => __( 'Enable Animation Opacity', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'anim_opacity_speed',
			[
				'label' => __( 'Animation Speed', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 3', 'hub-elementor-addons' ),
				'condition' => [
					'enable_anim_opacity' => 'yes',
				]
			]
		);

		$this->add_control(
			'anim_opacity_min',
			[
				'label' => __( 'Min opacity', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1,
						'step' => 0.05,
					],
				],
				'default' => [
					'unit' => 'px',
				],
				'condition' => [
					'enable_anim_opacity' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_anim_sync',
			[
				'label' => __( 'Enable Animation Sync', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'enable_anim_opacity' => 'yes'
				]
			]
		);

		$this->add_control(
			'size',
			[
				'label' => __( 'Size', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 20', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'enable_random_size',
			[
				'label' => __( 'Enable Random size', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'enable_anim_size',
			[
				'label' => __( 'Enable Animation size', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'anim_size_speed',
			[
				'label' => __( 'Speed', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 80', 'hub-elementor-addons' ),
				'condition' => [
					'enable_anim_size' => 'yes'
				]
			]
		);

		$this->add_control(
			'anim_size_min',
			[
				'label' => __( 'Min size', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
				],
				'condition' => [
					'enable_anim_size' => 'yes'
				]
			]
		);
		
		$this->add_control(
			'enable_anim_size_sync',
			[
				'label' => __( 'Enable Animation Sync', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'enable_anim_size' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_line_linked',
			[
				'label' => __( 'Enable Linked Line', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'line_distance',
			[
				'label' => __( 'Distance', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 300', 'hub-elementor-addons' ),
				'condition' => [
					'enable_line_linked' => 'yes'
				]
			]
		);

		$this->add_control(
			'line_color',
			[
				'label' => __( 'Line color', 'hub-elementor-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					//'{{WRAPPER}} {{CURRENT_ITEM}}' => 'color: {{VALUE}}'
				],
				'condition' => [
					'enable_line_linked' => 'yes'
				]
			]
		);

		$this->add_control(
			'line_opacity',
			[
				'label' => __( 'Min opacity', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1,
						'step' => 0.05,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
				'condition' => [
					'enable_line_linked' => 'yes'
				]
			]
		);

		$this->add_control(
			'line_width',
			[
				'label' => __( 'Line Width', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 2', 'hub-elementor-addons' ),
				'condition' => [
					'enable_line_linked' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_move',
			[
				'label' => __( 'Enable Move', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'move_speed',
			[
				'label' => __( 'Move Speed', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 12', 'hub-elementor-addons' ),
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'move_direction',
			[
				'label' => __( 'Move Direction', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none' => __( 'None', 'hub-elementor-addons' ),
					'top' => __( 'Top', 'hub-elementor-addons' ),
					'top-right' => __( 'Top Right', 'hub-elementor-addons' ),
					'right' => __( 'Right', 'hub-elementor-addons' ),
					'bottom-right' => __( 'Bottom Right', 'hub-elementor-addons' ),
					'bottom' => __( 'Bottom', 'hub-elementor-addons' ),
					'bottom-left' => __( 'Bottom Left', 'hub-elementor-addons' ),
					'left' => __( 'Left', 'hub-elementor-addons' ),
					'top-left' => __( 'Top Left', 'hub-elementor-addons' ),
				],
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_random_move',
			[
				'label' => __( 'Enable Random Move', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_straight_move',
			[
				'label' => __( 'Enable Straight Move', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'move_out_mode',
			[
				'label' => __( 'Out Mode', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'out',
				'options' => [
					'out' => __( 'Out', 'hub-elementor-addons' ),
					'bounce' => __( 'Bounce', 'hub-elementor-addons' ),
				],
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_bounce_move',
			[
				'label' => __( 'Enable Bounce', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_attract_move',
			[
				'label' => __( 'Enable Attract', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'enable_move' => 'yes'
				]
			]
		);

		$this->add_control(
			'move_attract_rotatex',
			[
				'label' => __( 'Attract Rotate X', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 3000', 'hub-elementor-addons' ),
				'condition' => [
					'enable_attract_move' => 'yes'
				]
			]
		);
		
		$this->add_control(
			'move_attract_rotatey',
			[
				'label' => __( 'Attract Rotate Y', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 1500', 'hub-elementor-addons' ),
				'condition' => [
					'enable_attract_move' => 'yes'
				]
			]
		);
		$this->end_controls_section();

		// Section Interactivity
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Interactivity', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'detect_on',
			[
				'label' => __( 'Detect on', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => __( 'None', 'hub-elementor-addons' ),
					'canvas' => __( 'Canvas', 'hub-elementor-addons' ),
					'window' => __( 'Window', 'hub-elementor-addons' ),
				],
			]
		);

		$this->add_control(
			'enable_onhover',
			[
				'label' => __( 'Enable onhover events', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'onhover_mode',
			[
				'label' => __( 'Onhover mode', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'grab',
				'options' => [
					'grab' => __( 'Grab', 'hub-elementor-addons' ),
					'bubble' => __( 'Bubble', 'hub-elementor-addons' ),
					'repulse' => __( 'Repulse', 'hub-elementor-addons' ),
				],
				'condition' => [
					'enable_onhover' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_onclick',
			[
				'label' => __( 'Enable onclick event', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'onclick_mode',
			[
				'label' => __( 'Onclick mode', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'push',
				'options' => [
					'push' => __( 'Push', 'hub-elementor-addons' ),
					'remove' => __( 'Remove', 'hub-elementor-addons' ),
					'bubble' => __( 'Bubble', 'hub-elementor-addons' ),
					'repulse' => __( 'Repulse', 'hub-elementor-addons' ),
				],
				'condition' => [
					'enable_onclick' => 'yes'
				]
			]
		);

		$this->add_control(
			'enable_inter_resize',
			[
				'label' => __( 'Enable resize', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'modes_grab_distance',
			[
				'label' => __( 'Grab Distance', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 100', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'modes_grab_opacity',
			[
				'label' => __( 'Grab Line Opacity', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1,
						'step' => 0.05,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
			]
		);

		$this->add_control(
			'modes_bubble_distance',
			[
				'label' => __( 'Bubble Distance', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 100', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'modes_bubble_size',
			[
				'label' => __( 'Bubble Size', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 40', 'hub-elementor-addons' ),
			]
		);
		
		$this->add_control(
			'modes_bubble_duration',
			[
				'label' => __( 'Bubble Duration', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'second (ex 0.4)', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'modes_repulse_distance',
			[
				'label' => __( 'Repulse Distance', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 200', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'modes_repulse_duration',
			[
				'label' => __( 'Repulse Duration', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'second (ex 1.2)', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'modes_push_particles_nb',
			[
				'label' => __( 'Push particles number', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 4', 'hub-elementor-addons' ),
			]
		);

		$this->add_control(
			'modes_remove_particles_nb',
			[
				'label' => __( 'Remove particles number', 'hub-elementor-addons' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => __( 'for ex 4', 'hub-elementor-addons' ),
			]
		);
		$this->end_controls_section();
		
		// Retina Section
		$this->start_controls_section(
			'retina_section',
			[
				'label' => __( 'Retina', 'hub-elementor-addons' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'retina_detect',
			[
				'label' => __( 'Retina Detect', 'hub-elementor-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'hub-elementor-addons' ),
				'label_off' => __( 'Off', 'hub-elementor-addons' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Render heading widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute(
			'wrapper',
			[
				'class' => [
					'ld-particles-container',
					'pos-rel',
					'w-100',
					$this->get_visible_on_hover(),
					$this->getAsBg(),
					$settings['particle_id'],
				],
			]
		);

	?>

		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php $this->get_particle_items() ?>	
		</div>

	<?php
	
	}

	protected function get_particle_items() {
		
		extract( $this->get_settings_for_display() );
		
		$out = '';
		$opts = $this->get_options();
		
		$the_ID = isset( $particle_id ) ? $particle_id : '';

		printf( '<div class="ld-particles-inner lqd-overlay pointer-events-none" id="%s" data-particles="true" %s></div>', $the_ID, $opts  );

	}

	protected function getAsBg() {

		$settings = $this->get_settings_for_display();

		if( ! $settings['as_bg'] ) {
			return;
		}
		
		return 'lqd-particles-as-bg lqd-overlay';

	}

	public function get_visible_on_hover() {

		$settings = $this->get_settings_for_display();
		
		if( ! $settings['visible_on_hover'] ) {
			return;
		}
		
		return 'visible-on-column-hover';
		
	}

	protected function get_options() {
		
		extract( $this->get_settings_for_display() );
		
		$data = '';
		$options = $particle_opts = $interactivity_opts = $number_opts = $shape_opts = $stroke_opts = $image_opts = $opacity_opts = $opacity_anim_opts = $size_opts = $size_anim_opts = $line_linked_opts = $move_opts = $move_attract_opts = $onohver_opts = $events_opts = $onclick_opts = $modes_opts = $bubble_opts = $repulse_opts = $density_opts = array();
		
		
		if( !empty( $number ) ) {
			$number_opts['value'] = (int)$number;
		}
		if( $enable_density ) {
			$density_opts['enable'] = true;
		}
		if( !empty( $density ) ) {
			$density_opts['value_area'] = $density;
		}
		if( !empty( $density_opts ) ) {
			$number_opts['density'] = (int)$density_opts;
		}
		//Number of elements
		if( !empty( $number_opts ) ) {
			$particle_opts['number'] = $number_opts;
		}
		//Background Color
		if( 'single_color' === $color_type ) {
			if( !empty( $color ) ) {
				$particle_opts['color'] = array( 'value' => $color );
			}
		}
		elseif( 'multi_color' === $color_type ) {
			$colors = array();
			$color_arr = $multi_color_values ;
			//$color_arr = liquid_normalize_group_atts($multi_color_values);
			//$color_arr = liquid_param_group_parse_atts( $multi_color_values );
			foreach ( $color_arr as $color ) {
				$colors[] = $color['scolor'];
			}
			$particle_opts['color'] = array( 'value' => $colors );
		}
		else {
			$particle_opts['color'] = array( 'value' => 'random' );	
		}
		
		//Shape options

		if( !empty( $shape_type ) ) {
			$shape_arr = $shape_type;
			$shape_opts['type'] = $shape_arr;
		}
		
		if( !empty( $stroke_width ) ) {
			$stroke_opts['width'] = (int)$stroke_width;	
		}
		if( !empty( $stroke_color ) ) {
			$stroke_opts['color'] = $stroke_color;	
		}
		if( !empty( $stroke_opts ) ) {
			$shape_opts['stroke'] = $stroke_opts;
		}
		if( !empty( $nb_sides ) ) {
			$shape_opts['polygon'] = array( 'nb_sides' => (int)$nb_sides );
		}
		if( !empty( $image ) ) {
			$url = wp_get_attachment_image_url( $image['id'], 'full', false );
			$image_opts['src'] = esc_url( $url );
		}
		if( !empty( $image_width ) ) {
			$image_opts['width'] = (int)$image_width;
		}
		if( !empty( $image_height ) ) {
			$image_opts['height'] = (int)$image_height;
		}
		if( !empty( $image_opts ) ) {
			$shape_opts['image'] = $image_opts;
		}
		if( !empty( $shape_opts ) ) {
			$particle_opts['shape'] = $shape_opts;
		}
		
		//Opacity values
		if( '1' !== $opacity ) {
			$opacity_opts['value'] = (float)$opacity;
		}
		if( $enable_random_opacity ) {
			$opacity_opts['random'] = true;
		}
		if( $enable_anim_opacity ) {
			$opacity_anim_opts['enable'] = true;
			$opacity_anim_opts['opacity_min'] = (float)$anim_opacity_min;
		}
		if( !empty( $anim_opacity_speed ) ) {
			$opacity_anim_opts['speed'] = (int)$anim_opacity_speed;
		}
		if( $enable_anim_opacity ) {
			$opacity_anim_opts['sync'] = true;
		}
		if( !empty( $opacity_anim_opts ) ) {
			$opacity_opts['anim'] = $opacity_anim_opts;
		}
		if( !empty( $opacity_opts ) ) {
			$particle_opts['opacity'] = $opacity_opts;
		}
		
		//Size values
		if( !empty( $size ) ) {
			$size_opts['value'] = (int)$size;
		}
		if( $enable_random_size ) {
			$size_opts['random'] = true;
		}
		if( $enable_anim_size ) {
			$size_anim_opts['enable'] = true;
			$size_anim_opts['size_min'] = (float)$anim_size_min;
		}
		if( !empty( $anim_size_speed ) ) {
			$size_anim_opts['speed'] = (int)$anim_size_speed;
		}
		if( $enable_anim_size_sync ) {
			$size_anim_opts['sync'] = true;
		}
		if( !empty( $size_anim_opts ) ) {
			$size_opts['anim'] = $size_anim_opts;
		}
		if( !empty( $size_opts ) ) {
			$particle_opts['size'] = $size_opts;
		}
		
		//Linked line
		if( $enable_line_linked ) {
			$line_linked_opts['enable'] = true;
			$line_linked_opts['opacity'] = (float)$line_opacity;
		}
		if( !empty( $line_distance ) ) {
			$line_linked_opts['distance'] = (int)$line_distance;
		}
		if( !empty( $line_color ) ) {
			$line_linked_opts['color'] = $line_color;
		}
		if( !empty( $line_width ) ) {
			$line_linked_opts['width'] = (int)$line_width;
		}
		
		if( !empty( $line_linked_opts ) ) {
			$particle_opts['line_linked'] = $line_linked_opts;
		}
		
		//Move values
		if( $enable_move ) {
			$move_opts['enable'] = true;
			$move_opts['direction'] = $move_direction;
		}
		if( !empty( $move_speed ) ) {
			$move_opts['speed'] = (float)$move_speed;
		}
		if( $enable_random_move ) {
			$move_opts['random'] = true;
		}
		if( $enable_straight_move ) {
			$move_opts['straight'] = true;
		}
		if( isset( $move_out_mode ) ) {
			$move_opts['out_mode'] = $move_out_mode;	
		}
		if( $enable_bounce_move ) {
			$move_opts['bounce'] = true;
		}
		if( $enable_attract_move ) {
			$move_attract_opts['enable'] = true;
		}
		if( !empty( $move_attract_rotatex ) ) {
			$move_attract_opts['rotateX'] = (int)$move_attract_rotatex;
		}
		if( !empty( $move_attract_rotatey ) ) {
			$move_attract_opts['rotateY'] = (int)$move_attract_rotatey;
		}
		if( !empty( $move_attract_opts ) ) {
			$move_opts['attract'] = $move_attract_opts;
		}
		
		
		if( !empty( $move_opts ) ) {
			$particle_opts['move'] = $move_opts;
		}

		$options['particles']     = $particle_opts;
		
		if( !empty( $detect_on ) ) {
			$interactivity_opts['detect_on'] = $detect_on;
		}
		
		if( $enable_onhover ) {
			$onhover_arr = explode( ',', $onhover_mode );
			$events_opts['onhover'] = array( 'enable' => true, 'mode' => $onhover_arr );
		}
		if( $enable_onclick ) {
			$onclick_arr = explode( ',', $onclick_mode );
			$events_opts['onclick'] = array( 'enable' => true, 'mode' => $onclick_arr );
		}
		
		if( $enable_inter_resize ) {
			$events_opts['resize'] = true;
		}
		if( !empty( $events_opts ) ) {
			$interactivity_opts['events'] = $events_opts;
		}
		
		
		if( !empty( $modes_grab_distance ) ) {
			$modes_opts['grab'] = array( 'distance' => (int)$modes_grab_distance, 'line_linked' => array( 'opacity' => $modes_grab_opacity ) );
		}
		
		if( !empty( $modes_bubble_distance ) ) {
			$bubble_opts['distance'] = (int)$modes_bubble_distance;
		}
		if( !empty( $modes_bubble_size ) ) {
			$bubble_opts['size'] = (int)$modes_bubble_size;
		}
		if( !empty( $modes_bubble_duration ) ) {
			$bubble_opts['duration'] = (float)$modes_bubble_duration;
		}
		if( !empty( $bubble_opts ) ) {
			$modes_opts['bubble'] = $bubble_opts;
		}


		if( !empty( $modes_repulse_distance ) ) {
			$repulse_opts['distance'] = (int)$modes_repulse_distance;	
		}
		if( !empty( $modes_repulse_duration ) ) {
			$repulse_opts['duration'] = (float)$modes_repulse_duration;
		}
		if( !empty( $repulse_opts ) ) {
			$modes_opts['repulse'] = $repulse_opts;
		}


		
		if( !empty( $modes_push_particles_nb ) ) {
			$modes_opts['push'] = array( 'particles_nb' => (int)$modes_push_particles_nb );
		}
		if( !empty( $modes_remove_particles_nb ) ) {
			$modes_opts['remove'] = array( 'particles_nb' => (int)$modes_remove_particles_nb );
		}
		if( !empty( $modes_opts ) ) {
			$interactivity_opts['modes'] = $modes_opts;
			//array_push($interactivity_opts, ['modes' => $modes_opts]);
		}
		
		//array_push($options, ['interactivity' => $interactivity_opts]);
		$options['interactivity'] = $interactivity_opts;
		
		
		if( $retina_detect ) {
			//array_push($options, ['retina_detect' => true]);
			$options['retina_detect'] = true;
		}

		
		if( $as_bg ) {
			$options['asBG'] = true;
			//array_push($options, ['asBG' => true]);
		}
		
		
		if( !empty( $options ) ) {
			$data = 'data-particles-options=\'' . wp_json_encode( $options ) .'\'';	
		}
		
		
		return $data;
		
	}

}
\Elementor\Plugin::instance()->widgets_manager->register( new LD_Particles() );