<?php
$wpRootDir = isset($wpRootDir)?$wpRootDir:'../../../../';
require ($wpRootDir.'wp-load.php');

require_once('../classes/WP_MVMCloud/Settings.php');
require_once('../classes/WP_MVMCloud/Logger.php');
require_once('../classes/WP_MVMCloud/Logger/Dummy.php');

$logger = new WP_MVMCloud\Logger\Dummy ( __CLASS__ );
$settings = new WP_MVMCloud\Settings ( $logger );

$protocol = (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] != 'off') ? 'https' : 'http';

switch ($settings->getGlobalOption ( 'mvmcloud_mode' )) {
	case 'php' :
		$MVMCLOUD_URL = $settings->getGlobalOption ( 'proxy_url' );
		break;
	case 'cloud' :
		$MVMCLOUD_URL = 'https://' . $settings->getGlobalOption ( 'mvmcloud_user' ) . '.innocraft.cloud/';
		break;
    case 'cloud-mvmcloud' :
        $MVMCLOUD_URL = 'https://' . $settings->getGlobalOption ( 'mvmcloud_user' ) . '.mvmcloud.cloud/';
        break;
	default :
		$MVMCLOUD_URL = $settings->getGlobalOption ( 'mvmcloud_url' );
}

if (substr ( $MVMCLOUD_URL, 0, 2 ) == '//')
	$MVMCLOUD_URL = $protocol . ':' . $MVMCLOUD_URL;

$TOKEN_AUTH = $settings->getGlobalOption ( 'mvmcloud_token' );
$timeout = $settings->getGlobalOption ( 'connection_timeout' );
$useCurl = (
	(function_exists('curl_init') && ini_get('allow_url_fopen') && $settings->getGlobalOption('http_connection') == 'curl') || (function_exists('curl_init') && !ini_get('allow_url_fopen'))
);

$settings->getGlobalOption ( 'http_connection' );

ini_set ( 'display_errors', 0 );
