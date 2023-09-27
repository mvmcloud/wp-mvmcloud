<?php

namespace WP_MVMCloud\Widget;

class Ecommerce extends \WP_MVMCloud\Widget
{

    public $className = __CLASS__;

    protected function configure($prefix = '', $params = array())
    {
        $timeSettings = $this->getTimeSettings();
        $this->title = $prefix . __('E-Commerce', 'wp-mvmcloud');
        $this->method = 'Goals.get';
        $this->parameter = array(
            'idSite' => self::$wpMvmcloud->getMvmcloudSiteId($this->blogId),
            'period' => $timeSettings['period'],
            'date' => $timeSettings['date']
        );
    }

    public function show()
    {
        $response = self::$wpMvmcloud->request($this->apiID[$this->method]);
        if (!empty($response['result']) && $response['result'] = 'error')
            echo '<strong>' . __('Mvmcloud error', 'wp-mvmcloud') . ':</strong> ' . htmlentities($response['message'], ENT_QUOTES, 'utf-8');
        else {
            $tableHead = null;
            $revenue = is_float($this->value($response, 'revenue')) ? number_format($this->value($response, 'revenue'), 2) : "";
            $revenue_new = is_float($this->value($response, 'revenue_new_visit')) ? number_format($this->value($response, 'revenue_new_visit'), 2) : "";
            $revenue_return = is_float($this->value($response, 'revenue_returning_visit')) ? number_format($this->value($response, 'revenue_returning_visit'), 2) : "";
            $tableBody = array(
                array(__('Conversions', 'wp-mvmcloud') . ':', $this->value($response, 'nb_conversions')),
                array(__('Visits converted', 'wp-mvmcloud') . ':', $this->value($response, 'nb_visits_converted')),
                array(__('Revenue', 'wp-mvmcloud') . ':', $revenue),
                array(__('Conversion rate', 'wp-mvmcloud') . ':', $this->value($response, 'conversion_rate')),
                array(__('Conversions (new visitor)', 'wp-mvmcloud') . ':', $this->value($response, 'nb_conversions_new_visit')),
                array(__('Visits converted (new visitor)', 'wp-mvmcloud') . ':', $this->value($response, 'nb_visits_converted_new_visit')),
                array(__('Revenue (new visitor)', 'wp-mvmcloud') . ':', $revenue_new),
                array(__('Conversion rate (new visitor)', 'wp-mvmcloud') . ':', $this->value($response, 'conversion_rate_new_visit')),
                array(__('Conversions (returning visitor)', 'wp-mvmcloud') . ':', $this->value($response, 'nb_conversions_returning_visit')),
                array(__('Visits converted (returning visitor)', 'wp-mvmcloud') . ':', $this->value($response, 'nb_visits_converted_returning_visit')),
                array(__('Revenue (returning visitor)', 'wp-mvmcloud') . ':', $revenue_return),
                array(__('Conversion rate (returning visitor)', 'wp-mvmcloud') . ':', $this->value($response, 'conversion_rate_returning_visit')),
            );
            $tableFoot = (self::$settings->getGlobalOption('mvmcloud_shortcut') ? array(__('Shortcut', 'wp-mvmcloud') . ':', '<a href="' . self::$settings->getGlobalOption('mvmcloud_url') . '">Mvmcloud</a>' . (isset($aryConf['inline']) && $aryConf['inline'] ? ' - <a href="?page=wp-mvmcloud_stats">WP-MVMCloud</a>' : '')) : null);
            $this->table($tableHead, $tableBody, $tableFoot);
        }
    }

}
