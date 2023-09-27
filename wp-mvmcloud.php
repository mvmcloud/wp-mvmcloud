<?php
/*
Plugin Name: WP-MVMCloud Integration

Plugin URI: https://github.com/mvmcloud

Description: Adds MVMCloud Analytics statistics to your WordPress dashboard and is also able to add the MVMCloud Analytics Tracking Code to your website/blog.

Version: 2.0.28
Author: MVMCloud Analytics
Author URI: https://www.mvmcloud.net
Text Domain: wp-mvmcloud
Domain Path: /languages
License: GPL3

******************************************************************************************
	Copyright (C) 2014-Today MVMCloud (email: contact@mvmcloud.net)

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************************/
if (! function_exists ( 'add_action' )) {
	header ( 'Status: 403 Forbidden' );
	header ( 'HTTP/1.1 403 Forbidden' );
	exit ();
}

if (! defined ( 'NAMESPACE_SEPARATOR' ))
	define ( 'NAMESPACE_SEPARATOR', '\\' );

/**
 * Define WP-MVMCloud autoloader
 *
 * @param string $class
 *        	class name
 */
function wp_mvmcloud_autoloader($class) {
	if (substr ( $class, 0, 12 ) == 'WP_MVMCloud' . NAMESPACE_SEPARATOR) {
		$class = str_replace ( '.', '', str_replace ( NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, substr ( $class, 12 ) ) );
		require_once ('classes' . DIRECTORY_SEPARATOR . 'WP_MVMCloud' . DIRECTORY_SEPARATOR . $class . '.php');
	}
}

/**
 * Show notice about outdated PHP version
 */
function wp_mvmcloud_phperror() {
	echo '<div class="error"><p>';
	printf ( __ ( 'WP-MVMCloud requires at least PHP 5.3. You are using the deprecated version %s. Please update PHP to use WP-MVMCloud.', 'wp-mvmcloud' ), PHP_VERSION );
	echo '</p></div>';
}

function wp_mvmcloud_load_textdomain() {
    load_plugin_textdomain( 'wp-mvmcloud', false, plugin_basename( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR );
}
add_action( 'plugins_loaded', 'wp_mvmcloud_load_textdomain' );

if (version_compare ( PHP_VERSION, '5.3.0', '<' ))
	add_action ( 'admin_notices', 'wp_mvmcloud_phperror' );
else {
	define ( 'WP_MVMCLOUD_PATH', dirname ( __FILE__ ) . DIRECTORY_SEPARATOR );
	require_once (WP_MVMCLOUD_PATH . 'config.php');
	require_once (WP_MVMCLOUD_PATH . 'classes' . DIRECTORY_SEPARATOR . 'WP_Mvmcloud.php');
	spl_autoload_register ( 'wp_mvmcloud_autoloader' );
	$GLOBALS ['wp-mvmcloud_debug'] = false;
	if (class_exists ( 'WP_Mvmcloud' ))
		add_action( 'init', 'wp_mvmcloud_loader' );
}

function wp_mvmcloud_loader() {
	$GLOBALS ['wp-mvmcloud'] = new WP_Mvmcloud ();
}
