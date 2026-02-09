<?php //phpcs:ignore -- \r\n notice.
/**
 * Plugin Name: Hosting Object Cache
 * Description: External object cache. A highly-tuned Memcached object cache for blazing fast dynamic page loads.
 * Version:     d380bd4
*/

// only include our code if in our hosting environment (be nice to people migrating away from WPMU DEV).
if ( isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
	require_once '/var/web/plugins/object-cache/wp-object-cache.php';
}
