<?php

// Check GeoDir_Lists_Forms class exists or not.
if ( ! class_exists( 'GeoDir_Lists_Forms' ) ) {

	/**
	 * GeoDir_Lists_Formss Class for the CPT actions.
	 *
	 * @since 2.0.0
	 *
	 * Class GeoDir_Lists_Forms
	 */
	class GeoDir_Lists_Forms {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GeoDir_Lists_Forms constructor.
		 */
		public function __construct() {

			// add_filter( 'geodir_loop_active', array( $this, 'maybe_loop' ) );

		}

		public static function edit_dialog( $post_id = '' ) {
			if ( geodir_design_style() ) {
				return self::edit_dialog_aui( $post_id );
			}

			$post = get_post( $post_id );
			$content = '';

			if ( ! empty( $post->ID ) && geodir_listing_belong_to_current_user( $post->ID ) ) {
				$content .= "<h2>" . sprintf( __( "Edit %s",'gd-lists' ), geodir_lists_name_singular() ) . "</h2>";
				$content .= "<form>";

				// list name
				$content .= "<label for='gd-list-name'>" . __( "Name",'gd-lists' ) . "</label>";
				$content .= '<input id="gd-list-name" name="list_name" type="text" value="'.esc_attr($post->post_title).'">';

				// list description
				$content .= "<label for='gd-list-description'>" . __( "Description",'gd-lists' ) . "</label>";
				$content .= '<textarea id="gd-list-description" name="list_description" class="gd-ios-scrollbars">'.esc_textarea($post->post_content).'</textarea>';

				// public or non-public
				$content .= '<label><input type="radio" name="is_public" value="publish" ' . checked( ( $post->post_status == '1' || $post->post_status == 'publish' ), $post->post_status, false ) . '><span>'. __( "Public",'gd-lists' ) .'</span> </label>';
				$content .= '<label><input type="radio" name="is_public" value="private" ' . checked( ! ( $post->post_status == '1' || $post->post_status == 'publish' ), $post->post_status, false ) . '><span>'. __( "Non-Public",'gd-lists' ) .'</span> </label>';

				// description
				$content .= '<p>'. sprintf( __( "A public %s is displayed on the site and can be viewed by anyone. A non-public %s can still be visible to others if you share a link to it.", 'gd-lists' ), geodir_lists_name_singular(), geodir_lists_name_singular() ) .'</p>';

				$content .= "<p>" . self::save_list_button( 0,$post_id ) . "</p>"; // live inside form so we can use JS: this

				$content .= "</form>";
			}

			return $content;
		}

		public static function edit_dialog_aui( $post_id = '' ) {
			global $aui_bs5;

			$post = get_post( $post_id );
			$content = '';

			if ( ! empty( $post->ID ) && geodir_listing_belong_to_current_user( $post->ID ) ) {
				$content .= self::get_header( wp_sprintf( __( "Edit %s",'gd-lists' ), geodir_lists_name_singular() ) );
				$content .= "<form>";

				// description
				$content .= '<div class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '"><p>'. sprintf( __( "A public %s is displayed on the site and can be viewed by anyone. A non-public %s can still be visible to others if you share a link to it.", 'gd-lists' ), geodir_lists_name_singular(), geodir_lists_name_singular() ) .'</p></div>';

				// list name
				$content .= aui()->input(
					array(
						'id' => 'gd-list-name',
						'name' => 'list_name',
						'required' => true,
						'label' => __( 'Name', 'gd-lists' ) . ' <span class="text-danger">*</span>',
						'label_type' => 'vertical',
						'type' => 'text',
						'value' => $post->post_title
					)
				);

				// list description
				$content .= aui()->textarea(
					array(
						'id' => 'gd-list-description',
						'name' => 'list_description',
						'required' => false,
						'label' => __( 'Description', 'gd-lists' ),
						'label_type' => 'vertical',
						'no_wrap' => false,
						'rows' => 2,
						'wysiwyg' => false,
						'value' => esc_textarea( $post->post_content )
					)
				);

				// public or non-public
				$content .= aui()->radio(
					array(
						'id' => 'is_public',
						'name' => 'is_public',
						'required' => true,
						'label' => '',
						'label_type' => 'vertical',
						'type' => 'radio',
						'value' => $post->post_status,
						'options' => array(
							'publish' => __( "Public",'gd-lists' ),
							'private' => __( "Non-Public",'gd-lists' )
						)
					)
				);

				$content .= self::save_list_button_aui( 0, $post_id );

				$content .= "</form>";
			}

			return $content;
		}

		public static function save_dialog( $post_id = '' ) {
			if ( geodir_design_style() ) {
				return self::save_dialog_aui( $post_id );
			}

			$user_id = get_current_user_id();
			$content = '';
			if ( $user_id ) {

				$user_lists = GeoDir_Lists_Data::get_user_lists( $user_id );

				$content .= "<h2>" . sprintf( __( "Save to %s", 'gd-lists' ), geodir_lists_name_singular() ) . "</h2>";
				$content .= "<form>";

				if ( empty( $user_lists ) ) {
					$content .= "<p>" . sprintf( __( "You don't have any %s yet.","gd-lists" ), geodir_lists_name_plural() ) . "</p>";
				} else {
					$content .= "<div class='gd-list-select-container'>";
					$content .= "<ul class='gd-list-select-container-list gd-ios-scrollbars'>";

					foreach ( $user_lists as $list ) {
						$image = GeoDir_Lists_Data::get_list_image( $list->ID, 'thumbnail' );
						$post_title = wp_strip_all_tags($list->post_title);
						$maybe_private = '';
						// maybe private? <i class="fas fa-user-secret" aria-hidden="true"></i>
						if($list->post_status=='private'){
							$maybe_private = '<i class="fas fa-user-secret" title="'.sprintf( __( "Non-public %s (you can still share a direct link with your friends)", 'gd-lists' ), geodir_lists_name_singular() ).'" aria-hidden="true"></i> ';
						}
						$post_link = get_permalink($list->ID);

						$action_link = self::get_list_save_button($list->ID,$post_id);

						$content .= "<li>";
						$content .= "<div class='gd-list-wrap'>";
						$content .= <<<HTML
	<span class='gd-list-image'>
		$image
  	</span> 
  	<span class='gd-list-info'>
		$maybe_private<a href="$post_link">$post_title</a>
  	</span> 
  	<span class='gd-list-actions'>
		$action_link
  	</span> 
HTML;
						$content .= "</div>";
						$content .= "</li>";
					}

					$content .= "</ul>";
					$content .= "</div>";


				}

				//$content .= print_r($user_lists, true);
				$content .= "</form>";

				$content .= "<p>" . self::create_new_list_button( $post_id ) . "</p>";

			} else {
				$content .= self::login_options( $post_id );
			}

			return $content;
		}

		public static function save_dialog_aui( $post_id = '' ) {
			global $aui_bs5;

			$user_id = get_current_user_id();
			$content = '';
			if ( $user_id ) {

				$user_lists = GeoDir_Lists_Data::get_user_lists( $user_id );

				$content .= self::get_header( wp_sprintf( __( "Save to %s", 'gd-lists' ), geodir_lists_name_singular() ) );
				$content .= "<form>";
				if ( empty( $user_lists ) ) {
					$content .= '<div class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '"><div class="alert alert-warning mb-4" role="alert">' . wp_sprintf( __( "You don't have any %s yet.", "gd-lists" ), geodir_lists_name_plural() ) . '</div></div>';
				} else {
					$content .= "<div class='" . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . " gd-list-select-container'>";
					$content .= '<table class="table table-sm m-0 gd-duplicate-table gd-list-select-container-list gd-ios-scrollbars"><thead><tr><th scope="col">' . __( 'Image', 'gd-lists' ) . '</th><th scope="col">' . __( 'Name', 'gd-lists' ) . '</th><th scope="col" class="text-center">' . __( 'Action', 'gd-lists' ) . '</th></tr></thead><tbody>';

					foreach ( $user_lists as $list ) {
						$image = GeoDir_Lists_Data::get_list_image( $list->ID, 'thumbnail' );
						$post_title = wp_strip_all_tags( $list->post_title );
						$maybe_private = '';
						// maybe private? <i class="fas fa-user-secret" aria-hidden="true"></i>
						if ( $list->post_status == 'private' ) {
							$maybe_private = '<i class="fas fa-user-secret ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ) . '" title="'.sprintf( __( "Non-public %s (you can still share a direct link with your friends)", 'gd-lists' ), geodir_lists_name_singular() ).'" aria-hidden="true"></i> ';
						}
						$action_link = self::get_list_save_button_aui( $list->ID, $post_id );

						$content .= '<tr><td>' . $image . '</td><td>' . $maybe_private . '<a href="' . get_permalink( $list->ID ) .'">' . $post_title . '</a></td><td class="text-center">' . $action_link . '</td></tr>';
					}

					$content .= "</tbody></table>";
					$content .= "</div>";
				}

				$content .= self::create_new_list_button_aui( $post_id );

				$content .= "</form>";
			} else {
				$content .= self::login_options( $post_id );
			}

			return $content;
		}

		public static function new_dialog( $post_id = '' ) {
			if ( geodir_design_style() ) {
				return self::new_dialog_aui( $post_id );
			}

			$user_id = get_current_user_id();
			$content = '';
			if ( $user_id ) {

				$content .= "<h2>" . sprintf( __( "New %s", 'gd-lists' ), geodir_lists_name_singular() ) . "</h2>";
				$content .= "<form>";

				// list name
				$content .= "<label for='gd-list-name'>" . __( "Name",'gd-lists' ) . "</label>";
				$content .= '<input id="gd-list-name" name="list_name" type="text">';

				// list description
//				$content .= "<label for='gd-list-description'>" . __( "Description",'gd-lists' ) . "</label>";
//				$content .= '<textarea id="gd-list-description" name="list_description"></textarea>';

				// public or non-public
				$content .= '<label><input type="radio" name="is_public" value="1" checked><span>'. __( "Public",'gd-lists' ) .'</span> </label>';
				$content .= '<label><input type="radio" name="is_public" value="0"><span>'. __( "Non-Public",'gd-lists' ) .'</span> </label>';

				// description
				$content .= '<p>'. sprintf( __( "A public %s is displayed on the site and can be viewed by anyone. A non-public %s can still be visible to others if you share a link to it.", 'gd-lists' ), geodir_lists_name_singular(), geodir_lists_name_singular() ) .'</p>';

				$content .= "<p>" . self::save_list_button( $post_id ) . "</p>"; // live inside form so we can use JS: this

				//$content .= print_r($user_lists, true);
				$content .= "</form>";


			} else {
				$content .= self::login_options( $post_id );
			}

			return $content;
		}

		public static function new_dialog_aui( $post_id = '' ) {
			global $aui_bs5;

			$user_id = get_current_user_id();
			$content = '';
			if ( $user_id ) {

				$content .= self::get_header( wp_sprintf( __( "New %s", 'gd-lists' ), geodir_lists_name_singular() ) );
				$content .= "<form>";

				// description
				$content .= '<div class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '"><p>'. sprintf( __( "A public %s is displayed on the site and can be viewed by anyone. A non-public %s can still be visible to others if you share a link to it.", 'gd-lists' ), geodir_lists_name_singular(), geodir_lists_name_singular() ) .'</p></div>';

				// list name
				$content .= aui()->input(
					array(
						'id' => 'gd-list-name',
						'name' => 'list_name',
						'required' => true,
						'label' => __( 'Name', 'gd-lists' ) . ' <span class="text-danger">*</span>',
						'label_type' => 'vertical',
						'type' => 'text'
					)
				);

				// public or non-public
				$content .= aui()->radio(
					array(
						'id' => 'is_public',
						'name' => 'is_public',
						'required' => true,
						'label' => '',
						'label_type' => 'vertical',
						'type' => 'radio',
						'value' => apply_filters( 'gd_list_default_is_public', 'publish' ),
						'options' => array(
							'publish' => __( "Public",'gd-lists' ),
							'private' => __( "Non-Public",'gd-lists' )
						)
					)
				);

				$content .= self::save_list_button_aui( $post_id );

				$content .= "</form>";
			} else {
				$content .= self::login_options( $post_id );
			}

			return $content;
		}

		public static function save_list_button( $post_id = '',$list_id = '') {
			if ( geodir_design_style() ) {
				return self::save_list_button_aui( $post_id, $list_id );
			}

			$args = array(
				'badge' =>  __( "Save", 'gd-lists' ),
				'onclick' => "gd_list_save_list(" . (int) $post_id . ", this, " . (int) $list_id . ");",
				'size' => 'medium',
				'css_class' => 'gd-pointer',
				'alignment' => 'right'
			);

			$content = geodir_get_post_badge( '', $args );

			return $content;
		}

		public static function save_list_button_aui( $post_id = '',$list_id = '') {
			global $aui_bs5;

			$button = aui()->button(
				array(
					'type' => 'a',
					'href' => 'javascript:void(0);',
					'class' => 'btn btn-sm btn-primary',
					'icon' => 'fas fa-bookmark ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ),
					'content' => __( "Save", 'gd-lists' ),
					'no_wrap' => true,
					'onclick' => 'gd_list_save_list(' . (int) $post_id . ', this, ' . (int) $list_id . ');'
				)
			);

			$content = AUI_Component_Input::wrap(
				array(
					'content' => $button,
					'class' => ( $aui_bs5 ? '' : 'form-group ' ) . 'text-center mb-0 pt-3'
				)
			);

			return $content;
		}

		public static function create_new_list_button( $post_id ) {
			if ( geodir_design_style() ) {
				return self::create_new_list_button_aui( $post_id );
			}

			$args = array(
				'badge' => '<i class="fas fa-bookmark" aria-hidden="true"></i> ' . sprintf( __( "Save to a New %s", 'gd-lists' ), geodir_lists_name_singular() ),
				'onclick' => "gd_list_create_new_list_dialog(" . (int) $post_id . ");",
				'size' => $design_style ? 'h4' : 'medium',
				'css_class' => 'gd-pointer' . ( $design_style ? ' mt-3' : '' )
			);

			$content = geodir_get_post_badge( $post_id, $args );

			return $content;
		}

		public static function create_new_list_button_aui( $post_id ) {
			global $aui_bs5;

			$button = aui()->button(
				array(
					'type' => 'a',
					'href' => 'javascript:void(0);',
					'class' => 'btn btn-sm btn-primary',
					'icon' => 'fas fa-bookmark ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ),
					'content' => wp_sprintf( __( "Save to a New %s", 'gd-lists' ), geodir_lists_name_singular() ),
					'no_wrap' => true,
					'onclick' => 'gd_list_create_new_list_dialog(' . (int) $post_id . ');'
				)
			);

			$content = AUI_Component_Input::wrap(
				array(
					'content' => $button,
					'class' => ( $aui_bs5 ? '' : 'form-group ' ) . 'text-center mb-0 pt-3'
				)
			);

			return $content;
		}

		public function new_list_dialog() {
			$content = '';

			return $content;
		}

		public static function login_options( $post_id = '' ) {
			$redirect = '';
			if ( $post_id ) {
				$redirect = get_permalink( $post_id );
			}
			$notifications['login_msg'] = array(
				'type' => 'info',
				'note' => __( 'You must be logged in to use this feature', 'gd-lists' ),
			);
			$content = geodir_notification( $notifications );
			$content .= "<br />";
			$content .= GeoDir_User::login_link( $redirect );

			return $content;
		}

		public static function get_list_save_button($list_id,$post_id){
			if ( geodir_design_style() ) {
				return self::get_list_save_button_aui( $list_id, $post_id );
			}

			$in_list = GeoDir_Lists_Data::has_post($list_id,$post_id);

			$action = $in_list ? 'remove' : 'add';
			$remove_text = __("Remove","gd-lists");
			$save_text = __("Save","gd-lists");
			$current_text = $in_list ? $remove_text : $save_text;
			$current_class = $in_list ? 'gd-list-action-remove' : 'gd-list-action-add';

			$content = '<a href="javascript:void(0);" onclick="gd_list_save_to_list('.absint($list_id).', '.absint($post_id).',\'' . $action . '\',this)" class="gd-list-save-action-link '.$current_class.'" data-text-save="'.$save_text.'" data-text-remove="'.$remove_text.'">'.$current_text.'</a>';

			return $content;
		}

		public static function get_list_save_button_aui($list_id,$post_id){
			$in_list = GeoDir_Lists_Data::has_post($list_id,$post_id);

			$action = $in_list ? 'remove' : 'add';
			$remove_text = __( "Remove", "gd-lists" );
			$save_text = __( "Add", "gd-lists" );
			$current_text = $in_list ? $remove_text : $save_text;
			$current_class = $in_list ? 'btn-outline-danger  gd-list-action-remove' : 'btn-outline-primary  gd-list-action-add';
			$icon_class = $in_list ? 'fas fa-minus-circle' : 'fas fa-plus-circle';

			$content = aui()->button(
				array(
					'type' => 'a',
					'href' => 'javascript:void(0);',
					'class' => 'btn btn-sm gd-list-save-action-link ' . $current_class,
					'icon' => $icon_class,
					'content' => $current_text,
					'no_wrap' => true,
					'onclick' => 'gd_list_save_to_list(' . absint( $list_id ) . ', ' . absint( $post_id ) . ',\'' . $action . '\',this);',
					'extra_attributes' => array(
						'data-text-save' => $save_text,
						'data-text-remove' => $remove_text,
					)
				)
			);

			return $content;
		}

		public static function get_header( $title = '' ) {
			global $aui_bs5;

			ob_start();
			?>
			<div class="modal-header pt-0 mt-0 mb-3 mx-n3 d-flex justify-content-start align-items-center">
				<h5 class="modal-title"><?php echo $title; ?></h5>
				<?php if ( $aui_bs5 ) { ?>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'gd-lists' ); ?>"></button>
				<?php } else { ?>
					<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'gd-lists' ); ?>"><span aria-hidden="true">&times;</span></button>
				<?php } ?>
			</div>
			<?php
			return ob_get_clean();
		}

	}

}