<?php

// Check GD_List_BuddyPress class exists or not.
if( ! class_exists( 'GD_List_BuddyPress' ) ) {

    /**
     * GD_List_BuddyPress Class for buddypress actions.
     *
     * @since 2.0.0
     *
     * Class GD_List_BuddyPress
     */
    class GD_List_BuddyPress{

        /**
         * Constructor.
         *
         * @since 2.0.0
         *
         * GD_List_BuddyPress constructor.
         */
        public function __construct() {

            add_action('bp_setup_nav', array( $this,'bp_add_new_list_tab'));

        }

        /**
         * Method are used for add new list tab on member page.
         *
         * @since 2.0.0
         */
        public function bp_add_new_list_tab() {

            global $bp;

            $user_id = 0;

            if ( bp_is_user() ) {
                $user_id = $bp->displayed_user->id;
            }

            if ( 0 == $user_id ) {
                return;
            }

            $screen_function = apply_filters('gd_list_bp_screen_function', array( $this,'list_tab_screen_fn'));

            bp_core_new_nav_item(
                array(
                    'name' => geodir_lists_name_plural(),
                    'slug' => geodir_lists_slug(),
                    'position' => 21,
                    'show_for_displayed_user' => true,
                    'screen_function' => $screen_function,
                    'item_css_id' => 'lists',
                    'default_subnav_slug' => 'public'
                )
            );

        }

        /**
         * This method are used to display list tab screen function.
         *
         * @since 2.0.0
         */
        public function list_tab_screen_fn() {

            // add action for display list tab screen content.
            add_action('bp_template_content', array( $this,'list_tab_content'));
            bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));

        }

        /**
         * Display list tab contents.
         *
         * @since 2.0.0
         */
        public function list_tab_content() {
            global $bp;

            $user_id = $bp->displayed_user->id;

            if (!$user_id) {
                return;
            }

            // Display post lists using displayed user id.
            $this->current_user_lists();

        }

        /**
         * Get current displayed user id post listings.
         *
         * @since 2.0.0
         */
        public function current_user_lists() {

            ob_start();

            $displayed_user_id = bp_displayed_user_id();

            $get_display_name = bp_core_get_user_displayname( $displayed_user_id );

            $member_page_url = $this->bp_displayed_user_link( $displayed_user_id );

            $get_list_results = GeoDir_Lists_Data::get_user_lists($displayed_user_id);


            if ( get_current_user_id() == $displayed_user_id) {
                $title = sprintf( __( "My %s","gd-lists" ), geodir_lists_name_plural() );
            } else {
                $title = wp_sprintf( __( "%s's %s", 'gd-lists' ), get_the_author_meta( 'display_name', $displayed_user_id ),geodir_lists_name_plural()  );
            }

            ?>
            <div id="gd_bp_tab_lists">
                <div class="gd-bp-tab-list-title"><h2><?php echo $title; ?></h2></div>
                <div class="gd-bp-tab-list-content">
                    <ul class="gd-list-item-wrap-ul">
                        <?php if( !empty( $get_list_results ) && '' != $get_list_results ) {
                            foreach ( $get_list_results as $list_key => $list_value ) {
                                $maybe_private = '';
                                if($list_value->post_status=='private'){
                                    $maybe_private = '<i class="fas fa-user-secret" title="'.sprintf( __( "Non-public %s (you can still share a direct link with your friends)", 'gd-lists' ), geodir_lists_name_singular() ).'" aria-hidden="true"></i> ';
                                }
                                $list_content = wp_trim_excerpt($list_value->post_content);
                                ?>
                                <li class="gd-list-item-wrap">
                                    <h3><?php echo $maybe_private;?><a href="<?php echo esc_url(get_permalink($list_value->ID));?>"><?php echo !empty( $list_value->post_title ) ? __($list_value->post_title,'gd-lists') : '';?></a></h3>
                                    <div class="list_description"><?php echo $list_content; ?></div>
                                </li>
                            <?php }
                        } else {
                            ?>
                            <li class="gd-list-item-wrap">
                                <p><?php _e( 'No list(s) Found!', 'gd-lists' ); ?></p>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <?php

            echo ob_get_clean();
        }
        
        /**
         * Get displayed user profile page link using user ID.
         *
         * @since 2.0.0
         *
         * @param int $displayed_user_id Get displayed user ID.
         * @return string $user_link
         */
        public function bp_displayed_user_link($displayed_user_id) {

            $get_bp_page = get_option('bp-pages');

            $user_link = '';

            if( !empty( $get_bp_page['members'] ) && $get_bp_page['members'] > 0 ) {

                $user_link = get_the_permalink($get_bp_page['members']).bp_core_get_username($displayed_user_id);

            }

            return apply_filters('list_bp_displayed_user_link',esc_url( $user_link ));
        }


    }

    new GD_List_BuddyPress();
}