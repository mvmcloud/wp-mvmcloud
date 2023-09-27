<?php

	namespace WP_MVMCloud\Widget;

	class Pages extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Pages', 'wp-mvmcloud').' ('.__($timeSettings['description'],'wp-mvmcloud').')';
			$this->method = 'Actions.getPageTitles';
			$this->name = __('Page', 'wp-mvmcloud' );
		}

	}
