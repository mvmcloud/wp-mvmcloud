<?php

	namespace WP_MVMCloud\Widget;

	class Keywords extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Keywords', 'wp-mvmcloud').' ('.__($timeSettings['description'],'wp-mvmcloud').')';
			$this->method = 'Referrers.getKeywords';
			$this->name = 'Keyword';
		}

	}
