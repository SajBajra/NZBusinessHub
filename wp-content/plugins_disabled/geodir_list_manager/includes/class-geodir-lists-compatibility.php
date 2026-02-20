<?php
/**
 * Check GeoDir_Lists_Compatibility class exists or not.
 */
if( ! class_exists( 'GeoDir_Lists_Compatibility' ) ) {
	/**
	 * Lists Compatibility class.
	 *
	 * @class GeoDir_Lists_Compatibility
	 *
	 * @since 2.0.0
	 */
	class GeoDir_Lists_Compatibility {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GeoDir_Lists_Compatibility constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ), 5 );
			add_filter( 'geodir_dashboard_links' ,array( __CLASS__, 'geodirectory_dashboard' ), 10 );
		}

		public function init() {
			// Used in UWP
			if ( ! defined( 'MY_LISTS_TEXT' ) ) {
				define( 'MY_LISTS_TEXT', wp_sprintf( __( "My %s", "gd-lists" ), geodir_lists_name_plural() ) );
			}
		}

		public static function geodirectory_dashboard( $dashboard_links = '', $output_type = 'select' ) {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return $dashboard_links;
			}

			// My Lists in Dashboard
			$user_lists = GeoDir_Lists_Data::get_user_lists( $user_id );

			$pt = GeoDir_Lists_CPT::post_type_args();
			$cpt_slug = $pt['rewrite']['slug'];

			$options = array();
			$options[ trailingslashit( get_author_posts_url( $user_id ) ).trailingslashit($cpt_slug) ] = wp_sprintf( __( "All My %s", 'gd-lists' ), geodir_lists_name_plural() );

			if ( ! empty( $user_lists ) ) {
				$lists_links = $output_type == 'select' ? '' : array();
				foreach ( $user_lists as $list) {
					$ID           = $list->ID;
					$name           = $list->post_title;
					$list_link = get_permalink($ID);

					$selected = '';

					/**
					 * Filter lists list link.
					 *
					 * @since 1.0.0
					 *
					 * @param string $post_type_link Favorite listing link.
					 * @param string $key Favorite listing array key.
					 * @param int $current_user ->ID Current user ID.
					 */
					$list_link = apply_filters( 'geodir_dashboard_link_lists_list', $list_link, $ID, $user_id );

					if ( $output_type == 'select' ) {
						$lists_links .= '<option ' . $selected . ' value="' . $list_link . '">' . __( geodir_utf8_ucfirst( $name ),'gd-lists' ) . '</option>';
					} elseif ( $output_type == 'link' ) {
						$lists_links[] = '<a href="' . $list_link . '">' . __( geodir_utf8_ucfirst( $name ),'gd-lists' ) . '</a>';
					}elseif($output_type == 'array'){
						$lists_links[$ID] = array('url' => $list_link,'text'=>__( geodir_utf8_ucfirst( $name ),'gd-lists' ));
					}
					$options[ $list_link ] = __( $name );
				}

				if ( $lists_links != '' ) {
					if ( $output_type == 'select' ) {
						ob_start();
						if ( geodir_design_style() ) {
							echo "<li class='list-unstyled'>";
							echo  aui()->select( array(
								'id'               => "geodir_my_lists",
								'name'             => "geodir_my_lists",
								'class'             => 'mw-100',
								'placeholder'      => wp_sprintf( esc_attr__( "My %s", 'gd-lists' ), geodir_lists_name_plural() ),
								'value'            => '',
								'options'          => $options,
								'extra_attributes' => array(
									'option-autoredirect' => "1",
									'onchange' => 'if(jQuery(this).val()){window.location = jQuery(this).val();}'
								)
							) );
							echo "</li>";
						} else {
						?>
						<li>
							<select id="geodir_my_lists" class="geodir-select" onchange="window.location.href = jQuery(this).val();"
									option-autoredirect="1" name="geodir_my_favourites" option-ajaxchosen="false"
									data-placeholder="<?php echo sprintf( __( "My %s", 'gd-lists' ), geodir_lists_name_plural() ); ?>"
									aria-label="<?php echo sprintf( __( "My %s", 'gd-lists' ), geodir_lists_name_plural() ); ?>">
								<option value="" disabled="disabled" selected="selected"
										style='display:none;'><?php echo sprintf( __( "My %s", 'gd-lists' ), geodir_lists_name_plural() ); ?></option>
								<option value="<?php echo trailingslashit( get_author_posts_url( $user_id ) ).trailingslashit($cpt_slug); ?>" ><?php echo sprintf( __( "All My %s", 'gd-lists' ), geodir_lists_name_plural() ); ?></option>
								<?php echo $lists_links; ?>
							</select>
						</li>
						<?php
						}
						$dashboard_links .= ob_get_clean();
					}elseif($output_type=='array'){
						return $lists_links;
					}
				}
			}

			return $dashboard_links;
		}

	}

	new GeoDir_Lists_Compatibility();
}
