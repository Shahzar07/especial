<?php
/**
 * Customizer settings.
 *
 * Everything the source project left as a {{placeholder}} in lib/config.ts —
 * brand, wordmark, tagline, hero copy, categories — resolves here, so a rebrand
 * is a Customizer session rather than a code edit.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers panels, sections, settings and controls.
 */
class Customizer {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'register' ) );
	}

	/**
	 * Registers everything.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	public function register( $wp_customize ) {
		$wp_customize->add_panel(
			'eg_panel',
			array(
				'title'    => __( 'Especial Gallery', 'especial-gallery' ),
				'priority' => 20,
			)
		);

		$this->brand( $wp_customize );
		$this->hero( $wp_customize );
		$this->home( $wp_customize );
		$this->gate( $wp_customize );
		$this->colors( $wp_customize );
		$this->footer( $wp_customize );

		// Live preview for the text that carries the brand.
		foreach ( array( 'eg_brand', 'eg_wordmark', 'eg_tagline' ) as $setting ) {
			$control = $wp_customize->get_setting( $setting );
			if ( $control ) {
				$control->transport = 'postMessage';
			}
		}

		$wp_customize->selective_refresh->add_partial(
			'eg_wordmark',
			array(
				'selector'        => '.eg-wordmark',
				'render_callback' => 'eg_wordmark',
			)
		);
	}

	/**
	 * Adds a setting and its control in one call.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @param string                $id           Setting ID.
	 * @param array                 $args         Control arguments plus `default` and `sanitize`.
	 * @return void
	 */
	private function add( $wp_customize, $id, array $args ) {
		$default  = isset( $args['default'] ) ? $args['default'] : '';
		$sanitize = isset( $args['sanitize'] ) ? $args['sanitize'] : 'sanitize_text_field';

		unset( $args['default'], $args['sanitize'] );

		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $default,
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control( $id, $args );
	}

	/**
	 * Brand identity.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	private function brand( $wp_customize ) {
		$wp_customize->add_section(
			'eg_brand_section',
			array(
				'title' => __( 'Brand', 'especial-gallery' ),
				'panel' => 'eg_panel',
			)
		);

		$this->add(
			$wp_customize,
			'eg_brand',
			array(
				'label'       => __( 'Brand name', 'especial-gallery' ),
				'description' => __( 'Defaults to the site title.', 'especial-gallery' ),
				'section'     => 'eg_brand_section',
				'type'        => 'text',
			)
		);

		$this->add(
			$wp_customize,
			'eg_wordmark',
			array(
				'label'       => __( 'Wordmark', 'especial-gallery' ),
				'description' => __( 'Shown letterspaced in the header. Usually the brand name in capitals.', 'especial-gallery' ),
				'section'     => 'eg_brand_section',
				'type'        => 'text',
			)
		);

		$this->add(
			$wp_customize,
			'eg_tagline',
			array(
				'label'   => __( 'Tagline', 'especial-gallery' ),
				'section' => 'eg_brand_section',
				'type'    => 'text',
				'default' => __( 'Objects for people who look twice.', 'especial-gallery' ),
			)
		);

		$this->add(
			$wp_customize,
			'eg_contact_email',
			array(
				'label'    => __( 'Contact email', 'especial-gallery' ),
				'section'  => 'eg_brand_section',
				'type'     => 'email',
				'sanitize' => 'sanitize_email',
			)
		);
	}

	/**
	 * The hero banner.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	private function hero( $wp_customize ) {
		$wp_customize->add_section(
			'eg_hero_section',
			array(
				'title'       => __( 'Hero', 'especial-gallery' ),
				'panel'       => 'eg_panel',
				'description' => __( 'The artwork is laid out from its own dimensions and never cropped, so a file of any shape drops in. The wordmark is set over it in white, so leave the left of the wide image and the lower part of the tall one quiet.', 'especial-gallery' ),
			)
		);

		$wp_customize->add_setting(
			'eg_hero_image',
			array(
				'default'           => '',
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			new \WP_Customize_Media_Control(
				$wp_customize,
				'eg_hero_image',
				array(
					'label'     => __( 'Hero image (wide)', 'especial-gallery' ),
					'section'   => 'eg_hero_section',
					'mime_type' => 'image',
				)
			)
		);

		$wp_customize->add_setting(
			'eg_hero_image_mobile',
			array(
				'default'           => '',
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			new \WP_Customize_Media_Control(
				$wp_customize,
				'eg_hero_image_mobile',
				array(
					'label'       => __( 'Hero image (tall, mobile)', 'especial-gallery' ),
					'description' => __( 'Optional. The wide image is used on every screen when this is empty.', 'especial-gallery' ),
					'section'     => 'eg_hero_section',
					'mime_type'   => 'image',
				)
			)
		);

		$this->add(
			$wp_customize,
			'eg_hero_eyebrow',
			array(
				'label'   => __( 'Eyebrow', 'especial-gallery' ),
				'section' => 'eg_hero_section',
				'type'    => 'text',
				'default' => __( 'Current release', 'especial-gallery' ),
			)
		);

		$this->add(
			$wp_customize,
			'eg_hero_title',
			array(
				'label'   => __( 'Headline', 'especial-gallery' ),
				'section' => 'eg_hero_section',
				'type'    => 'text',
			)
		);

		$this->add(
			$wp_customize,
			'eg_hero_facts',
			array(
				'label'       => __( 'Facts', 'especial-gallery' ),
				'description' => __( 'One per line. Three reads best.', 'especial-gallery' ),
				'section'     => 'eg_hero_section',
				'type'        => 'textarea',
				'sanitize'    => 'sanitize_textarea_field',
			)
		);

		$this->add(
			$wp_customize,
			'eg_hero_cta',
			array(
				'label'   => __( 'Link text', 'especial-gallery' ),
				'section' => 'eg_hero_section',
				'type'    => 'text',
				'default' => __( 'See the object', 'especial-gallery' ),
			)
		);

		$this->add(
			$wp_customize,
			'eg_hero_url',
			array(
				'label'       => __( 'Link target', 'especial-gallery' ),
				'description' => __( 'Defaults to the newest product.', 'especial-gallery' ),
				'section'     => 'eg_hero_section',
				'type'        => 'url',
				'sanitize'    => 'esc_url_raw',
			)
		);
	}

	/**
	 * Front-page section headings and the editorial block.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	private function home( $wp_customize ) {
		$wp_customize->add_section(
			'eg_home_section',
			array(
				'title' => __( 'Front page', 'especial-gallery' ),
				'panel' => 'eg_panel',
			)
		);

		$headings = array(
			'eg_heading_release'    => array( __( 'Release section title', 'especial-gallery' ), __( 'Current release', 'especial-gallery' ) ),
			'eg_heading_how'        => array( __( '"How we work" title', 'especial-gallery' ), __( 'How we work', 'especial-gallery' ) ),
			'eg_heading_categories' => array( __( 'Categories title', 'especial-gallery' ), __( 'Categories', 'especial-gallery' ) ),
			'eg_heading_everything' => array( __( 'Catalogue title', 'especial-gallery' ), __( 'Everything', 'especial-gallery' ) ),
			'eg_heading_ordering'   => array( __( 'Ordering title', 'especial-gallery' ), __( 'Ordering', 'especial-gallery' ) ),
			'eg_heading_specs'      => array( __( 'Specification title', 'especial-gallery' ), __( 'What the objects are', 'especial-gallery' ) ),
		);

		foreach ( $headings as $id => $meta ) {
			$this->add(
				$wp_customize,
				$id,
				array(
					'label'   => $meta[0],
					'section' => 'eg_home_section',
					'type'    => 'text',
					'default' => $meta[1],
				)
			);
		}

		$this->add(
			$wp_customize,
			'eg_editorial_page',
			array(
				'label'       => __( 'Editorial block', 'especial-gallery' ),
				'description' => __( 'A page whose content is printed at the foot of the front page. This is where organic traffic lands, so the long-form copy lives here.', 'especial-gallery' ),
				'section'     => 'eg_home_section',
				'type'        => 'dropdown-pages',
				'sanitize'    => 'absint',
			)
		);

		$this->add(
			$wp_customize,
			'eg_products_per_page',
			array(
				'label'       => __( 'Products per page', 'especial-gallery' ),
				'section'     => 'eg_home_section',
				'type'        => 'number',
				'default'     => 24,
				'sanitize'    => 'absint',
				'input_attrs' => array(
					'min' => 1,
					'max' => 96,
				),
			)
		);
	}

	/**
	 * The gate.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	private function gate( $wp_customize ) {
		$wp_customize->add_section(
			'eg_gate_section',
			array(
				'title'       => __( 'Mailing-list gate', 'especial-gallery' ),
				'panel'       => 'eg_panel',
				'description' => __( 'This is a mailing-list capture, not access control: search and social crawlers are let through on a spoofable User-Agent so links still rank and unfurl. Never put anything sensitive behind it.', 'especial-gallery' ),
			)
		);

		$this->add(
			$wp_customize,
			'eg_gate_enabled',
			array(
				'label'    => __( 'Enable the gate', 'especial-gallery' ),
				'section'  => 'eg_gate_section',
				'type'     => 'checkbox',
				'default'  => true,
				'sanitize' => 'rest_sanitize_boolean',
			)
		);

		$this->add(
			$wp_customize,
			'eg_gate_headline',
			array(
				'label'   => __( 'Headline', 'especial-gallery' ),
				'section' => 'eg_gate_section',
				'type'    => 'text',
				'default' => __( 'Sign up for our list', 'especial-gallery' ),
			)
		);

		$this->add(
			$wp_customize,
			'eg_preview_token',
			array(
				'label'       => __( 'Preview token', 'especial-gallery' ),
				'description' => __( 'Append ?eg_preview=TOKEN to any URL to skip the gate. Leave empty to disable the bypass entirely.', 'especial-gallery' ),
				'section'     => 'eg_gate_section',
				'type'        => 'text',
			)
		);
	}

	/**
	 * Colour overrides.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	private function colors( $wp_customize ) {
		$wp_customize->add_section(
			'eg_colors_section',
			array(
				'title'       => __( 'Colour', 'especial-gallery' ),
				'panel'       => 'eg_panel',
				'description' => __( 'The system reserves colour for the product photography and the focus ring. Change these only with a reason.', 'especial-gallery' ),
			)
		);

		$colors = array(
			'eg_color_accent' => array( __( 'Accent', 'especial-gallery' ), '#1B34FF' ),
			'eg_color_ink'    => array( __( 'Ink', 'especial-gallery' ), '#000000' ),
			'eg_color_paper'  => array( __( 'Paper', 'especial-gallery' ), '#FFFFFF' ),
			'eg_color_rule'   => array( __( 'Rule', 'especial-gallery' ), '#E6E6E4' ),
		);

		foreach ( $colors as $id => $meta ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $meta[1],
					'sanitize_callback' => 'sanitize_hex_color',
				)
			);
			$wp_customize->add_control(
				new \WP_Customize_Color_Control(
					$wp_customize,
					$id,
					array(
						'label'   => $meta[0],
						'section' => 'eg_colors_section',
					)
				)
			);
		}
	}

	/**
	 * Footer copy.
	 *
	 * @param \WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	private function footer( $wp_customize ) {
		$wp_customize->add_section(
			'eg_footer_section',
			array(
				'title' => __( 'Footer', 'especial-gallery' ),
				'panel' => 'eg_panel',
			)
		);

		$this->add(
			$wp_customize,
			'eg_newsletter_note',
			array(
				'label'   => __( 'Mailing-list note', 'especial-gallery' ),
				'section' => 'eg_footer_section',
				'type'    => 'textarea',
				'default' => __( 'Drop announcements and restocks. No more than twice a month.', 'especial-gallery' ),
				'sanitize' => 'sanitize_textarea_field',
			)
		);

		$this->add(
			$wp_customize,
			'eg_payment_marks',
			array(
				'label'       => __( 'Payment marks', 'especial-gallery' ),
				'description' => __( 'Comma separated. Set as words rather than logos so nothing is hotlinked.', 'especial-gallery' ),
				'section'     => 'eg_footer_section',
				'type'        => 'text',
				'default'     => 'Visa, Mastercard, Amex, PayPal, Apple Pay',
			)
		);

		$this->add(
			$wp_customize,
			'eg_copyright',
			array(
				'label'       => __( 'Copyright line', 'especial-gallery' ),
				'description' => __( 'Leave empty for "© year Brand".', 'especial-gallery' ),
				'section'     => 'eg_footer_section',
				'type'        => 'text',
			)
		);
	}
}
