<?php

	namespace WP_MVMCloud\Admin;

	class Statistics extends \WP_MVMCloud\Admin {

		public function show() {
			global $screen_layout_columns;
			if (empty($screen_layout_columns)) $screen_layout_columns = 2;
			if (self::$settings->getGlobalOption('disable_timelimit')) set_time_limit(0);
			echo '<div id="wp-mvmcloud-stats-general" class="wrap">';
			echo '<h2>'.(self::$settings->getGlobalOption('plugin_display_name') == 'WP-MVMCloud'?'MVMCloud Analytics '.__('Statistics', 'wp-mvmcloud'):self::$settings->getGlobalOption('plugin_display_name')).'</h2>';
			if (self::$settings->checkNetworkActivation() && function_exists('is_super_admin') && is_super_admin()) {

                if (isset($_GET['wpmu_show_stats'])) {
					switch_to_blog((int) $_GET['wpmu_show_stats']);
				} elseif ((isset($_GET['overview']) && $_GET['overview']) || (function_exists('is_network_admin') && is_network_admin())) {
					new \WP_MVMCloud\Admin\Sitebrowser(self::$wpMvmcloud);
					return;
				}
				echo '<p>'.__('Currently shown stats:').' <a href="'.get_bloginfo('url').'">'.get_bloginfo('name').'</a>.'.' <a href="?page=wp-mvmcloud_stats&overview=1">Show site overview</a>.</p>';
			}
			echo '<form action="admin-post.php" method="post"><input type="hidden" name="action" value="save_wp-mvmcloud_stats_general" /><div id="dashboard-widgets" class="metabox-holder columns-'.$screen_layout_columns.(2 <= $screen_layout_columns?' has-right-sidebar':'').'">';
			wp_nonce_field('wp-mvmcloud_stats-general');
			wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
			wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
			$columns = array('normal', 'side', 'column3');
			for ($i = 0; $i < 3; $i++) {
				echo '<div id="postbox-container-'.($i+1).'" class="postbox-container">';
				do_meta_boxes(self::$wpMvmcloud->statsPageId, $columns[$i], null);
				echo '</div>';
			}
			echo '</div></form></div>';
			echo '<script>//<![CDATA['."\n";
			echo 'jQuery(document).ready(function($) {$(".if-js-closed").removeClass("if-js-closed").addClass("closed"); postboxes.add_postbox_toggles("'.self::$wpMvmcloud->statsPageId.'");});'."\n";
			echo '//]]></script>'."\n";
			if (self::$settings->checkNetworkActivation() && function_exists('is_super_admin') && is_super_admin()) {
				restore_current_blog();
			}
		}

		public function printAdminScripts() {
			wp_enqueue_script('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'js/wp-mvmcloud.js', array(), self::$wpMvmcloud->getPluginVersion(), true);
            wp_enqueue_script ( 'wp-mvmcloud-chartjs', self::$wpMvmcloud->getPluginURL () . 'js/chartjs/chart.min.js', "3.4.1" );
		}

	}
