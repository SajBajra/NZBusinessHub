<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'nz');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'onAdy*oH-grOQ+taNaW($u%V5cQ1if_Fml9*rYybF=x-Z^~rn|Do03F-Li<nmqju');
define('SECURE_AUTH_KEY',  'O*~|3`<-m(,<}|a(>?[<K {LL%BK.{-@6|!(.Fus7Qz_D0.V| Os}3VUwU|1uV:7');
define('LOGGED_IN_KEY',    '-4G61G*DHtj)QA`~]12aJ}Ejy:lDRy[ CGk3^>G0WHHaC+j7uTW6}|/C?~M>|$/_');
define('NONCE_KEY',        '}vhH-4^~=gsANfZa~L+WhNudSds-3mBpFYg:$+$|r-1 >6L+t|M#6x/^FM,zSYzW');
define('AUTH_SALT',        '_BpU{0qHcq:grb_/rS5Xf($>CZVT<)C-rN-et7(jxnj g5i}SQj)5;GX8Q<}KQQ]');
define('SECURE_AUTH_SALT', 'EhgqU_dJhn ?=ItSgE~l}m Dn:||,g%K/),{)6!AQ3lPG}Qtxc28phS?ghdgFo(h');
define('LOGGED_IN_SALT',   '5s--ijKyag*W8uqUDeydt0zU`r*6]mEO@ifKjH47t,HQqOy5JN} 3C!wrT[r/4]R');
define('NONCE_SALT',       '~I{:W+?vntGi10xR=nRrk=9e3}6JXx+SS#_PI{%U[zfWnZ]#kW4u$<VPla3-%K@v');


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = "wp_qf_";

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

define( 'WP_POST_REVISIONS', 5 );

/** Disable the Plugin and Theme Editor **/
define( 'DISALLOW_FILE_EDIT', true );
define('WP_HOME', 'http://localhost/nz');
define('WP_SITEURL', 'http://localhost/nz');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
