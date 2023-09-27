<?php

namespace WP_MVMCloud\Widget;

use WP_MVMCloud\Widget;

class Visitors extends Widget
{

    public $className = __CLASS__;

    protected function configure($prefix = '', $params = array())
    {
        $timeSettings = $this->getTimeSettings();
        $this->parameter = array(
            'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
            'period' => isset($params['period']) ? $params['period'] : $timeSettings['period'],
            'date' => 'last' . ($timeSettings['period'] == 'day' ? '30' : '12'),
            'limit' => null
        );
        $this->title = $prefix . __('Visitors', 'wp-mvmcloud') . ' (' . __($this->rangeName(), 'wp-mvmcloud') . ')';
        $this->method = array('VisitsSummary.getVisits', 'VisitsSummary.getUniqueVisitors', 'VisitsSummary.getBounceCount', 'VisitsSummary.getActions');
        $this->context = 'normal';
        wp_enqueue_script('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'js/wp-mvmcloud.js', array(), self::$wpMvmcloud->getPluginVersion(), true);
        wp_enqueue_script('wp-mvmcloud-chartjs', self::$wpMvmcloud->getPluginURL() . 'js/chartjs/chart.min.js', "3.4.1");
        wp_enqueue_style('wp-mvmcloud', self::$wpMvmcloud->getPluginURL() . 'css/wp-mvmcloud.css', array(), self::$wpMvmcloud->getPluginVersion());
    }

    public function requestData()
    {
        $response = array();
        $success = true;
        foreach ($this->method as $method) {
            $response[$method] = self::$wpMvmcloud->request($this->apiID[$method]);
            if (!empty($response[$method]['result']) && $response[$method]['result'] = 'error')
                $success = false;
        }
        return array("response" => $response, "success" => $success);
    }

    public function show()
    {
        $result = $this->requestData();
        $response = $result["response"];
        if (!$result["success"]) {
            echo '<strong>' . __('Mvmcloud error', 'wp-mvmcloud') . ':</strong> ' . htmlentities($response[$method]['message'], ENT_QUOTES, 'utf-8');
        } else {
            $data = array();
            if (is_array($response) && is_array($response['VisitsSummary.getVisits']))
                foreach ($response['VisitsSummary.getVisits'] as $key => $value) {
                    if ($this->parameter['period'] == 'week') {
                        preg_match("/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/", $key, $dateList);
                        $jsKey = $dateList[0];
                        $textKey = $this->dateFormat($jsKey, 'week');
                    } elseif ($this->parameter['period'] == 'month') {
                        $jsKey = $key . '-01';
                        $textKey = $key;
                    } else $jsKey = $textKey = $key;
                    $data[] = array(
                        $textKey,
                        $value,
                        $response['VisitsSummary.getUniqueVisitors'][$key] ? $response['VisitsSummary.getUniqueVisitors'][$key] : '-',
                        $response['VisitsSummary.getBounceCount'][$key] ? $response['VisitsSummary.getBounceCount'][$key] : '-',
                        $response['VisitsSummary.getActions'][$key] ? $response['VisitsSummary.getActions'][$key] : '-'
                    );
                    $javaScript[] = 'javascript:wp_mvmcloud_datelink(\'' . urlencode('wp-mvmcloud_stats') . '\',\'' . str_replace('-', '', $jsKey) . '\',\'' . (isset($_GET['wpmu_show_stats']) ? (int)$_GET['wpmu_show_stats'] : '') . '\');';
                }
            $this->table(
                array(__('Date', 'wp-mvmcloud'), __('Visits', 'wp-mvmcloud'), __('Unique', 'wp-mvmcloud'), __('Bounced', 'wp-mvmcloud'), __('Page Views', 'wp-mvmcloud')),
                array_reverse($data),
                array(),
                'clickable',
                array_reverse(isset($javaScript) ? $javaScript : [])
            );
        }

    }

}
