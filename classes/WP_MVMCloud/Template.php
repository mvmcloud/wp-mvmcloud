<?php

	namespace WP_MVMCloud;

    /**
     * Manage WP-MVMCloud
     *
     * @author  MVMCloud
     * @package WP_MVMCloud
     */
	class Template {

		public static $logger, $settings, $wpMvmcloud;

		public function __construct($wpMvmcloud, $settings) {
			self::$settings = $settings;
			self::$wpMvmcloud = $wpMvmcloud;
		}

		public function output($array, $key, $default = '') {
			if (isset($array[$key]))
				return $array[$key];
			else
				return $default;
		}

		public function tabRow($name, $value) {
			echo '<tr><td>'.$name.'</td><td>'.$value.'</td></tr>';
		}

		public function getRangeLast30() {
			$diff = (self::$settings->getGlobalOption('default_date') == 'yesterday') ? -86400 : 0;
			$end = time() + $diff;
			$start = time() - 2592000 + $diff;
			return date('Y-m-d', $start).','.date('Y-m-d', $end);
		}
	}
