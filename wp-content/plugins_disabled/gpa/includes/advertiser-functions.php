<?php


/**
 * Retrieves an advertiser's display name
 */
function adv_advertiser_name( $user_id = 0 ) {
    if ( empty( $user_id ) ) {
        return NULL;
    }
    
    $user = get_user_by( 'id', $user_id );
    
    if ( !empty( $user->display_name ) ) {
        return $user->display_name;
    } else if ( !empty( $user->user_login ) ) {
        return $user->user_login;
    }
    
    return NULL;
}

/**
 * Retrieves an advertiser's email
 */
function adv_advertiser_email( $user_id = 0 ) {
    if ( empty( $user_id ) ) {
        return NULL;
    }
    
    $user = get_user_by( 'id', $user_id );
    
    if ( !empty( $user->user_email ) ) {
        return $user->user_email;
    }
    
    return NULL;
}

/**
 * Retrieve the current user's user roles
 */
function adv_get_user_roles( $user_id = 0 ) {
    global $current_user;
            
    // user roles
    if ( !empty( $current_user ) ) {
        $roles = !empty( $current_user->roles ) ? $current_user->roles : array( 'guest' );
    } else {
        $roles = array( 'guest' );
    }

    return $roles;
}