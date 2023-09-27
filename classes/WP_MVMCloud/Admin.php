<?php

	namespace WP_MVMCloud;

    /**
     * Manage WP-MVMCloud Admin
     *
     * @author  MVMCloud
     * @package WP_MVMCloud
     */
    abstract class Admin {

		protected static $wpMvmcloud, $pageID, $settings;

		public function __construct($wpMvmcloud, $settings) {
			self::$wpMvmcloud = $wpMvmcloud;
			self::$settings = $settings;
		}

		abstract public function show();

		abstract public function printAdminScripts();

		public function printAdminStyles() {
			wp_enqueue_style('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'css/wp-mvmcloud.css', array(), self::$wpMvmcloud->getPluginVersion());
		}

		public function onLoad() {}

	}
