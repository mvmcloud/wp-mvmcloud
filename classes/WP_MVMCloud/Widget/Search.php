<?php

	namespace WP_MVMCloud\Widget;

	class Search extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Site Search', 'wp-mvmcloud').' ('.__($timeSettings['description'],'wp-mvmcloud').')';
			$this->method = 'Actions.getSiteSearchKeywords';
		}

		public function show() {
			$response = self::$wpMvmcloud->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Mvmcloud error', 'wp-mvmcloud').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				$tableHead = array(__('Keyword', 'wp-mvmcloud'), __('Requests', 'wp-mvmcloud'), __('Bounced', 'wp-mvmcloud'));
				$tableBody = array();
				$count = 0;
				if (is_array($response))
				    foreach ($response as $row) {
					    $count++;
					    $tableBody[] = array(htmlentities($row['label']), $row['nb_visits'], $row['bounce_rate']);
					    if ($count == 10) break;
				    }
				$this->table($tableHead, $tableBody, null);
			}
		}

	}
