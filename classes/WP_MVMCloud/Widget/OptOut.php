<?php

	namespace WP_MVMCloud\Widget;

	class OptOut extends \WP_MVMCloud\Widget {

		public $className = __CLASS__;

		protected function configure($prefix = '', $params = array()) {
			$this->parameter = $params;
		}

		public function show() {
			$protocol = (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] != 'off') ? 'https' : 'http';
			switch (self::$settings->getGlobalOption ( 'mvmcloud_mode' )) {
				case 'php' :
					$MVMCLOUD_URL = $protocol . ':' . self::$settings->getGlobalOption ( 'proxy_url' );
					break;
				case 'cloud' :
					$MVMCLOUD_URL = 'https://' . self::$settings->getGlobalOption ( 'mvmcloud_user' ) . '.innocraft.cloud/';
					break;
                case 'cloud-mvmcloud':
                    $MVMCLOUD_URL = 'https://' . self::$settings->getGlobalOption ( 'mvmcloud_user' ) . '.mvmcloud.cloud/';
                    break;
				default :
					$MVMCLOUD_URL = self::$settings->getGlobalOption ( 'mvmcloud_url' );
			}
			$this->out ( '<iframe frameborder="no" width="'.(isset($this->parameter['width'])?$this->parameter['width']:'').'" height="'.(isset($this->parameter['height'])?$this->parameter['height']:'').'" src="'.$MVMCLOUD_URL.'index.php?module=CoreAdminHome&action=optOut&'.(isset($this->parameter['idsite'])?'idsite='.$this->parameter['idsite'].'&':'').'language='.(isset($this->parameter['language'])?$this->parameter['language']:'en').'"></iframe>' );
		}

	}
