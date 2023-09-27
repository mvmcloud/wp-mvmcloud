<?php

// Check if uninstall call is valid
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

$globalSettings = array(
	'revision',
	'last_settings_update',
	'mvmcloud_mode',
	'mvmcloud_url',
	'mvmcloud_path',
	'mvmcloud_user',
	'mvmcloud_user',
	'mvmcloud_token',
	'auto_site_config',
	'default_date',
	'stats_seo',
	'dashboard_widget',
	'dashboard_chart',
	'dashboard_seo',
	'toolbar',
	'capability_read_stats',
	'perpost_stats',
	'plugin_display_name',
	'mvmcloud_shortcut',
	'shortcodes',
	'track_mode',
	'track_codeposition',
	'track_noscript',
	'track_nojavascript',
	'proxy_url',
	'track_content',
	'track_search',
	'track_404',
	'add_post_annotations',
	'add_customvars_box',
	'add_download_extensions',
	'disable_cookies',
	'limit_cookies',
	'limit_cookies_visitor',
	'limit_cookies_session',
	'limit_cookies_referral',
	'track_admin',
	'capability_stealth',
	'track_across',
	'track_across_alias',
	'track_crossdomain_linking',
	'track_feed',
	'track_feed_addcampaign',
	'track_feed_campaign',
	'cache',
	'disable_timelimit',
	'connection_timeout',
	'disable_ssl_verify',
	'disable_ssl_verify_host',
	'mvmcloud_useragent',
	'mvmcloud_useragent_string',
	'track_datacfasync',
	'track_cdnurl',
	'track_cdnurlssl',
	'force_protocol'
);

$settings = array (
	'name',
	'site_id',
	'noscript_code',
	'tracking_code',
	'last_tracking_code_update',
	'dashboard_revision'
);

global $wpdb;

if (function_exists('is_multisite') && is_multisite()) {
	if ($limit && $page)
		$queryLimit = 'LIMIT '.(int) (($page - 1) * $limit).','.(int) $limit.' ';
	$aryBlogs = $wpdb->get_results('SELECT blog_id FROM '.$wpdb->blogs.' '.$queryLimit.'ORDER BY blog_id', ARRAY_A);
	if (is_array($aryBlogs))
		foreach ($aryBlogs as $aryBlog) {
	        foreach ($settings as $key) {
				delete_blog_option($aryBlog['blog_id'], 'wp-mvmcloud-'.$key);
			}
			switch_to_blog($aryBlog['blog_id']);
			$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'wp-mvmcloud_%'");
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp-mvmcloud_%'");
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-mvmcloud_%'");
			restore_current_blog();
		}
	foreach ($globalSettings as $key)
		delete_site_option('wp-mvmcloud_global-'.$key);
	delete_site_option('wp-mvmcloud-manually');
	delete_site_option('wp-mvmcloud-notices');
}

foreach ($settings as $key)
	delete_option('wp-mvmcloud-'.$key);

foreach ($globalSettings as $key)
	delete_option('wp-mvmcloud_global-'.$key);

delete_option('wp-mvmcloud-manually');
delete_option('wp-mvmcloud-notices');

$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'wp-mvmcloud-%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp-mvmcloud_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-mvmcloud_%'");
