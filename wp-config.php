<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', 'woocommerce');

/** Tu nombre de usuario de MySQL */
define('DB_USER', 'root');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', '');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', 'localhost');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8mb4');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'IX5x+5s.kw>|L#Eg@/FalQ@7?:JWwS bk. y|UWStxWvf~)9c7)lxf`L;bQ}@FRy');
define('SECURE_AUTH_KEY', 'IN#lP}V/FO&V4bA&xez (sf]2ZY^*|nU1rU>~a9NSno^ `<5@{+ht#.[XUOXO,by');
define('LOGGED_IN_KEY', 'SrHkN@=vYHCUvI;=.nKY++F_qOs;#Z$Bi9_69I^?REx#4<q90W1N,1#B4]Mt1Ij[');
define('NONCE_KEY', 'S}mEo~t+r78yS:Sx;!m$8-K(_<iG>0Xc.M]xMULJA-:!5UOY+lg8h-wOk)9Tzy7-');
define('AUTH_SALT', 'f~mB>D18i:pT)Cl8x7=!N#_yWr>[%-<$k:kob{at]Gy.(<<B5|N V!WyeIdLH,w9');
define('SECURE_AUTH_SALT', '%k2{ED46>p-EA*cOoTw9* $!Va8.8Sl/:GfWFjfis7X:+<-x8!4/b1@WE5qPoDN+');
define('LOGGED_IN_SALT', '+(L!ED54$unb*j/X+M,4Vk)]NKLFm1(6S*meiJu+v~+Wes-z1&9K:@FE7U4Q}$*n');
define('NONCE_SALT', '^ru8o(,~IDP8y).A.&YO%G#_5F_Q[)b6%?t+p&KNZ0cr}OH2HS!|?bNo)<ehOY2j');

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix  = 'wp_';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

