<?php

	namespace WP_MVMCloud\Widget;

	class Post extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
            global $post;
            $timeSettings = $this->getTimeSettings();
            $this->parameter = array(
                'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
                'period' => isset($params['period']) ? $params['period'] : $timeSettings['period'],
                'date' => isset($params['date']) ? $params['date'] : $timeSettings['date'],
                'key' => isset($params['key'])?$params['key']:null,
                'pageUrl' => isset($params['url'])?$params['url']:urlencode(get_permalink($post->ID)),
                'description' => $timeSettings['description']
            );
			$this->title = $prefix.__('Overview', 'wp-mvmcloud').' ('.__($this->parameter['date'],'wp-mvmcloud').')';
			$this->method = 'Actions.getPageUrl';
		}

		public function show() {
			$response = self::$wpMvmcloud->request($this->apiID[$this->method]);
            if (!empty($response['result']) && $response['result'] = 'error')
                echo '<strong>' . __('Mvmcloud error', 'wp-mvmcloud') . ':</strong> ' . htmlentities($response['message'], ENT_QUOTES, 'utf-8');
            else {
                if (in_array($this->parameter['date'], array('last30', 'last60', 'last90'))) {
                    $result = array();
                    if (is_array($response)) {
                        foreach ($response as $data) {
                            if (isset($data[0])) {
                                foreach ($data[0] as $key => $value)
                                    if (isset($result[$key]) && is_numeric($value))
                                        $result[$key] += $value;
                                    elseif (is_numeric($value))
                                        $result[$key] = $value;
                                    else
                                        $result[$key] = 0;
                            }
                        }
                        if (isset($result['nb_visits']) && $result['nb_visits'] > 0) {
                            $result['nb_actions_per_visit'] = round((isset( $result['nb_actions'] ) ? $result['nb_actions'] : 0) / $result['nb_visits'], 1);
                            $result['bounce_rate'] = round((isset($result['bounce_count']) ? $result['bounce_count'] : 0) / $result['nb_visits'] * 100, 1) . '%';
                            $result['avg_time_on_site'] = round((isset($result['sum_visit_length']) ? $result['sum_visit_length'] : 0) / $result['nb_visits'], 0);
                        } else $result['nb_actions_per_visit'] = $result['bounce_rate'] = $result['avg_time_on_site'] = 0;
                    }
                    $response = $result;
                } else {
                    if (isset($response[0]))
                        $response = $response[0];
                    if ($this->parameter['key']) {
                        $this->out(isset($response[$this->parameter['key']])?$response[$this->parameter['key']]:'<em>not defined</em>');
                        return;
                    }
                }
                $time = isset($response['sum_visit_length']) ? $this->timeFormat($response['sum_visit_length']) : '-';
                $avgTime = isset($response['avg_time_on_site']) ? $this->timeFormat($response['avg_time_on_site']) : '-';
                $tableHead = null;
                $tableBody = array(array(__('Visitors', 'wp-mvmcloud') . ':', $this->value($response, 'nb_visits')));
                if ($this->value($response, 'nb_uniq_visitors') != '-')
                    array_push($tableBody, array(__('Unique visitors', 'wp-mvmcloud') . ':', $this->value($response, 'nb_uniq_visitors')));
                elseif ($this->value($response, 'sum_daily_nb_uniq_visitors') != '-') {
                    array_push($tableBody, __('Unique visitors', 'wp-mvmcloud') . ':', $this->value($response, 'sum_daily_nb_uniq_visitors'));
                }
                array_push($tableBody,
                    array(__('Page views', 'wp-mvmcloud').':', $this->value($response, 'nb_hits').' (&#216; '.$this->value($response, 'entry_nb_actions').')'),
                    array(__('Total time spent', 'wp-mvmcloud').':', $time),
                    array(__('Bounce count', 'wp-mvmcloud').':', $this->value($response, 'entry_bounce_count').' ('.$this->value($response, 'bounce_rate').')'),
                    array(__('Time/visit', 'wp-mvmcloud').':', $avgTime),
					array(__('Min. generation time', 'wp-mvmcloud').':', $this->value($response, 'min_time_generation')),
					array(__('Max. generation time', 'wp-mvmcloud').':', $this->value($response, 'max_time_generation'))
                );
                if (!in_array($this->parameter['date'], array('last30', 'last60', 'last90')))
                    array_push($tableBody, array(__('Time/visit', 'wp-mvmcloud') . ':', $avgTime), array(__('Max. page views in one visit', 'wp-mvmcloud') . ':', $this->value($response, 'max_actions')));
                $tableFoot = (self::$settings->getGlobalOption('mvmcloud_shortcut') ? array(__('Shortcut', 'wp-mvmcloud') . ':', '<a href="' . self::$settings->getGlobalOption('mvmcloud_url') . '">Mvmcloud</a>' . (isset($aryConf['inline']) && $aryConf['inline'] ? ' - <a href="?page=wp-mvmcloud_stats">WP-MVMCloud</a>' : '')) : null);
                $this->table($tableHead, $tableBody, $tableFoot);
            }
		}

	}
