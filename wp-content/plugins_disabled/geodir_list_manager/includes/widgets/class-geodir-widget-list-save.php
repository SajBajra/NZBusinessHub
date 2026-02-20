<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GeoDir_Widget_Add_List
 *
 * @since 2.0.0
 */
class GeoDir_Widget_List_Save extends WP_Super_Duper {

    /**
     * GeoDir_Widget_Add_List constructor.
     *
     * @since 2.0.0
     */
    public function __construct() {

        $options = array(
            'textdomain'    => GD_LISTS_TEXTDOMAIN,
            'block-icon'    => 'admin-site',
            'block-category'=> 'geodirectory',
            'block-keywords'=> "['save','list','geodir']",
            'class_name'    => __CLASS__,
            'base_id'       => 'gd_list_save',
            'name'          => __('GD > List Save','gd-lists'),
            'widget_ops'    => array(
                'classname'       => 'geodir-list-save-container' . ( geodir_design_style() ? ' bsui' : '' ),
                'description'     => esc_html__( 'Shows button to add a listing to the list.', 'gd-lists' ),
                'geodirectory'    => true,
                'gd_wgt_showhide' => 'show_on',
                'gd_wgt_restrict' => array( 'gd-detail' ),
            ),
        );

        parent::__construct( $options );
    }

	/**
	 * Set widget arguments.
	 *
	 */
	public function set_arguments() {
		$arguments = array(
			'save_text'  => array(
				'type' => 'text',
				'title' => __('Button save text', 'gd-lists'),
				'desc' => __('The text used by the button to save to a list. (Leave empty to be able to use translations)', 'gd-lists'),
				'placeholder' => __('Save', 'gd-lists'),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'save_icon_class'  => array(
				'type' => 'text',
				'title' => __('Button save icon', 'gd-lists'),
				'desc' => __('Enter a FontAwesome icon class here and it will be displayed in the button.', 'gd-lists'),
				'placeholder' => 'far fa-bookmark',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'saved_text'  => array(
				'type' => 'text',
				'title' => __('Button saved text', 'gd-lists'),
				'desc' => __('The text used by the button to save to a list. (Leave empty to be able to use translations)', 'gd-lists'),
				'placeholder' => __('Saved', 'gd-lists'),
				'default' => '',
				'desc_tip' => true,
				'advanced' => true
			),
			'saved_icon_class'  => array(
				'type' => 'text',
				'title' => __('Button saved icon', 'gd-lists'),
				'desc' => __('Enter a FontAwesome icon class here and it will be displayed in the button when a post is saved by the user.', 'gd-lists'),
				'placeholder' => 'fas fa-bookmark',
				'default' => '',
				'desc_tip' => true,
				'advanced' => true
			)
		);

		$design_style = geodir_design_style();

		if ( $design_style ) {
			$arguments['shadow'] = array(
				'title' => __( 'Shadow', 'geodirectory' ),
				'desc' => __( 'Select the shadow badge type.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					"" => __( 'None', 'geodirectory' ),
					"small" => __( 'small', 'geodirectory' ),
					"medium" => __( 'medium', 'geodirectory' ),
					"large" => __( 'large', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['color'] = array(
				'title' => __( 'Badge Color', 'geodirectory' ),
				'desc' => __( 'Select the the badge color.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					"" => __( 'Custom colors', 'geodirectory' ),
					) + geodir_aui_colors( true ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);
		}

		$arguments['bg_color'] = array(
			'type' => 'color',
			'title' => __('Badge background color:','gd-lists'),
			'desc' => __('Color for the badge background.','gd-lists'),
			'placeholder' => '',
			'default' => '#0073aa',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' ),
			'element_require' => $design_style ? '[%color%]==""' : '',
		);

		$arguments['txt_color'] = array(
			'type' => 'color',
			'title' => __('Badge text color:','gd-lists'),
			'desc' => __('Color for the badge text.','gd-lists'),
			'placeholder' => '',
			'default'  => '#ffffff',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' ),
			'element_require' => $design_style ? '[%color%]==""' : '',
		);

		$arguments['size'] = array(
			'type' => 'select',
			'title' => __('Badge size:','gd-lists'),
			'desc' => __('Size of the badge.','gd-lists'),
			'options' => array(
				"small" => __('Small','gd-lists'),
				"" => __('Normal','gd-lists'),
				"medium" => __('Medium','gd-lists'),
				"large" => __('Large','gd-lists'),
				"extra-large" => __('Extra Large','gd-lists'),
			),
			'default' => '',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' ),
		);

		$arguments['alignment'] = array(
			'type' => 'select',
			'title' => __('Alignment:','gd-lists'),
			'desc' => __('How the item should be positioned on the page.','gd-lists'),
			'options' =>  array(
				"" => __('None','gd-lists'),
				"left" => __('Left','gd-lists'),
				"center" => __('Center','gd-lists'),
				"right" => __('Right','gd-lists'),
			),
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' ),
		);

		$arguments['css_class']  = array(
			'type' => 'text',
			'title' => __('Extra class:', 'gd-lists'),
			'desc' => __('Give the wrapper an extra class so you can style things as you want.', 'gd-lists'),
			'placeholder' => '',
			'default' => '',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' ),
		);

		return $arguments;
	}

	/**
	 * Display Widget output.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Get Arguments.
	 * @param array $widget_args Get widget arguments.
	 * @param string $content Get widget content.
	 * @return string
	 *
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $gd_post;

		$design_style = geodir_design_style();

		$output = '';
		$post_id = ! empty( $gd_post->ID ) ? absint( $gd_post->ID ) : '';

		if ( $post_id ) {
			$defaults = array(
				'onclick' => 'gd_list_save_to_list_dialog(' . $post_id . ', this);',
				'extra_attributes' => 'data-lists-save-id=' . $post_id,
				'css_class' => ''
			);

			$params = wp_parse_args( $args, $defaults );

			$params['css_class'] .= ' c-pointer gd-lists-save-button gd-list-save-' . (int) $post_id;

			// Set some vars
			$save_text = !empty($params['save_text']) ? __($params['save_text'],'gd-lists') : __('Save', 'gd-lists');
			$save_icon = !empty($params['save_icon_class']) ? esc_attr($params['save_icon_class']) : 'far fa-bookmark';
			$saved_text = !empty($params['saved_text']) ? __($params['saved_text'],'gd-lists') : __('Saved', 'gd-lists');
			$saved_icon = !empty($params['saved_icon_class']) ? esc_attr($params['saved_icon_class']) : 'fas fa-bookmark';

			// Set texts and classes
			$params['badge'] = $save_text;
			$params['icon_class'] = $save_icon . ' gd-list-icon mr-1 me-1';

			$user_id = get_current_user_id();

			if ( $user_id ) {
				$in_user_lists = GeoDir_Lists_Data::in_user_lists($post_id);

				if($in_user_lists){
					$params['badge'] = $saved_text;
					$params['css_class'] .= ' gd-lists-is-in-user-lists';
					$params['icon_class'] = $saved_icon . ' gd-list-icon mr-1 me-1';
				}
			}

			if ( ! empty( $params['size'] ) ) {
				switch ($params['size']) {
					case 'small':
						$params['size'] = $design_style ? '' : 'small';
						break;
					case 'medium':
						$params['size'] = $design_style ? 'h4' : 'medium';
						break;
					case 'large':
						$params['size'] = $design_style ? 'h2' : 'large';
						break;
					case 'extra-large':
						$params['size'] = $design_style ? 'h1' : 'extra-large';
						break;
					case 'h6': $params['size'] = 'h6';break;
					case 'h5': $params['size'] = 'h5';break;
					case 'h4': $params['size'] = 'h4';break;
					case 'h3': $params['size'] = 'h3';break;
					case 'h2': $params['size'] = 'h2';break;
					case 'h1': $params['size'] = 'h1';break;
					default:
						$params['size'] = '';

				}
			}

			// data attributes
			$params['extra_attributes'] .= ' data-lists-save-text="'.$save_text.'"';
			$params['extra_attributes'] .= ' data-lists-saved-text="'.$saved_text.'"';
			$params['extra_attributes'] .= ' data-lists-save-icon="'.$save_icon.'"';
			$params['extra_attributes'] .= ' data-lists-saved-icon="'.$saved_icon.'"';

			if ( $design_style ) {
				$alignment = $params['alignment'];
				$params['alignment'] = '';
			}

			$output = geodir_get_post_badge( $post_id, $params );

			if ( $design_style && ! empty( $alignment ) ) {
				$output = '<div class="text-' . sanitize_html_class( $alignment ) . '">' . $output . '</div>';
			}
		}

		return $output;
	}
}