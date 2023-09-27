<?php

	namespace WP_MVMCloud\Request;

	class Php extends \WP_MVMCloud\Request {

		private static $mvmcloudEnvironment = false;

		protected function request($id) {
			$count = 0;
			$url = self::$settings->getGlobalOption('mvmcloud_url');
			foreach (self::$requests as $requestID => $config) {
				if (!isset(self::$results[$requestID])) {
                    if (self::$settings->getGlobalOption('filter_limit') != "" && self::$settings->getGlobalOption('filter_limit') == (int) self::$settings->getGlobalOption('filter_limit'))
                        $config['parameter']['filter_limit'] = self::$settings->getGlobalOption('filter_limit');
					$params = 'module=API&format=json&'.$this->buildURL($config, true);
                    $map[$count] = $requestID;
					$result = $this->call($id, $url, $params);
					self::$results[$map[$count]] = $result;
					$count++;
				}
			}
		}

		private function call($id, $url, $params) {
			if (!defined('MVMCLOUD_INCLUDE_PATH'))
				return false;
			if (MVMCLOUD_INCLUDE_PATH === FALSE)
				 return array('result' => 'error', 'message' => __('Could not resolve','wp-mvmcloud').' &quot;'.htmlentities(self::$settings->getGlobalOption('mvmcloud_path')).'&quot;: '.__('realpath() returns false','wp-mvmcloud').'.');
			if (file_exists(MVMCLOUD_INCLUDE_PATH . "/index.php"))
				require_once MVMCLOUD_INCLUDE_PATH . "/index.php";
			if (file_exists(MVMCLOUD_INCLUDE_PATH . "/core/API/Request.php"))
				require_once MVMCLOUD_INCLUDE_PATH . "/core/API/Request.php";
			if (class_exists('\Mvmcloud\Application\Environment') && !self::$mvmcloudEnvironment) {
				// MVMCloud Analytics 2.14.* compatibility fix
				self::$mvmcloudEnvironment = new \Mvmcloud\Application\Environment(null);
				self::$mvmcloudEnvironment->init();
			}
			if (class_exists('Mvmcloud\FrontController'))
				\Mvmcloud\FrontController::getInstance()->init();
			else return array('result' => 'error', 'message' => __('Class MVMCloud Analytics\FrontController does not exists.','wp-mvmcloud'));
			if (class_exists('Mvmcloud\API\Request'))
				$request = new \Mvmcloud\API\Request($params.'&token_auth='.self::$settings->getGlobalOption('mvmcloud_token'));
			else return array('result' => 'error', 'message' => __('Class MVMCloud Analytics\API\Request does not exists.','wp-mvmcloud'));
			if (isset($request))
				$result = $request->process();
			else $result = null;
			if (!headers_sent())
				header("Content-Type: text/html", true);
			$result = $this->unserialize($result);
			if ($GLOBALS ['wp-mvmcloud_debug'])
				self::$debug[$id] = array ( $params.'&token_auth=...' );
			return $result;
		}
	}
