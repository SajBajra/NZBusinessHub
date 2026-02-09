<?php
/**
 * Advertising Admin Functions.
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function adv_admin_params() {
	$params = array(
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'upload_image'      => __( 'Upload Image', 'advertising' ),
		'ajax_nonce'        => wp_create_nonce( 'adv-ajax-nonce' ),
		'txt_dashboard'     => __( 'Dashboard', 'advertising' ),
		'dashboard_href'    => admin_url( 'edit.php?post_type=adv_ad' ), // TODO remove once dashboard page done
        'searching'         => __( 'Searching...', 'advertising' ),
        'search_listings'   => __( 'Search Listings', 'advertising' ),
	);

    return apply_filters( 'adv_admin_params', $params );
}


/**
 * Displays the tabs meta box fields
 */
function adv_show_tabs( $args ) {
    global $aui_bs5;

    //Abort early if there are no tabs
    if ( empty( $args['tabs'] ) ) {
        return NULL;
    }

    //meta_prefix
    $meta_prefix = '_adv_zone_';
    if(! empty( $args['meta_prefix'] ) ) {
        $style = trim( $args['meta_prefix'] );
    }

    //Style of tabs
    $style = 'box';
    if(! empty( $args['style'] ) ) {
        $style = trim( $args['style'] );
    }

    //Tabs wrapper
    $wrapper = '';
    if(! empty( $args['wrapper'] ) ) {
        $wrapper = 'adv-mbtabs-no-wrapper';
    }

    // Start output
    echo "<div class='bsui adv-mbtabs adv-mbtabs-$style $wrapper'>";
    echo '<ul class="adv-mbtab-nav nav nav-tabs nav-fill mt-3">';

    $i = 0;

    $panels = '';

    foreach ( $args['tabs'] as $key => $tab_data ) {
        if ( is_string( $tab_data ) ) {
            $tab_data = array( 'label' => $tab_data );
        }
        
        $tab_data = wp_parse_args( $tab_data, array( 'icon'  => '', 'label' => '' ) );
        
        if ( filter_var( $tab_data['icon'], FILTER_VALIDATE_URL ) ) {
            $icon = '<img src="' . $tab_data['icon'] . '">';
        } else {
            if ( false !== strpos( $tab_data['icon'], 'dashicons' ) ) {
                $tab_data['icon'] .= ' dashicons';
            }

            $tab_data['icon'] = array_filter( array_map( 'trim', explode( ' ', $tab_data['icon'] ) ) );
            $tab_data['icon'] = implode( ' ', array_unique( $tab_data['icon'] ) );

            $icon = $tab_data['icon'] ? ( $aui_bs5 ? '<div class="' . $tab_data['icon'] . '"></div>' : '<i class="' . $tab_data['icon'] . '"></i>' ) : '';
        }

        $class = "adv-mbtab-" . $key;

        printf( '<li class="%s ' . ( $aui_bs5 ? 'nav-item mx-1' : 'm-0' ) . '" data-panel="%s"><a href="#" class="nav-link shadow-none c-pointer">%s%s</a></li>', $class, $key, $icon, $tab_data['label'] );
        ob_start();

        echo '<div class="adv-mbtab-panel adv-mbtab-panel-' . $key . '">';
            do_action( "adv_tab_$key", $meta_prefix);
        echo '</div>';
        
        $panels .= ob_get_clean();

        $i++;
    }
    
        echo '</ul>';

        echo '<div class="adv-mbtab-panels">';
            echo $panels;
        echo '</div>';
    echo '</div>';
}

function adv_ad_tab_content_users( $meta_prefix ) {
    global $aui_bs5, $adv_post;
    
    $post_id = !empty( $adv_post->ID ) ? $adv_post->ID : 0;
    
    $wp_user_roles = apply_filters( 'adv_ad_supported_user_roles', get_editable_roles() );
    
    $user_role_to = get_post_meta( $post_id, "{$meta_prefix}user_role_to", true );
    $user_role_to = !empty( $user_role_to ) ? $user_role_to : 'all';
    $user_roles = get_post_meta( $post_id, "{$meta_prefix}user_roles", true );
    $user_roles = !empty( $user_roles ) ? $user_roles : array();

    if ( $user_role_to == 'show' || $user_role_to == 'hide' ) {
        $style = '';
    } else {
        $style = 'display:none;';
        $user_roles = array();
    }
    
    //adv_log( $user_role_to, 'user_role_to', __FILE__, __LINE__ );
    ?>
    <table class="form-table">
        <tbody>
            <tr class="tr-user_roles">
                <th valign="top" scope="row">
                    <label class="form-label" for="user_role_to"><?php _e( 'User Roles Settings:', 'advertising' ); ?></label>
                </th>
                <td>
                    <select name="user_role_to" id="user_role_to" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> form-select regular-text">
                        <option value="all" <?php selected( $user_role_to == 'all', true ); ?>><?php _e( 'Show to all user roles', 'advertising' ); ?></option>
                        <option data-select="1" value="show" <?php selected( $user_role_to == 'show', true ); ?>><?php _e( 'Only show to selected user roles', 'advertising' ); ?></option>
                        <option data-select="1" value="hide" <?php selected( $user_role_to == 'hide', true ); ?>><?php _e( 'Hide for selected user roles', 'advertising' ); ?></option>
                    </select>
                    <div class="adv-check-boxes" style="<?php echo $style; ?>">
                        <div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
                            <label for="adv_user_roles_guest"><input value="guest" id="adv_user_roles_guest" name="user_roles[]" type="checkbox" <?php checked( in_array( 'guest', $user_roles ), true ); ?> /> <?php _e( 'Guest (logged out)', 'advertising' ); ?></label>
                        </div>
                        <?php foreach ( $wp_user_roles as $user_role => $data ) { ?>
                            <div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
                                <label for="adv_user_roles_<?php echo $user_role; ?>"><input value="<?php echo $user_role; ?>" id="adv_user_roles_<?php echo $user_role; ?>" name="user_roles[]" type="checkbox" <?php checked( in_array( $user_role, $user_roles ), true ); ?> /> <?php _e( $data['name'], 'advertising' ); ?></label>
                            </div>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    // rule info
    echo aui()->alert(array(
        'type' => 'info',
        'content' => __( 'User rules take priority over all other rules.', 'advertising' )
    ));
}
add_action( 'adv_tab_users', 'adv_ad_tab_content_users' );

function adv_ad_tab_content_post_types( $meta_prefix ) {
    global $aui_bs5, $adv_post;
    
    $post_id = !empty( $adv_post->ID ) ? $adv_post->ID : 0;
    
    $wp_post_types = apply_filters( 'adv_ad_supported_post_types', get_post_types( array( 'public' => true ), 'objects' ) );
    
    $post_type_to = get_post_meta( $post_id, "{$meta_prefix}post_type_to", true );
    $post_type_to = !empty( $post_type_to ) ? $post_type_to : 'all';
    $post_types = get_post_meta( $post_id, "{$meta_prefix}post_types", true );
    $post_types = !empty( $post_types ) ? $post_types : array();
    
    if ( $post_type_to == 'show' || $post_type_to == 'hide' ) {
        $style = '';
    } else {
        $style = 'display:none;';
        $post_types = array();
    }
    
    //adv_log( $post_type_to, 'post_type_to', __FILE__, __LINE__ );
    ?>
    <table class="form-table">
        <tbody>
            <tr class="tr-post_types">
                <th valign="top" scope="row">
                    <label class="form-label" for="post_type_to"><?php _e( 'Post Types Settings:', 'advertising' ); ?></label>
                </th>
                <td>
                    <select name="post_type_to" id="post_type_to" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> form-select regular-text">
                        <option value="none" <?php selected( $post_type_to == 'none', true ); ?>><?php _e( 'Hide on all post types', 'advertising' ); ?></option>
                        <option value="all" <?php selected( $post_type_to == 'all', true ); ?>><?php _e( 'Show on all post types', 'advertising' ); ?></option>
                        <option data-select="1" value="show" <?php selected( $post_type_to == 'show', true ); ?>><?php _e( 'Only show on selected post types', 'advertising' ); ?></option>
                        <option data-select="1" value="hide" <?php selected( $post_type_to == 'hide', true ); ?>><?php _e( 'Hide on selected post types', 'advertising' ); ?></option>
                    </select>
                    <div class="adv-check-boxes" style="<?php echo $style; ?>">
                        <?php foreach ( $wp_post_types as $post_type => $data ) { ?>
                            <div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
                                <label for="adv_post_types_<?php echo $post_type; ?>"><input value="<?php echo $post_type; ?>" id="adv_post_types_<?php echo $post_type; ?>" name="post_types[]" type="checkbox" <?php checked( in_array( $post_type, $post_types ), true ); ?> /> <?php _e( $data->labels->name, 'advertising' ); ?> ( <?php echo $post_type; ?> )</label>
                            </div>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    // rule info
    echo aui()->alert(array(
        'type' => 'info',
        'content' => __( 'These rules affect the root archive ie: "/events/" and single posts (posts can be disabled in post settings).', 'advertising' )
    ));
}
add_action( 'adv_tab_post_types', 'adv_ad_tab_content_post_types' );

function adv_ad_tab_content_taxonomies( $meta_prefix ) {
    global $aui_bs5, $adv_post;
    
    $post_id = !empty( $adv_post->ID ) ? $adv_post->ID : 0;
    
    $wp_taxonomies = apply_filters( 'adv_ad_supported_taxonomies', get_taxonomies( array( 'public' => true ), 'objects' ) );
    unset( $wp_taxonomies['post_format'] );
    $taxonomy_to = get_post_meta( $post_id, "{$meta_prefix}taxonomy_to", true );
    $taxonomy_to = !empty( $taxonomy_to ) ? $taxonomy_to : 'all';
    $taxonomies = get_post_meta( $post_id, "{$meta_prefix}taxonomies", true );
    $taxonomies = !empty( $taxonomies ) ? $taxonomies : array();
    
    if ( $taxonomy_to == 'show' || $taxonomy_to == 'hide' ) {
        $style = '';
    } else {
        $style = 'display:none;';
        $taxonomies = array();
    }
    
    //adv_log( $taxonomy_to, 'taxonomy_to', __FILE__, __LINE__ );
    ?>
    <table class="form-table">
        <tbody>
            <tr class="tr-taxonomies">
                <th valign="top" scope="row">
                    <label class="form-label" for="taxonomy_to"><?php _e( 'Taxonomies Settings:', 'advertising' ); ?></label>
                </th>
                <td>
                    <select name="taxonomy_to" id="taxonomy_to" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> form-select regular-text">
                        <option value="none" <?php selected( $taxonomy_to == 'none', true ); ?>><?php _e( 'Hide on all taxonomy archives', 'advertising' ); ?></option>
                        <option value="all" <?php selected( $taxonomy_to == 'all', true ); ?>><?php _e( 'Show on all taxonomy archives', 'advertising' ); ?></option>
                        <option data-select="1" value="show" <?php selected( $taxonomy_to == 'show', true ); ?>><?php _e( 'Only show on selected taxonomy archives', 'advertising' ); ?></option>
                        <option data-select="1" value="hide" <?php selected( $taxonomy_to == 'hide', true ); ?>><?php _e( 'Hide on selected taxonomy archives', 'advertising' ); ?></option>
                    </select>
                    <div class="adv-check-boxes" style="<?php echo $style; ?>">
                        <?php foreach ( $wp_taxonomies as $taxonomy => $data ) { ?>
                            <div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
                                <label for="adv_taxonomies_<?php echo $taxonomy; ?>"><input value="<?php echo $taxonomy; ?>" id="adv_taxonomies_<?php echo $taxonomy; ?>" name="taxonomies[]" type="checkbox" <?php checked( in_array( $taxonomy, $taxonomies ), true ); ?> /> <?php _e( $data->labels->name, 'advertising' ); ?> ( <?php echo $taxonomy; ?> )</label>
                            </div>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    // rule info
    echo aui()->alert(array(
        'type' => 'info',
        'content' => __( 'Taxonomy rules affect tag and category archives such as : "/events/sports/" or "/blog/news/', 'advertising' )
    ));
}
add_action( 'adv_tab_taxonomies', 'adv_ad_tab_content_taxonomies' );

function adv_ad_tab_content_posts( $meta_prefix ) {
    global $aui_bs5, $adv_post;
    
    $post_id = !empty( $adv_post->ID ) ? $adv_post->ID : 0;
    
    $post_to = get_post_meta( $post_id, "{$meta_prefix}post_to", true );
    $post_to = !empty( $post_to ) ? $post_to : 'all';
    $posts = get_post_meta( $post_id, "{$meta_prefix}posts", true );
    
    if ( $post_to == 'show' || $post_to == 'hide' ) {
        $style = '';
    } else {
        $style = 'display:none;';
        $posts = '';
    }
    
    //adv_log( $post_to, 'post_to', __FILE__, __LINE__ );
    ?>
    <table class="form-table">
        <tbody>
            <tr class="tr-posts">
                <th valign="top" scope="row">
                    <label class="form-label" for="post_to"><?php _e( 'Posts Settings:', 'advertising' ); ?></label>
                </th>
                <td>
                    <select name="post_to" id="post_to" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> form-select regular-text">
                        <option value="none" <?php selected( $post_to == 'none', true ); ?>><?php _e( 'Hide for all posts', 'advertising' ); ?></option>
                        <option value="all" <?php selected( $post_to == 'all', true ); ?>><?php _e( 'Show on all posts', 'advertising' ); ?></option>
                        <option data-select="1" value="show" <?php selected( $post_to == 'show', true ); ?>><?php _e( 'Only Show on selected posts', 'advertising' ); ?></option>
                        <option data-select="1" value="hide" <?php selected( $post_to == 'hide', true ); ?>><?php _e( 'Hide on selected posts', 'advertising' ); ?></option>
                    </select>
                    <div class="adv-check-boxes" style="<?php echo $style; ?>">
                        <div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
                            <input type="text" name="posts" id="posts" class="large-text"  placeholder="10,20,99,100" value="<?php echo esc_attr( $posts ); ?>" />
                            <p class="description"><?php _e( 'You can show/hide this ad for certain posts by entering a comma separated string with the Post ID\'s. The format will become something like: 10,20,99,100', 'advertising' ); ?></p>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    // rule info
    echo aui()->alert(array(
        'type' => 'info',
        'content' => __( 'Post Type settings also take affect here, if limited, those settings will also affect the show or hide all settings here.', 'advertising' )
    ));
}
add_action( 'adv_tab_posts', 'adv_ad_tab_content_posts' );

function adv_ad_tab_content_terms( $meta_prefix ) {
    global $aui_bs5, $adv_post;
    
    $post_id = !empty( $adv_post->ID ) ? $adv_post->ID : 0;
    
    $term_to = get_post_meta( $post_id, "{$meta_prefix}term_to", true );
    $term_to = !empty( $term_to ) ? $term_to : 'all';
    $terms = get_post_meta( $post_id, "{$meta_prefix}terms", true );
    
    if ( $term_to == 'show' || $term_to == 'hide' ) {
        $style = '';
    } else {
        $style = 'display:none;';
        $terms = '';
    }
    
    //adv_log( $term_to, 'term_to', __FILE__, __LINE__ );
    ?>
    <table class="form-table">
        <tbody>
            <tr class="tr-terms">
                <th valign="top" scope="row">
                    <label class="form-label" for="term_to"><?php _e( 'Terms Settings:', 'advertising' ); ?></label>
                </th>
                <td>
                    <select name="term_to" id="term_to" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> form-select regular-text">
                        <option value="none" <?php selected( $term_to == 'none', true ); ?>><?php _e( 'Hide for all terms', 'advertising' ); ?></option>
                        <option value="all" <?php selected( $term_to == 'all', true ); ?>><?php _e( 'Show on all terms', 'advertising' ); ?></option>
                        <option data-select="1" value="show" <?php selected( $term_to == 'show', true ); ?>><?php _e( 'Only show on selected terms', 'advertising' ); ?></option>
                        <option data-select="1" value="hide" <?php selected( $term_to == 'hide', true ); ?>><?php _e( 'Hide on selected terms', 'advertising' ); ?></option>
                    </select>
                    <div class="adv-check-boxes" style="<?php echo $style; ?>">
                        <div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
                            <input type="text" name="terms" id="terms" class="large-text"  placeholder="11,20,99,120" value="<?php echo esc_attr( $terms ); ?>" />
                            <p class="description"><?php _e( 'You can show/hide this ad for certain terms (categories, tags & custom taxonomies) by entering a comma separated string with the Term ID\'s. The format will become something like: 11,20,99,120', 'advertising' ); ?></p>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    // rule info
    echo aui()->alert(array(
        'type' => 'info',
        'content' => __( 'Taxonomy settings also take affect here, if limited, those settings will also affect the show or hide all settings here.', 'advertising' )
    ));
}
add_action( 'adv_tab_terms', 'adv_ad_tab_content_terms' );