<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'Automation' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

if ( !defined('WP_CLI') ) {
    define( 'WP_SITEURL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] );
    define( 'WP_HOME',    $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] );
}



/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'fCLSH11BTzXt9xOj64qmy9mW0QJLyySom527JVbznaQtylFo8DLzOfp5kAnVLIeL' );
define( 'SECURE_AUTH_KEY',  'RHZpKOUV6qq3rxb8EAxP9mQrz7w5jxx7ALr6lBXYreTophVo3heJwCgQQffRFtVq' );
define( 'LOGGED_IN_KEY',    '1NkjYvciGN2sj3FLxI2nuoVAmnHgRcFruTHSxBHDWbCRBPpuZpPwHOjZx1aTotDG' );
define( 'NONCE_KEY',        'ehFfXr4U7L5GqoObrjoUffPDLSc2EifQ8078rPC1JFq6s03yEjfoW2NuxX9uaaBv' );
define( 'AUTH_SALT',        '4XzgLXWMPc1awTVutDzD7L6zZCxgyL0On6REdP3NvvsTSyh59ae6kwkomi3O2rx0' );
define( 'SECURE_AUTH_SALT', '7sRLm1J8VdHO2WFaGNBXYnstoikyCDIRwr4NKqNrhQ9HeiEpaZL76yFLSFiGGNcc' );
define( 'LOGGED_IN_SALT',   'LxYGV1oF6nfnUm5jT6IGNtBv8IKqLzEpVujfbNqv8keVUky0XMgC6QNekFWl5BzP' );
define( 'NONCE_SALT',       'htofvLPaZfPXXGkmQm4xRcNg2sgoDtelh8y9Dt6XW2Jiwy6i4kvLwjtWxwt52N2i' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
