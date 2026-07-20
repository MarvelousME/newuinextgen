<?php
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = 'localhost';
require '/var/www/html/wp-load.php';
echo "loaded\n";
echo class_exists( 'NGC_Business_Profile' ) ? "biz_ok\n" : "biz_no\n";
echo class_exists( 'NGC_Roles' ) ? "roles_ok\n" : "roles_no\n";
echo 'theme=' . wp_get_theme()->get( 'Name' ) . "\n";
