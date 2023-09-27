<?php

	namespace WP_MVMCloud\Widget;

	class Items extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
            $timeSettings = $this->getTimeSettings();
            $this->title = $prefix.__('E-Commerce Items', 'wp-mvmcloud');
			$this->method = 'Goals.getItemsName';
            $this->parameter = array(
                'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
                'period' => $timeSettings['period'],
                'date'  => $timeSettings['date']
            );
		}

		public function show() {
			$response = self::$wpMvmcloud->request($this->apiID[$this->method]);
			if (!empty($response['result']) && $response['result'] ='error')
				echo '<strong>'.__('Mvmcloud error', 'wp-mvmcloud').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
			else {
			    $tableHead = array(
                    __('Label', 'wp-mvmcloud'),
                    __('Revenue', 'wp-mvmcloud'),
                    __('Quantity', 'wp-mvmcloud'),
                    __('Orders', 'wp-mvmcloud'),
                    __('Avg. price', 'wp-mvmcloud'),
                    __('Avg. quantity', 'wp-mvmcloud'),
                    __('Conversion rate', 'wp-mvmcloud'),
                );
			    $tableBody = array();
			    if (is_array($response))
                    foreach ($response as $data) {
                        array_push($tableBody, array(
                            $data['label'],
                            number_format($data['revenue'],2),
                            $data['quantity'],
                            $data['orders'],
                            number_format($data['avg_price'],2),
                            $data['avg_quantity'],
                            $data['conversion_rate']
                        ));
                    }
                $tableFoot = array();
                $this->table($tableHead, $tableBody, $tableFoot);
			}
		}

	}
