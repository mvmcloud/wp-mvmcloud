<?php

	namespace WP_MVMCloud\Widget;

	use WP_MVMCloud\Widget;

	class City extends Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$timeSettings = $this->getTimeSettings();
			$this->parameter = array(
				'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
				'period' => $timeSettings['period'],
				'date'  => $timeSettings['date']
			);
			$this->title = $prefix.__('Cities', 'wp-mvmcloud').' ('.__($timeSettings['description'],'wp-mvmcloud').')';
			$this->method = 'UserCountry.getCity';
			$this->context = 'normal';
			wp_enqueue_script('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'js/wp-mvmcloud.js', array(), self::$wpMvmcloud->getPluginVersion(), true);
			wp_enqueue_script ( 'wp-mvmcloud-chartjs', self::$wpMvmcloud->getPluginURL () . 'js/chartjs/chart.min.js', "3.4.1" );
			wp_enqueue_style('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'css/wp-mvmcloud.css',array(),self::$wpMvmcloud->getPluginVersion());
}

		public function show() {
			$response = self::$wpMvmcloud->request($this->apiID[$this->method]);
			$tableBody = array();
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Mvmcloud error', 'wp-mvmcloud').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
				$tableHead = array(__('City', 'wp-mvmcloud'), __('Unique', 'wp-mvmcloud'), __('Percent', 'wp-mvmcloud'));
				if (isset($response[0]['nb_uniq_visitors'])) $unique = 'nb_uniq_visitors';
				else $unique = 'sum_daily_nb_uniq_visitors';
				$count = 0;
				$sum = 0;
				$js = array();
				$class = array();
                if (is_array($response))
                    foreach ($response as $row) {
                        $count++;
                        $sum += isset($row[$unique])?$row[$unique]:0;
                        if ($count < $this->limit)
                            $tableBody[$row['label']] = array($row['label'], $row[$unique], 0);
                        elseif (!isset($tableBody['Others'])) {
                            $tableBody['Others'] = array($row['label'], $row[$unique], 0);
                            $class['Others'] = 'wp-mvmcloud-hideDetails';
                            $js['Others'] = '$j'."( '.wp-mvmcloud-hideDetails' ).toggle( 'hidden' );";
                            $tableBody[$row['label']] = array($row['label'], $row[$unique], 0);
                            $class[$row['label']] = 'wp-mvmcloud-hideDetails hidden';
                            $js[$row['label']] = '$j'."( '.wp-mvmcloud-hideDetails' ).toggle( 'hidden' );";
                        } else {
                            $tableBody['Others'][1] += $row[$unique];
                            $tableBody[$row['label']] = array($row['label'], $row[$unique], 0);
                            $class[$row['label']] = 'wp-mvmcloud-hideDetails hidden';
                            $js[$row['label']] = '$j'."( '.wp-mvmcloud-hideDetails' ).toggle( 'hidden' );";
                        }
    				}
				if ($count > $this->limit)
					$tableBody['Others'][0] = __('Others', 'wp-mvmcloud');
				elseif ($count == $this->limit) {
					$class['Others'] = $js['Others'] = '';
				}

				foreach ($tableBody as $key => $row)
					$tableBody[$key][2] = number_format($row[1]/$sum*100, 2).'%';

				if (!empty($tableBody)) $this->pieChart($tableBody);
				$this->table($tableHead, $tableBody, null, false, $js, $class);
			}
		}

	}
