<?php

	namespace WP_MVMCloud\Admin;

	class Network extends \WP_MVMCloud\Admin\Statistics {

		public function show() {
			parent::show();
		}

		public function printAdminScripts() {
			wp_enqueue_script('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'js/wp-mvmcloud.js', array(), self::$wpMvmcloud->getPluginVersion(), true);
            wp_enqueue_script ( 'wp-mvmcloud-chartjs', self::$wpMvmcloud->getPluginURL() . 'js/chartjs/chart.min.js', "3.4.1" );
		}

		public function onLoad() {
			self::$wpMvmcloud->onloadStatsPage(self::$pageID);
		}
	}
