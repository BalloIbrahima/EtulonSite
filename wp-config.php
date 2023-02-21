<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'etulon_wordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'L&4ImQ.eodJ<^K@KkDC]PkO!N%MP?k7JVCp|[HY06)Hexa^,D3c~cpzV[i`; _a ' );
define( 'SECURE_AUTH_KEY',  'hy4?1WH{jgRy5[u~ae .Dc;6Up,) #F+d}nUi3g|;u) eTX`mqWUe<PMKh9Ur@:`' );
define( 'LOGGED_IN_KEY',    'd*#)b?p>ci|VEIiXtqE#qzvziN&)Qfg!`4%*.;.Id/b;|;`c$&ZJOyFE#f +!N5r' );
define( 'NONCE_KEY',        'iS~a0>Q$x!v`QwW0vg&aFw.wQ7:oF!nlnL[4hbS.}HuSdH$0)P4M`aYm.U*0a[E ' );
define( 'AUTH_SALT',        'BYY|-!k%iI.__Yu6U?pSkD^l!?Fj]oJ)~Tqqq(kvc8y$K}v.oW:(x%[NtW;Je+y.' );
define( 'SECURE_AUTH_SALT', '}dKjo)`$ON4OERARr2F.PQ2}npJC,.{(zifZ;vu8idya=k`+.2guJ!)W aUU<!X4' );
define( 'LOGGED_IN_SALT',   '2sN#{.ISQiwahTrm/-aDj~/~W_A(1.Pgi()E?|cS4}QRsotF&.:[zM7(LVhQ^7f2' );
define( 'NONCE_SALT',       'D5#&]|)P-EKasY}*{PlLa`1hJrq;PdLdKf3+6N_u4z14Ra8!hTl;rH8DVuUu.l{:' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'et_';

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
