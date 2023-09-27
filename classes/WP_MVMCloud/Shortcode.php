<?php

	namespace WP_MVMCloud;

    /**
     * Manage WP-MVMCloud
     *
     * @author  MVMCloud
     * @package WP_MVMCloud
     */
	class Shortcode {

		private $available = array(
			'opt-out' => 'OptOut',
			'post' => 'Post',
			'overview' => 'Overview'
		), $content;

		public function __construct($attributes, $wpMvmcloud, $settings) {
			$wpMvmcloud->log('Check requested shortcode widget '.$attributes['module']);
			if (isset($attributes['module']) && isset($this->available[$attributes['module']])) {
				$wpMvmcloud->log('Add shortcode widget '.$this->available[$attributes['module']]);
				$class = '\\WP_MVMCloud\\Widget\\'.$this->available[$attributes['module']];
				$widget = new $class($wpMvmcloud, $settings, null, null, null, $attributes, true);
				$widget->show();
				$this->content = $widget->get();
			}
		}

		public function get() {
			return $this->content;
		}

	}
