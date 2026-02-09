<?php //phpcs:ignore -- \r\n notice.
/**
 * Plugin Name: Hosting
 * Description: Provides functions and features for compatibility with the managed WordPress hosting environment. Please don't delete, we'll just add it back ;-)
 * Version:     1.0
*/

// only include our code if in our hosting environment (be nice to people migrating away from WPMU DEV).
if ( isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
	require_once '/var/web/plugins/mu-plugins/wp-hosting.php';
}
