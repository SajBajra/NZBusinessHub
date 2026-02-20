<?php

/**
 * Zone usage
 *
 * Display the zone usage meta box.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Meta_Box_Zone_Usage Class.
 */
class Adv_Meta_Box_Zone_Usage {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {
		global $aui_bs5;

        do_action( 'adv_zone_usage_meta_box_top', $post );
		?>

		<div class="adv-usage bsui">
			<?php

				// Ad injections.
				$inject = aui()->select(
					array(
						'id'            => 'adv_inject',
						'label'         => __( 'Ad Injection', 'advertising' ),
						'label_class'   => 'font-weight-bold',
						'wrap_class'    => 'py-3 py-0 border-bottom',
						'name'          => 'inject',
						'value'         => adv_zone_get_meta( $post->ID, 'inject', true, 'disabled' ),
						'placeholder'   => __( 'Ad Injection', 'advertising' ),
						'label_type'    => 'horizontal',
						'help_text'     => __( 'Automatically insert the zone in your content whenever it meets the display rules defined above.', 'advertising' ),
						'options'       => apply_filters(
							'adv_zone_injection_positions',
							array(
								'before'      => __( 'Before content', 'advertising' ),
								'after'       => __( 'After content', 'advertising' ),
							)
						),
						'multiple'      => true,
						'select2'       => true,
					)
				);
		
				echo str_replace( 'class="col-sm-10"', 'style="max-width: 27em;" class="col-sm-10"', $inject );

				// Shortcodes.
				$shortcode = aui()->input(
					array(
						'type'          => 'text',
						'id'            => 'adv_shortcode',
						'label'         => __( 'Shortcode', 'advertising' ),
						'label_class'   => 'font-weight-bold',
						'wrap_class'    => 'py-3 py-0 border-bottom',
						'value'         => esc_attr( adv_zone_shortcode( $post->ID ) ),
						'help_text'     => __( 'Use this shortcode to display the zone in a post or page.', 'advertising' ),
						'label_type'    => 'horizontal',
						'extra_attributes' => array(
							'onclick'  => 'this.select();',
							'readonly' => 'readonly',
						),
					)
				);
		
				echo str_replace( 'form-control', 'regular-text', $shortcode );

				$template = aui()->input(
					array(
						'type'          => 'text',
						'id'            => 'adv_template',
						'label'         => __( 'Template Code', 'advertising' ),
						'label_class'   => 'font-weight-bold',
						'wrap_class'    => 'py-3 py-0 border-bottom',
						'value'         => esc_attr( adv_zone_template_code( $post->ID ) ),
						'help_text'     => __( 'Use this code to insert the zone into your theme templates.', 'advertising' ),
						'label_type'    => 'horizontal',
						'extra_attributes' => array(
							'onclick'  => 'this.select();',
							'readonly' => 'readonly',
						),
					)
				);
		
				echo str_replace( 'form-control', 'regular-text', $template );

			?>
			<div class="mb-3 row pt-3">
				<label class="font-weight-bold fw-bold col-sm-2 col-form-label"><?php _e( 'Widget:', 'advertising' ); ?></label>
				<div class="col-sm-10">
					<p class="description"><?php printf( __( 'Add the %s widget to any widget area then select this zone.', 'advertising' ), '<strong>Advertising > Zone</strong>' ); ?></p>
				</div>
			</div>

		</div>

		<?php
		do_action( 'adv_zone_usage_meta_box_bottom', $post );

    }

}