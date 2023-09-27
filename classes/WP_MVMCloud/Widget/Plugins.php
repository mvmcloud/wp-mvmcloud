<?php

	namespace WP_MVMCloud\Widget;

	class Plugins extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Plugins', 'wp-mvmcloud').' ('.__($timeSettings['description'],'wp-mvmcloud').')';
			$this->method = 'DevicePlugins.getPlugin';
		}

		public function show() {
			$response = self::$wpMvmcloud->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Mvmcloud error', 'wp-mvmcloud').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				$tableHead = array(__('Plugin', 'wp-mvmcloud'), __('Visits', 'wp-mvmcloud'), __('Percent', 'wp-mvmcloud'));
				$tableBody = array();
				$count = 0;
				if (is_array($response))
				    foreach ($response as $row) {
					    $count++;
					    $tableBody[] = array($row['label'], $row['nb_visits'], $row['nb_visits_percentage']);
					    if ($count == 10) break;
				    }
				$this->table($tableHead, $tableBody, null);
			}
		}

	}
