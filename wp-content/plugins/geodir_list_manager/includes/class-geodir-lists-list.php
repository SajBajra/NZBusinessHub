<?php

// Check GeoDir_Lists_List class exists or not.
if ( ! class_exists( 'GeoDir_Lists_List' ) ) {

	/**
	 * GeoDir_Lists_Lists Class for the list output.
	 *
	 * @since 2.0.0
	 *
	 * Class GeoDir_Lists_List
	 */
	class GeoDir_Lists_List{

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GeoDir_Lists_List constructor.
		 */
		public function __construct() {
			add_filter( 'the_content', array( $this, 'list_post_content' ), 10, 2 );

			add_action( 'geodir_lists_loop_actions', array( $this, 'loop_actions' ), 10, 1 );
			add_filter( 'geodir_get_page_id', array( $this, 'get_page_id' ), 1, 4 );

			// author page permalinks
			add_filter( 'init', array( $this, 'author_rewrite_rules' ) );
			add_filter( 'geodir_list_overwrite_single_template_content', array( $this, 'overwrite_single_template_content' ), 10, 3 );
			add_filter( 'comments_open', array( $this, 'comments_open' ), 20, 2 );

			add_action( 'wp_head', array( $this, 'set_hook_open' ), -99999, 3 );
			add_action( 'wp_head', array( $this, 'set_hook_close' ), 99999, 3 );
			add_action( 'uwp_profile_posts_loop_wrap_start', array( $this, 'posts_loop_wrap_start' ), 20, 2 );
			add_filter( 'uwp_profile_posts_loop_no_items', array( $this, 'posts_loop_no_items' ), 20, 2 );
		}

		public function set_hook_open( $arg1 = '', $arg2 = '', $arg3 = '' ) {
			global $geodir_list_hooks;

			if ( ! empty( $geodir_list_hooks ) ) {
				$geodir_list_hooks = array();
			}

			$geodir_list_hooks[ current_filter() ] = true;
		}

		public function set_hook_close( $arg1 = '', $arg2 = '', $arg3 = '' ) {
			global $geodir_list_hooks;

			if ( ! empty( $geodir_list_hooks ) && ( $current_filter = current_filter() ) ) {
				if ( isset( $geodir_list_hooks[ $current_filter ] ) ) {
					unset( $geodir_list_hooks[ $current_filter ] );
				}
			}
		}

		/**
		 * Add author page pretty urls.
		 *
		 * @since 2.0.0
		 *
		 * @param array $rules Rules.
		 *
		 * @return array $rules.
		 */
		public function author_rewrite_rules( ){
			global $wp_rewrite;

			$post_type = 'gd_list';
			$pt = GeoDir_Lists_CPT::post_type_args();
			$cpt_slug = $pt['rewrite']['slug'];

			// main rule
			$regex = "^".$wp_rewrite->author_base."/([^/]+)/$cpt_slug/?$";
			$redirect = 'index.php?author_name=$matches[1]&post_type='.$post_type;
			add_rewrite_rule($regex,$redirect,'top');

			// paged rule
			$regex = "^".$wp_rewrite->author_base."/([^/]+)/$cpt_slug/page/?([0-9]{1,})/?$";
			$redirect = 'index.php?author_name=$matches[1]&post_type='.$post_type.'&paged=$matches[2]';
			add_rewrite_rule($regex,$redirect,'top');
		}

		public function loop_actions( $args = array() ) {
			global $post;

			if ( geodir_design_style() ) {
				$this->loop_actions_aui( $args );
				return;
			}

			$user_id = get_current_user_id();
			$is_author = $user_id && ! empty( $post->post_author ) && $post->post_author == $user_id ? true : false;
			$real_post_status = geodir_lists_get_real_post_status( $post->ID );

			if ( $is_author && $real_post_status && $real_post_status == 'private' ) {
				?>
				<div class="clearfix gd-lists-loop-info">
					<span class="gd-lists-list-private">
					<?php echo '<i class="fas fa-user-secret" title="" aria-hidden="true"></i> '.sprintf( __( "Non-public %s (you can still share a direct link with your friends)", 'gd-lists' ), geodir_lists_name_singular() );?>
					</span>
				</div>
			<?php }

			if ( $is_author ) {
			?>
			<div class="clearfix gd-lists-loop-author-actions">
				<span class="gd-lists-author-action-edit">
					<a href="javascript:void(0);" onclick="gd_list_edit_list_dialog(<?php echo absint($post->ID);?>)"><?php echo sprintf( __( "%s Edit %s", 'gd-lists' ), '<i class="fas fa-edit" aria-hidden="true"></i>', geodir_lists_name_singular() ) ?></a>
				</span>
				<span class="gd-lists-author-action-delete">
					<a href="javascript:void(0);" onclick="gd_list_delete_list(<?php echo absint($post->ID);?>)"><?php echo sprintf( __( "%s Delete %s", 'gd-lists' ), '<i class="fas fa-trash-alt" aria-hidden="true"></i>', geodir_lists_name_singular() ) ?></a>
				</span>
			</div>
			<?php
			}
		}

		public function loop_actions_aui( $args = array() ) {
			global $aui_bs5, $post;

			$user_id = get_current_user_id();
			$is_author = $user_id && ! empty( $post->post_author ) && $post->post_author == $user_id ? true : false;
			$real_post_status = geodir_lists_get_real_post_status( $post->ID );

			if ( $is_author ) {
				$wrap_class = 'gd-lists-loop-author-actions ' . geodir_build_aui_class( $args );
				$btn_class = '';

				if ( $args['alignment'] != '' ) {
					$wrap_class .= ' text-' . sanitize_html_class( $args['alignment'] );
					if ( $args['alignment'] == 'block' ) {
						$btn_class .= 'd-block';
					}
				} else {
					$wrap_class .= ' clear-both';
				}

				$btn_class = geodir_build_aui_class( array( 'mt' => $args['btn_mt'], 'mr' => $args['btn_mr'], 'mb' => $args['btn_mb'], 'ml' => $args['btn_ml'] ) );

				if ( $args['alignment'] ) {
					$btn_class .= ' btn-' . sanitize_html_class( $args['alignment'] );
				}
				
				// Button Size
				if ( ! empty( $args['size'] ) ) {
					$btn_class .= ' btn-' . sanitize_html_class( $args['size'] );
				}

				echo '<div class="' . $wrap_class . '">';

				echo aui()->button(
					array(
						'type' => 'a',
						'href' => 'javascript:void(0);',
						'class' => 'btn btn-outline-primary ' . $btn_class . ' gd-lists-author-action-edit',
						'icon' => 'fas fa-edit ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ),
						'content' => wp_sprintf( __( "Edit %s", 'gd-lists' ), geodir_lists_name_singular() ),
						'no_wrap' => true,
						'onclick' => 'gd_list_edit_list_dialog(' . absint( $post->ID ) .');'
					)
				);

				echo aui()->button(
					array(
						'type' => 'a',
						'href' => 'javascript:void(0);',
						'class' => 'btn btn-outline-danger ' . $btn_class . ' gd-lists-author-action-delete',
						'icon' => 'fas fa-trash-alt ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ),
						'content' => wp_sprintf( __( "Delete %s", 'gd-lists' ), geodir_lists_name_singular() ),
						'no_wrap' => true,
						'onclick' => 'gd_list_delete_list(' . (int) $post->ID . ');'
					)
				);

				echo '</div>';
			}

			if ( $is_author && $real_post_status && $real_post_status == 'private' ) {
			?>
			<div class="alert alert-info gd-lists-loop-info mb-4" role="alert">
				<span class="gd-lists-list-private"><?php echo '<i class="fas fa-user-secret  ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ) . '" aria-hidden="true"></i>' . wp_sprintf( __( "Non-public %s (you can still share a direct link with your friends)", 'gd-lists' ), geodir_lists_name_singular() ); ?></span>
			</div>
			<?php 
			}
		}

		/**
		 * Method are use to added links and update and listing content.
		 *
		 * @since 2.0.0
		 *
		 * @param string $post_content Get selected post Post_content.
		 * @param int $post_ID Post ID.
		 * @return string $post_content
		 */
		public function list_post_content( $post_content, $post_ID = 0 ) {
			global $post, $geodir_list_hooks, $geodir_list_post_content;

			if ( empty( $post ) || ( ! empty( $geodir_list_hooks ) && ! empty( $geodir_list_hooks['wp_head'] ) ) ) {
				return $post_content;
			}

			$post_type = ! empty( $post->post_type ) ? $post->post_type :'';

			if ( ! ( 'gd_list' === $post_type && is_single() && is_main_query() && in_the_loop() ) ) {
				return $post_content;
			}

			if ( empty( $geodir_list_post_content ) ) {
				$geodir_list_post_content = array();
			}

			$geodir_list_post_content[ $post->ID ] = $post_content;

			$elementor_params = '';
			$layout = 2;

			$page_id = geodir_details_page_id( $post_type );

			if ( ! empty( $page_id ) && $page_id > 0 ) {
				remove_filter( 'the_content', array( $this, 'list_post_content' ), 10, 2 );

				$content = get_post_field( 'post_content', $page_id );

				$overwrite_content = apply_filters( 'geodir_list_overwrite_single_template_content', '', $content, $page_id );

				if ( $overwrite_content ) {
					$content = $overwrite_content;
				} else {
					// Run the shortcodes on the content.
					$content = do_shortcode( $content );

					// Run block content if its available.
					if ( function_exists( 'do_blocks' ) ) {
						$content = do_blocks( $content );
					}
				}

				$post_content = $content;
			} else {
				// Elementor Skin
				if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
					$post_type_obj = geodir_post_type_object( $post_type );

					if ( ! empty( $post_type_obj->elementor_skin ) ) {
						$elementor_params .= ' skin_id=' . absint( $post_type_obj->elementor_skin );

						if ( ! empty( $post_type_obj->elementor_skin_column_gap ) ) {
							$elementor_params .= ' skin_column_gap=' . absint( $post_type_obj->elementor_skin_column_gap );
						}

						if ( ! empty( $post_type_obj->elementor_skin_columns ) ) {
							$layout = absint( $post_type_obj->elementor_skin_columns );
						}

						if ( ! empty( $post_type_obj->elementor_skin_row_gap ) ) {
							$elementor_params .= ' skin_row_gap=' . absint( $post_type_obj->elementor_skin_row_gap );
						}
					}
				}

				$post_content = "[gd_list_loop_actions]" . $post_content;
				$post_content .= "[gd_list_loop layout=" . $layout . " mt=4 mb=4" . $elementor_params . "]";
			}

			unset( $geodir_list_post_content[ $post->ID ] );

			return $post_content;
		}

		public function get_page_id( $page_id, $page, $post_type = '', $translated = true ) {
			if ( $post_type == 'gd_list' && in_array( $page, array( 'details', 'archive_item' ) ) ) {
				$post_type_obj = geodir_post_type_object( $post_type );

				if ( empty( $post_type_obj ) ) {
					return $page_id;
				}

				if ( $page == 'details' ) {
					$page_id = ! empty( $post_type_obj->page_details ) ? absint( $post_type_obj->page_details ) : (int) geodir_get_option( 'list_page_single' );
				} else if ( $page == 'archive_item' ) {
					$page_id = ! empty( $post_type_obj->page_archive_item ) ? absint( $post_type_obj->page_archive_item ) : 0;
				}
			}

			return $page_id;
		}

		/**
		 * Overwrite single template content for the elementor builder page.
		 *
		 * @since 2.3.2
		 *
		 * @param string $content          Overwrite content. Default empty.
		 * @param string $original_content Single template content.
		 * @param string $page_id          Single template ID.
		 * @return string Filtered content.
		 */
		public function overwrite_single_template_content( $content, $original_content, $page_id ) {
			if ( $page_id && defined( 'ELEMENTOR_VERSION' ) && class_exists( 'GeoDir_Elementor' ) && GeoDir_Elementor::is_elementor( $page_id ) ) {
				$content = \Elementor\Plugin::$instance->frontend->get_builder_content( $page_id, true );

				if ( ! $content ) {
					// Prevent showing default content when assigned blank template.
					$content = '<!-- GD SINGLE LIST TEMPLATE EMPTY ELEMENTOR CONTENT -->';
				}

				// Prevent showing comment template.
				global $gd_is_comment_template_set;
				$gd_is_comment_template_set = true;
			}

			return $content;
		}

		public function comments_open( $open, $post_id ) {
			if ( $open && ! empty( $post_id ) && get_post_type( (int) $post_id ) == 'gd_list' ) {
				$open = false;
			}

			return $open;
		}

		public function posts_loop_wrap_start( $args, $found_posts ) {
			global $post, $geodir_list_post;

			if ( ! empty( $args['template_args']['list_data']['id'] ) && get_query_var('uwp_tab') == 'lists' ) {
				$backup_post = $post;

				$post = get_post( (int) $args['template_args']['list_data']['id'] );
				$geodir_list_post = $post;

				$shortcode = '[gd_list_loop_actions mr=1 mb=3][gd_list_single_description mb=4 css_class="aaaaaaaaaaaaaa"]';
				$shortcode = apply_filters( 'geodir_uwp_profile_lists_loop_shortcode', $shortcode, $args );

				if ( $shortcode ) {
					echo do_shortcode( $shortcode ) . '<style>.geodir-list-loop-actions-container{display:none}</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				$post = $backup_post;
			}

			$geodir_list_post = null;
		}

		public function posts_loop_no_items( $no_items_message, $args ) {
			$uwp_tab = get_query_var('uwp_tab');

			if ( $uwp_tab == 'lists' ) {
				$no_items_message = '<span class="alert alert-info d-block uwp-no-items">' . esc_html( __( 'No items found in this list.', 'gd-lists' ) ) . '</span>';
			}

			return $no_items_message;
		}
	}

	new GeoDir_Lists_List();
}