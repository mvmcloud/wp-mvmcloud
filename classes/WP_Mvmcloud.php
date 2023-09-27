<?php

use WP_MVMCloud\Widget\Post;

/**
 * The main WP-MVMCloud class configures, registers and manages the plugin
 *
 * @author MVMCloud
 * @package WP_MVMCloud
 */
class WP_Mvmcloud {

	private static $revisionId = 2023052101, $version = '2.0.28', $blog_id, $pluginBasename = NULL, $logger, $settings, $request, $optionsPageId;

	/**
	 * Constructor class to configure and register all WP-MVMCloud components
	 */
	public function __construct() {
		global $blog_id;
		self::$blog_id = (isset ( $blog_id ) ? $blog_id : 'n/a');
		$this->openLogger ();
		$this->openSettings ();
		$this->setup ();
		$this->addFilters ();
		$this->addActions ();
		$this->addShortcodes ();
	}

	/**
	 * Destructor class to finish logging
	 */
	public function __destruct() {
		$this->closeLogger ();
	}

	/**
	 * Setup class to prepare settings and check for installation and update
	 */
	private function setup() {
		self::$pluginBasename = plugin_basename ( __FILE__ );
		if (! $this->isInstalled ())
			$this->installPlugin ();
		elseif ($this->isUpdated ())
			$this->updatePlugin ();
		if ($this->isConfigSubmitted ())
			$this->applySettings ();
		self::$settings->save ();
	}

	/**
	 * Register WordPress actions
	 */
	private function addActions() {
		if ( is_admin () ) {
			add_action ( 'admin_menu', array (
					$this,
					'buildAdminMenu'
			) );
			add_action ( 'admin_post_save_wp-mvmcloud_stats', array (
					$this,
					'onStatsPageSaveChanges'
			) );
			add_action ( 'load-post.php', array (
					$this,
					'addPostMetaboxes'
			) );
			add_action ( 'load-post-new.php', array (
					$this,
					'addPostMetaboxes'
			) );
			if ($this->isNetworkMode ()) {
				add_action ( 'network_admin_notices', array (
						$this,
						'showNotices'
				) );
				add_action ( 'network_admin_menu', array (
						$this,
						'buildNetworkAdminMenu'
				) );
				add_action ( 'update_site_option_blogname', array (
						$this,
						'onBlogNameChange'
				) );
				add_action ( 'update_site_option_siteurl', array (
						$this,
						'onSiteUrlChange'
				) );
			} else {
				add_action ( 'admin_notices', array (
						$this,
						'showNotices'
				) );
				add_action ( 'update_option_blogname', array (
						$this,
						'onBlogNameChange'
				) );
				add_action ( 'update_option_siteurl', array (
						$this,
						'onSiteUrlChange'
				) );
			}
			if ($this->isDashboardActive ())
				add_action ( 'wp_dashboard_setup', array (
						$this,
						'extendWordPressDashboard'
				) );
		}
		if ($this->isToolbarActive ()) {
			add_action ( is_admin () ? 'admin_head' : 'wp_head', array (
					$this,
					'loadToolbarRequirements'
			) );
			add_action ( 'admin_bar_menu', array (
					$this,
					'extendWordPressToolbar'
			), 1000 );
		}
		if ($this->isTrackingActive ()) {
			if ( !is_admin () || $this->isAdminTrackingActive ()) {
			    $prefix = is_admin ()?'admin':'wp';
				add_action ( self::$settings->getGlobalOption ( 'track_codeposition' ) == 'footer' ? $prefix.'_footer' : $prefix.'_head', array (
						$this,
						'addJavascriptCode'
					) );
                if (self::$settings->getGlobalOption ( 'dnsprefetch' ))
                    add_action ( $prefix.'_head', array (
                        $this,
                        'addDNSPrefetchTag'
                    ) );
				if ($this->isAddNoScriptCode ())
					add_action ( $prefix.'_footer', array (
							$this,
							'addNoscriptCode'
					) );
			}
			if (self::$settings->getGlobalOption ( 'add_post_annotations' ))
				add_action ( 'transition_post_status', array (
						$this,
						'addMvmcloudAnnotation'
				), 10, 3 );
		}

	}

	/**
	 * Register WordPress filters
	 */
	private function addFilters() {
		if (is_admin()) {
			add_filter ( 'plugin_row_meta', array (
					$this,
					'setPluginMeta'
			), 10, 2 );
			add_filter ( 'screen_layout_columns', array (
					$this,
					'onScreenLayoutColumns'
			), 10, 2 );
		} elseif ($this->isTrackingActive ()) {
			if ($this->isTrackFeed ()) {
				add_filter ( 'the_excerpt_rss', array (
						$this,
						'addFeedTracking'
				) );
				add_filter ( 'the_content', array (
						$this,
						'addFeedTracking'
				) );
			}
			if ($this->isAddFeedCampaign ()) {
				add_filter ( 'post_link', array (
						$this,
						'addFeedCampaign'
				) );
			}
			if ($this->isCrossDomainLinkingEnabled ()) {
				add_filter ( 'wp_redirect', array (
					$this,
					'forwardCrossDomainVisitorId'
				) );
			}
		}
	}

	/**
	 * Register WordPress shortcodes
	 */
	private function addShortcodes() {
		if ($this->isAddShortcode ())
			add_shortcode ( 'wp-mvmcloud', array (
					$this,
					'shortcode'
			) );
	}

	/**
	 * Install WP-MVMCloud for the first time
	 */
	private function installPlugin($isUpdate = false) {
		self::$logger->log ( 'Running WP-MVMCloud installation' );
		if (! $isUpdate)
			$this->addNotice ( 'install', sprintf ( __ ( '%s %s installed.', 'wp-mvmcloud' ), self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), self::$version ), __ ( 'Next you should connect to MVMCloud Analytics', 'wp-mvmcloud' ) );
		self::$settings->setGlobalOption ( 'revision', self::$revisionId );
		self::$settings->setGlobalOption ( 'last_settings_update', time () );
	}

	/**
	 * Uninstall WP-MVMCloud
	 */
	public function uninstallPlugin() {
		self::$logger->log ( 'Running WP-MVMCloud uninstallation' );
		if (! defined ( 'WP_UNINSTALL_PLUGIN' ))
			exit ();
		self::deleteWordPressOption ( 'wp-mvmcloud-notices' );
		self::$settings->resetSettings ( true );
	}

	/**
	 * Update WP-MVMCloud
	 */
	private function updatePlugin() {
		self::$logger->log ( 'Upgrade WP-MVMCloud to ' . self::$version );
		$patches = glob ( dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . '*.php' );
		$isPatched = false;
		if (is_array ( $patches )) {
			sort ( $patches );
			foreach ( $patches as $patch ) {
				$patchVersion = ( int ) pathinfo ( $patch, PATHINFO_FILENAME );
				if ($patchVersion && self::$settings->getGlobalOption ( 'revision' ) < $patchVersion) {
					self::includeFile ( 'update' . DIRECTORY_SEPARATOR . $patchVersion );
					$isPatched = true;
				}
			}
		}
		if ((self::$settings->getGlobalOption('update_notice') == 'enabled') || ((self::$settings->getGlobalOption('update_notice') == 'script') && $isPatched))
			$this->addNotice ( 'update', sprintf ( __ ( '%s updated to %s.', 'wp-mvmcloud' ), self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), self::$version ), __ ( 'Please validate your configuration', 'wp-mvmcloud' ) );
		$this->installPlugin ( true );
	}

	/**
	 * Define a notice
	 *
	 * @param string $type
	 *        	identifier
	 * @param string $subject
	 *        	notice headline
	 * @param string $text
	 *        	notice content
	 * @param boolean $stay
	 *        	set to true if the message should persist (default: false)
	 */
	private function addNotice($type, $subject, $text, $stay = false) {
		$notices = $this->getWordPressOption ( 'wp-mvmcloud-notices', array () );
		$notices [$type] = array (
				'subject' => $subject,
				'text' => $text,
				'stay' => $stay
		);
		$this->updateWordPressOption ( 'wp-mvmcloud-notices', $notices );
	}

	/**
	 * Show all notices defined previously
	 *
	 * @see addNotice()
	 */
	public function showNotices() {
		$link = sprintf ( '<a href="' . $this->getSettingsURL () . '">%s</a>', __ ( 'Settings', 'wp-mvmcloud' ) );
		if ($notices = $this->getWordPressOption ( 'wp-mvmcloud-notices' )) {
			foreach ( $notices as $type => $notice ) {
				printf ( '<div class="updated fade"><p>%s <strong>%s:</strong> %s: %s</p></div>', $notice ['subject'], __ ( 'Important', 'wp-mvmcloud' ), $notice ['text'], $link );
				if (! $notice ['stay'])
					unset ( $notices [$type] );
			}
		}
		$this->updateWordPressOption ( 'wp-mvmcloud-notices', $notices );
	}

	/**
	 * Get the settings page URL
	 *
	 * @return string settings page URL
	 */
	private function getSettingsURL() {
		return (self::$settings->checkNetworkActivation () ? 'settings' : 'options-general') . '.php?page=' . self::$pluginBasename;
	}

	/**
	 * Echo javascript tracking code
	 */
	public function addJavascriptCode() {
		if ($this->isHiddenUser ()) {
			self::$logger->log ( 'Do not add tracking code to site (user should not be tracked) Blog ID: ' . self::$blog_id . ' Site ID: ' . self::$settings->getOption ( 'site_id' ) );
			return;
		}
		$trackingCode = new WP_MVMCloud\TrackingCode ( $this );
		$trackingCode->is404 = (is_404 () && self::$settings->getGlobalOption ( 'track_404' ));
		$trackingCode->isUsertracking = self::$settings->getGlobalOption ( 'track_user_id' ) != 'disabled';
		$trackingCode->isSearch = (is_search () && self::$settings->getGlobalOption ( 'track_search' ));
		self::$logger->log ( 'Add tracking code. Blog ID: ' . self::$blog_id . ' Site ID: ' . self::$settings->getOption ( 'site_id' ) );
		if ($this->isNetworkMode () && self::$settings->getGlobalOption ( 'track_mode' ) == 'manually') {
			$siteId = $this->getMvmcloudSiteId ();
			if ($siteId != 'n/a')
				echo str_replace ( '{ID}', $siteId, $trackingCode->getTrackingCode () );
			else
				echo '<!-- Site will be created and tracking code added on next request -->';
		} else
			echo $trackingCode->getTrackingCode ();
	}

    /**
     * Echo DNS prefetch tag
     */
    public function addDNSPrefetchTag() {
        echo '<link rel="dns-prefetch" href="'.$this->getMvmcloudDomain().'" />';
    }

    /**
     * Get MVMCloud Analytics Domain
     */
    public function getMvmcloudDomain() {
        switch (self::$settings->getGlobalOption ( 'mvmcloud_mode' )) {
            case 'php' :
                return '//' . parse_url(self::$settings->getGlobalOption ( 'proxy_url' ), PHP_URL_HOST);
            case 'cloud' :
                return '//' . self::$settings->getGlobalOption ( 'mvmcloud_user' ) . '.innocraft.cloud';
            case 'cloud-mvmcloud' :
                return '//' . self::$settings->getGlobalOption ( 'mvmcloud_user' ) . '.mvmcloud.cloud';
            default :
                return '//' . parse_url(self::$settings->getGlobalOption ( 'mvmcloud_url' ), PHP_URL_HOST);
        }
    }

	/**
	 * Echo noscript tracking code
	 */
	public function addNoscriptCode() {
		if (self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy')
			return;
		if ($this->isHiddenUser ()) {
			self::$logger->log ( 'Do not add noscript code to site (user should not be tracked) Blog ID: ' . self::$blog_id . ' Site ID: ' . self::$settings->getOption ( 'site_id' ) );
			return;
		}
		self::$logger->log ( 'Add noscript code. Blog ID: ' . self::$blog_id . ' Site ID: ' . self::$settings->getOption ( 'site_id' ) );
		echo self::$settings->getOption ( 'noscript_code' ) . "\n";
	}

	/**
	 * Register post view meta boxes
	 */
	public function addPostMetaboxes() {
		if (self::$settings->getGlobalOption ( 'add_customvars_box' )) {
			add_action ( 'add_meta_boxes', array (
					new WP_MVMCloud\Template\MetaBoxCustomVars ( $this, self::$settings ),
					'addMetabox'
			) );
			add_action ( 'save_post', array (
					new WP_MVMCloud\Template\MetaBoxCustomVars ( $this, self::$settings ),
					'saveCustomVars'
			), 10, 2 );
		}
		if (self::$settings->getGlobalOption ( 'perpost_stats' ) != "disabled") {
			add_action ( 'add_meta_boxes', array (
					$this,
					'onloadPostPage'
			) );
		}
	}

	/**
	 * Register admin menu components
	 */
	public function buildAdminMenu() {
		if (self::isConfigured ()) {
			$cap = 'wp-mvmcloud_read_stats';
			if (self::$settings->checkNetworkActivation ()) {
				global $current_user;
				$userRoles = $current_user->roles;
				$allowed = self::$settings->getGlobalOption ( 'capability_read_stats' );
				if (is_array($userRoles) && is_array($allowed))
					foreach ($userRoles as $userRole)
						if (isset( $allowed[$userRole] ) && $allowed[$userRole]) {
							$cap = 'read';
							break;
						}
			}
			$statsPage = new WP_MVMCloud\Admin\Statistics ( $this, self::$settings );
			$this->statsPageId = add_dashboard_page ( __ ( 'Mvmcloud Statistics', 'wp-mvmcloud' ), self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), $cap, 'wp-mvmcloud_stats', array (
					$statsPage,
					'show'
			) );
			$this->loadAdminStatsHeader ( $this->statsPageId, $statsPage );
		}
		if (! self::$settings->checkNetworkActivation ()) {
			$optionsPage = new WP_MVMCloud\Admin\Settings ( $this, self::$settings );
			self::$optionsPageId = add_options_page ( self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), 'activate_plugins', __FILE__, array (
					$optionsPage,
					'show'
			) );
			$this->loadAdminSettingsHeader ( self::$optionsPageId, $optionsPage );
		}
	}

	/**
	 * Register network admin menu components
	 */
	public function buildNetworkAdminMenu() {
		if (self::isConfigured ()) {
			$statsPage = new WP_MVMCloud\Admin\Network ( $this, self::$settings );
			$this->statsPageId = add_dashboard_page ( __ ( 'Mvmcloud Statistics', 'wp-mvmcloud' ), self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), 'manage_sites', 'wp-mvmcloud_stats', array (
					$statsPage,
					'show'
			) );
			$this->loadAdminStatsHeader ( $this->statsPageId, $statsPage );
		}
		$optionsPage = new WP_MVMCloud\Admin\Settings ( $this, self::$settings );
		self::$optionsPageId = add_submenu_page ( 'settings.php', self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ), 'manage_sites', __FILE__, array (
				$optionsPage,
				'show'
		) );
		$this->loadAdminSettingsHeader ( self::$optionsPageId, $optionsPage );
	}

	/**
	 * Register admin header extensions for stats page
	 *
	 * @param $optionsPageId options
	 *        	page id
	 * @param $optionsPage options
	 *        	page object
	 */
	public function loadAdminStatsHeader($statsPageId, $statsPage) {
		add_action ( 'admin_print_scripts-' . $statsPageId, array (
				$statsPage,
				'printAdminScripts'
		) );
		add_action ( 'admin_print_styles-' . $statsPageId, array (
				$statsPage,
				'printAdminStyles'
		) );
		add_action ( 'load-' . $statsPageId, array (
				$this,
				'onloadStatsPage'
		) );
	}

	/**
	 * Register admin header extensions for settings page
	 *
	 * @param $optionsPageId options
	 *        	page id
	 * @param $optionsPage options
	 *        	page object
	 */
	public function loadAdminSettingsHeader($optionsPageId, $optionsPage) {
		add_action ( 'admin_head-' . $optionsPageId, array (
				$optionsPage,
				'extendAdminHeader'
		) );
		add_action ( 'admin_print_styles-' . $optionsPageId, array (
				$optionsPage,
				'printAdminStyles'
		) );
	}

	/**
	 * Register WordPress dashboard widgets
	 */
	public function extendWordPressDashboard() {
		if (current_user_can ( 'wp-mvmcloud_read_stats' )) {
			if (self::$settings->getGlobalOption ( 'dashboard_widget' ) != 'disabled')
				new WP_MVMCloud\Widget\Overview ( $this, self::$settings, 'dashboard', 'side', 'default', array (
						'date' => self::$settings->getGlobalOption ( 'dashboard_widget' ),
						'period' => 'day'
				) );
			if (self::$settings->getGlobalOption ( 'dashboard_chart' ))
				new WP_MVMCloud\Widget\Chart ( $this, self::$settings );
            if (self::$settings->getGlobalOption ( 'dashboard_ecommerce' ))
                new WP_MVMCloud\Widget\Ecommerce ( $this, self::$settings );
			if (self::$settings->getGlobalOption ( 'dashboard_seo' ))
				new WP_MVMCloud\Widget\Seo ( $this, self::$settings );
		}
	}

	/**
	 * Register WordPress toolbar components
	 */
	public function extendWordPressToolbar($toolbar) {
		if (current_user_can ( 'wp-mvmcloud_read_stats' ) && is_admin_bar_showing ()) {
			$id = WP_MVMCloud\Request::register ( 'VisitsSummary.getUniqueVisitors', array (
					'period' => 'day',
					'date' => 'last30'
			) );
			$unique = $this->request ( $id );
			$url = is_network_admin () ? $this->getSettingsURL () : false;
			$content = is_network_admin () ? __('Configure WP-MVMCloud', 'wp-mvmcloud') : '';
			// Leave if result array does contain a message instead of valid data
			if (isset($unique['result']))
				$content .= '<!-- '.$unique['result'].': '.($unique['message']?$unique['message']:'...').' -->';
			elseif (is_array ( $unique ) ) {
			    $labels = "";
			    for ($i = 0; $i < count($unique); $i++) {
                    $labels .= $i.",";
                }
                ob_start();
                ?>
                    <div style="width:100px; height:100%;">
                        <canvas id="wpMvmcloudSparkline" style="max-width:100%; max-height:100%;padding-top:4px; padding-bottom:4px;"></canvas>
                    </div>
                    <script>
                        function showWpMvmcloudSparkline() {
                            new Chart(document.getElementById('wpMvmcloudSparkline').getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: [<?php echo $labels; ?>],
                                    datasets: [
                                        {
                                            borderColor: "rgb(240, 240, 241)",
                                            backgroundColor: "rgb(240, 240, 241)",
                                            borderWidth:1,
                                            radius:0,
                                            data: [<?php echo implode(',', $unique); ?>]
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { enabled: false }
                                    },
                                    scales: {
                                        y: { display: false },
                                        x: { display: false }
                                    }
                                }
                            });
                        }
                        jQuery(showWpMvmcloudSparkline);
                    </script>
                <?php
                $content .= ob_get_contents();
                ob_end_clean();
				$url = $this->getStatsURL ();
			}
			$toolbar->add_menu ( array (
				'id' => 'wp-mvmcloud_stats',
				'title' => $content,
				'href' => $url
			) );
		}
	}

	/**
	 * Add plugin meta data
	 *
	 * @param array $links
	 *        	list of already defined plugin meta data
	 * @param string $file
	 *        	handled file
	 * @return array complete list of plugin meta data
	 */
	public function setPluginMeta($links, $file) {
		if ($file == 'wp-mvmcloud/wp-mvmcloud.php' && (!$this->isNetworkMode () || is_network_admin()) )
			return array_merge ( $links, array (
					sprintf ( '<a href="%s">%s</a>', self::getSettingsURL (), __ ( 'Settings', 'wp-mvmcloud' ) )
			) );
		return $links;
	}

	/**
	 * Prepare toolbar widget requirements
	 */
	public function loadToolbarRequirements() {
		if (is_admin_bar_showing ()) {
            wp_enqueue_script ( 'wp-mvmcloud-chartjs', $this->getPluginURL () . 'js/chartjs/chart.min.js', "3.4.1" );
		}
	}

	/**
	 * Add tracking pixels to feed content
	 *
	 * @param string $content
	 *        	post content
	 * @return string post content extended by tracking pixel
	 */
	public function addFeedTracking($content) {
		global $post;
		if (is_feed ()) {
			self::$logger->log ( 'Add tracking image to feed entry.' );
			if (! self::$settings->getOption ( 'site_id' )) {
				$siteId = $this->requestMvmcloudSiteId ();
				if ($siteId != 'n/a')
					self::$settings->setOption ( 'site_id', $siteId );
				else
					return false;
			}
			$title = the_title ( null, null, false );
			$posturl = get_permalink ( $post->ID );
			$urlref = get_bloginfo ( 'rss2_url' );
			if (self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy')
			    $url = plugins_url ( 'wp-mvmcloud' ) . '/proxy/mvmcloud.php';
            else {
                $url = self::$settings->getGlobalOption ( 'mvmcloud_url' );
                if (substr($url, -10, 10) == '/index.php')
                    $url = str_replace('/index.php', '/mvmcloud.php', $url);
                else
                    $url .= 'mvmcloud.php';
            }
			$trackingImage = $url . '?idsite=' . self::$settings->getOption ( 'site_id' ) . '&amp;rec=1&amp;url=' . urlencode ( $posturl ) . '&amp;action_name=' . urlencode ( $title ) . '&amp;urlref=' . urlencode ( $urlref );
			$content .= '<img src="' . $trackingImage . '" style="border:0;width:0;height:0" width="0" height="0" alt="" />';
		}
		return $content;
	}

	/**
	 * Add a campaign parameter to feed permalink
	 *
	 * @param string $permalink
	 *        	permalink
	 * @return string permalink extended by campaign parameter
	 */
	public function addFeedCampaign($permalink) {
		global $post;
		if (is_feed ()) {
			self::$logger->log ( 'Add campaign to feed permalink.' );
			$sep = (strpos ( $permalink, '?' ) === false ? '?' : '&');
			$permalink .= $sep . 'pk_campaign=' . urlencode ( self::$settings->getGlobalOption ( 'track_feed_campaign' ) ) . '&pk_kwd=' . urlencode ( $post->post_name );
		}
		return $permalink;
	}

	/**
	 * Forwards the cross domain parameter pk_vid if the URL parameter is set and a user is about to be redirected.
     * When another website links to WooCommerce with a pk_vid parameter, and WooCommerce redirects the user to another
     * URL, the pk_vid parameter would get lost and the visitorId would later not be applied by the tracking code
     * due to the lost pk_vid URL parameter. If the URL parameter is set, we make sure to forward this parameter.
	 *
	 * @param string $location
	 *
	 * @return string location extended by pk_vid URL parameter if the URL parameter is set
	 */
	public function forwardCrossDomainVisitorId($location) {

		if (!empty($_GET['pk_vid'])
			&& preg_match('/^[a-zA-Z0-9]{24,48}$/', $_GET['pk_vid'])) {
			// currently, the pk_vid parameter is 32 characters long, but it may vary over time.
			$location = add_query_arg( 'pk_vid', $_GET['pk_vid'], $location );
		}

		return $location;
	}

	/**
	 * Apply settings update
	 *
	 * @return boolean settings update applied
	 */
	private function applySettings() {
		self::$settings->applyChanges ( $_POST ['wp-mvmcloud'] );
		if (self::$settings->getGlobalOption ( 'auto_site_config' ) && self::isConfigured ()) {
			if ($this->isPHPMode () && ! defined ( 'MVMCLOUD_INCLUDE_PATH' ))
				self::defineMvmcloudConstants ();
			$siteId = $this->getMvmcloudSiteId ();
			$trackingCode = $this->updateTrackingCode ( $siteId );
			self::$settings->setOption ( 'site_id', $siteId );
		}
		self::$settings->setGlobalOption ( 'revision', self::$revisionId );
		self::$settings->setGlobalOption ( 'last_settings_update', time () );
		return true;
	}

	/**
	 * Check if WP-MVMCloud is configured
	 *
	 * @return boolean Is WP-MVMCloud configured?
	 */
	public static function isConfigured() {
		return (self::$settings->getGlobalOption ( 'mvmcloud_token' ) && (self::$settings->getGlobalOption ( 'mvmcloud_mode' ) != 'disabled') && (((self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'http') && (self::$settings->getGlobalOption ( 'mvmcloud_url' ))) || ((self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'php') && (self::$settings->getGlobalOption ( 'mvmcloud_path' ))) || ((self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'cloud') && (self::$settings->getGlobalOption ( 'mvmcloud_user' ))) || ((self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'cloud-mvmcloud') && (self::$settings->getGlobalOption ( 'mvmcloud_user' )))));
	}

	/**
	 * Check if WP-MVMCloud was updated
	 *
	 * @return boolean Was WP-MVMCloud updated?
	 */
	private function isUpdated() {
		return self::$settings->getGlobalOption ( 'revision' ) && self::$settings->getGlobalOption ( 'revision' ) < self::$revisionId;
	}

	/**
	 * Check if WP-MVMCloud is already installed
	 *
	 * @return boolean Is WP-MVMCloud installed?
	 */
	private function isInstalled() {
		$oldSettings = $this->getWordPressOption ( 'wp-mvmcloud_global-settings', false );
		if ($oldSettings && isset( $oldSettings['revision'] )) {
			self::log('Save old settings');
			self::$settings->setGlobalOption ( 'revision', $oldSettings['revision'] );
		} else self::log( 'Current revision '.self::$settings->getGlobalOption ( 'revision' ) );
		return self::$settings->getGlobalOption ( 'revision' ) > 0;
	}

	/**
	 * Check if new settings were submitted
	 *
	 * @return boolean Are new settings submitted?
	 */
	public static function isConfigSubmitted() {
		return isset ( $_POST ) && isset ( $_POST ['wp-mvmcloud'] ) && self::isValidOptionsPost();
	}

	/**
	 * Check if PHP mode is chosen
	 *
	 * @return Is PHP mode chosen?
	 */
	public function isPHPMode() {
		return self::$settings->getGlobalOption ( 'mvmcloud_mode' ) && self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'php';
	}

	/**
	 * Check if WordPress is running in network mode
	 *
	 * @return boolean Is WordPress running in network mode?
	 */
	public function isNetworkMode() {
		return self::$settings->checkNetworkActivation ();
	}

	/**
	 * Check if a WP-MVMCloud dashboard widget is enabled
	 *
	 * @return boolean Is a dashboard widget enabled?
	 */
	private function isDashboardActive() {
		return self::$settings->getGlobalOption ( 'dashboard_widget' ) || self::$settings->getGlobalOption ( 'dashboard_chart' ) || self::$settings->getGlobalOption ( 'dashboard_seo' );
	}

	/**
	 * Check if a WP-MVMCloud toolbar widget is enabled
	 *
	 * @return boolean Is a toolbar widget enabled?
	 */
	private function isToolbarActive() {
		return self::$settings->getGlobalOption ( 'toolbar' );
	}

	/**
	 * Check if WP-MVMCloud tracking code insertion is enabled
	 *
	 * @return boolean Insert tracking code?
	 */
	private function isTrackingActive() {
		return self::$settings->getGlobalOption ( 'track_mode' ) != 'disabled';
	}

	/**
	 * Check if admin tracking is enabled
	 *
	 * @return boolean Is admin tracking enabled?
	 */
	private function isAdminTrackingActive() {
		return self::$settings->getGlobalOption ( 'track_admin' ) && is_admin ();
	}

	/**
	 * Check if WP-MVMCloud noscript code insertion is enabled
	 *
	 * @return boolean Insert noscript code?
	 */
	private function isAddNoScriptCode() {
		return self::$settings->getGlobalOption ( 'track_noscript' );
	}

	/**
	 * Check if feed tracking is enabled
	 *
	 * @return boolean Is feed tracking enabled?
	 */
	private function isTrackFeed() {
		return self::$settings->getGlobalOption ( 'track_feed' );
	}

	/**
	 * Check if feed permalinks get a campaign parameter
	 *
	 * @return boolean Add campaign parameter to feed permalinks?
	 */
	private function isAddFeedCampaign() {
		return self::$settings->getGlobalOption ( 'track_feed_addcampaign' );
	}

	/**
	 * Check if feed permalinks get a campaign parameter
	 *
	 * @return boolean Add campaign parameter to feed permalinks?
	 */
	private function isCrossDomainLinkingEnabled() {
		return self::$settings->getGlobalOption ( 'track_crossdomain_linking' );
	}

	/**
	 * Check if WP-MVMCloud shortcodes are enabled
	 *
	 * @return boolean Are shortcodes enabled?
	 */
	private function isAddShortcode() {
		return self::$settings->getGlobalOption ( 'shortcodes' );
	}

	/**
	 * Define MVMCloud Analytics constants for PHP reporting API
	 */
	public static function defineMvmcloudConstants() {
		if (! defined ( 'MVMCLOUD_INCLUDE_PATH' )) {
            //@header('Content-type: text/html');
			define ( 'MVMCLOUD_INCLUDE_PATH', self::$settings->getGlobalOption ( 'mvmcloud_path' ) );
			define ( 'MVMCLOUD_USER_PATH', self::$settings->getGlobalOption ( 'mvmcloud_path' ) );
			define ( 'MVMCLOUD_ENABLE_DISPATCH', false );
			define ( 'MVMCLOUD_ENABLE_ERROR_HANDLER', false );
			define ( 'MVMCLOUD_ENABLE_SESSION_START', false );
		}
	}

	/**
	 * Start chosen logging method
	 */
	private function openLogger() {
		switch (WP_MVMCLOUD_ACTIVATE_LOGGER) {
			case 1 :
				self::$logger = new WP_MVMCloud\Logger\Screen ( __CLASS__ );
				break;
			case 2 :
				self::$logger = new WP_MVMCloud\Logger\File ( __CLASS__ );
				break;
			default :
				self::$logger = new WP_MVMCloud\Logger\Dummy ( __CLASS__ );
		}
	}

	/**
	 * Log a message
	 *
	 * @param string $message
	 *        	logger message
	 */
	public static function log($message) {
		self::$logger->log ( $message );
	}

	/**
	 * End logging
	 */
	private function closeLogger() {
		self::$logger = null;
	}

	/**
	 * Load WP-MVMCloud settings
	 */
	private function openSettings() {
		self::$settings = new WP_MVMCloud\Settings ( $this, self::$logger );
		if (! $this->isConfigSubmitted () && $this->isPHPMode () && ! defined ( 'MVMCLOUD_INCLUDE_PATH' ))
			self::defineMvmcloudConstants ();
	}

	/**
	 * Include a WP-MVMCloud file
	 */
	private function includeFile($strFile) {
		self::$logger->log ( 'Include ' . $strFile . '.php' );
		if (WP_MVMCLOUD_PATH . $strFile . '.php')
			include (WP_MVMCLOUD_PATH . $strFile . '.php');
	}

	/**
	 * Check if user should not be tracked
	 *
	 * @return boolean Do not track user?
	 */
	private function isHiddenUser() {
		if (is_multisite ())
			foreach ( self::$settings->getGlobalOption ( 'capability_stealth' ) as $key => $val )
				if ($val && current_user_can ( $key ))
					return true;
		return current_user_can ( 'wp-mvmcloud_stealth' );
	}

	/**
	 * Check if tracking code is up to date
	 *
	 * @return boolean Is tracking code up to date?
	 */
	public function isCurrentTrackingCode() {
		return (self::$settings->getOption ( 'last_tracking_code_update' ) && self::$settings->getOption ( 'last_tracking_code_update' ) > self::$settings->getGlobalOption ( 'last_settings_update' ));
	}

	/**
	 * DEPRECTAED Add javascript code to site header
	 *
	 * @deprecated
	 *
	 */
	public function site_header() {
		self::$logger->log ( 'Using deprecated function site_header' );
		$this->addJavascriptCode ();
	}

	/**
	 * DEPRECTAED Add javascript code to site footer
	 *
	 * @deprecated
	 *
	 */
	public function site_footer() {
		self::$logger->log ( 'Using deprecated function site_footer' );
		$this->addNoscriptCode ();
	}

	/**
	 * Identify new posts if an annotation is required
	 * and create MVMCloud Analytics annotation
	 *
	 * @param string $newStatus
	 *        	new post status
	 * @param strint $oldStatus
	 *        	new post status
	 * @param object $post
	 *        	current post object
	 */
	public function addMvmcloudAnnotation($newStatus, $oldStatus, $post) {
	    $enabledPostTypes = self::$settings->getGlobalOption ( 'add_post_annotations' );
		if (isset($enabledPostTypes[$post->post_type]) && $enabledPostTypes[$post->post_type] && $newStatus == 'publish' && $oldStatus != 'publish') {
			$note = 'Published: ' . $post->post_title . ' - URL: ' . get_permalink ( $post->ID );
			$id = WP_MVMCloud\Request::register ( 'Annotations.add', array (
				'idSite' => $this->getMvmcloudSiteId (),
				'date' => date ( 'Y-m-d' ),
				'note' => $note
			) );
			$result = $this->request ( $id );
			self::$logger->log ( 'Add post annotation. ' . $note . ' - ' . serialize ( $result ) );
		}
	}

	/**
	 * Get WP-MVMCloud's URL
	 */
	public function getPluginURL() {
		return trailingslashit ( plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Get WP-MVMCloud's version
	 */
	public function getPluginVersion() {
		return self::$version;
	}

	/**
	 * Enable three columns for WP-MVMCloud stats screen
	 *
	 * @param
	 *        	array full list of column settings
	 * @param
	 *        	mixed current screen id
	 * @return array updated list of column settings
	 */
	public function onScreenLayoutColumns($columns, $screen) {
		if (isset( $this->statsPageId ) && $screen == $this->statsPageId)
			$columns [$this->statsPageId] = 3;
		return $columns;
	}

	/**
	 * Add tracking code to admin header
	 */
	function addAdminHeaderTracking() {
		$this->addJavascriptCode ();
	}

	/**
	 * Get option value
	 *
	 * @param string $key
	 *        	option key
	 * @return mixed option value
	 */
	public function getOption($key) {
		return self::$settings->getOption ( $key );
	}

	/**
	 * Get global option value
	 *
	 * @param string $key
	 *        	global option key
	 * @return mixed global option value
	 */
	public function getGlobalOption($key) {
		return self::$settings->getGlobalOption ( $key );
	}

	/**
	 * Get stats page URL
	 *
	 * @return string stats page URL
	 */
	public function getStatsURL() {
		return admin_url () . '?page=wp-mvmcloud_stats';
	}

	/**
	 * Execute WP-MVMCloud test script
	 */
	private function loadTestscript() {
		$this->includeFile ( 'debug' . DIRECTORY_SEPARATOR . 'testscript' );
	}

	/**
	 * Echo an error message
	 *
	 * @param string $message
	 *        	message content
	 */
	private static function showErrorMessage($message) {
		echo '<strong class="wp-mvmcloud-error">' . __ ( 'An error occured', 'wp-mvmcloud' ) . ':</strong> ' . $message . ' [<a href="' . (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general') . '.php?page=wp-mvmcloud/classes/WP_MVMCloud.php&tab=support">' . __ ( 'Support', 'wp-mvmcloud' ) . '</a>]';
	}

	/**
	 * Perform a MVMCloud Analytics request
	 *
	 * @param string $id
	 *        	request ID
	 * @return mixed request result
	 */
	public function request($id, $debug = false) {
		if ( self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'disabled' )
			return 'n/a';
		if (! isset ( self::$request ) || empty ( self::$request ))
			self::$request = (self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'http' || self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'cloud' || self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'cloud-mvmcloud' ? new WP_MVMCloud\Request\Rest ( $this, self::$settings ) : new WP_MVMCloud\Request\Php ( $this, self::$settings ));
		if ($debug)
			return self::$request->getDebug ( $id );
		return self::$request->perform ( $id );
	}

	/**
	 * Reset request object
	 */
	public function resetRequest() {
		if (is_object(self::$request))
			self::$request->reset();
		self::$request = NULL;
	}

	/**
	 * Execute WP-MVMCloud shortcode
	 *
	 * @param array $attributes
	 *        	attribute list
	 */
	public function shortcode($attributes) {
		shortcode_atts ( array (
				'title' => '',
				'module' => 'overview',
				'period' => 'day',
				'date' => 'yesterday',
				'limit' => 10,
				'width' => '100%',
				'height' => '200px',
				'idsite' => '',
				'language' => 'en',
				'range' => false,
				'key' => 'sum_daily_nb_uniq_visitors'
		), $attributes );
		$shortcodeObject = new \WP_MVMCloud\Shortcode ( $attributes, $this, self::$settings );
		return $shortcodeObject->get();
	}

	/**
	 * Get MVMCloud Analytics site ID by blog ID
	 *
	 * @param int $blogId
	 *        	which blog's MVMCloud Analytics site ID to get, default is the current blog
	 * @return mixed MVMCloud Analytics site ID or n/a
	 */
	public function getMvmcloudSiteId($blogId = null) {
		if (! $blogId && $this->isNetworkMode ())
			$blogId = get_current_blog_id ();
		$result = self::$settings->getOption ( 'site_id', $blogId );
        self::$logger->log ( 'Database result: ' . $result );
        return (! empty ( $result ) ? $result : $this->requestMvmcloudSiteId ( $blogId ));
	}

	/**
	 * Get a detailed list of all MVMCloud Analytics sites
	 *
	 * @return array MVMCloud Analytics sites
	 */
	public function getMvmcloudSiteDetails() {
		$id = WP_MVMCloud\Request::register ( 'SitesManager.getSitesWithAtLeastViewAccess', array () );
		$mvmcloudSiteDetails = $this->request ( $id );
		return $mvmcloudSiteDetails;
	}

	/**
	 * Estimate a MVMCloud Analytics site ID by blog ID
	 *
	 * @param int $blogId
	 *        	which blog's MVMCloud Analytics site ID to estimate, default is the current blog
	 * @return mixed MVMCloud Analytics site ID or n/a
	 */
	private function requestMvmcloudSiteId($blogId = null) {
		$isCurrent = ! self::$settings->checkNetworkActivation () || empty ( $blogId );
		if (self::$settings->getGlobalOption ( 'auto_site_config' )) {
			$id = WP_MVMCloud\Request::register ( 'SitesManager.getSitesIdFromSiteUrl', array (
					'url' => $isCurrent ? get_bloginfo ( 'url' ) : get_blog_details ( $blogId )->siteurl
			) );
			$result = $this->request ( $id );
			$this->log ( 'Tried to identify current site, result: ' . serialize ( $result ) );
			if (is_array( $result ) && empty( $result ))
				$result = $this->addMvmcloudSite ( $blogId );
			elseif ( $result != 'n/a' && isset($result [0]) )
				$result = $result [0] ['idsite'];
			else $result = null;
		} else $result = null;
		self::$logger->log ( 'Get MVMCloud Analytics ID: WordPress site ' . ($isCurrent ? get_bloginfo ( 'url' ) : get_blog_details ( $blogId )->siteurl) . ' = MVMCloud Analytics ID ' . $result );
		if ($result !== null) {
			self::$settings->setOption ( 'site_id', $result, $blogId );
			if (self::$settings->getGlobalOption ( 'track_mode' ) != 'disabled' && self::$settings->getGlobalOption ( 'track_mode' ) != 'manually') {
				$code = $this->updateTrackingCode ( $result, $blogId );
			}
			$this::$settings->save ();
			return $result;
		}
		return 'n/a';
	}

	/**
	 * Add a new MVMCloud Analytics
	 *
	 * @param int $blogId
	 *        	which blog's MVMCloud Analytics site to create, default is the current blog
	 * @return int MVMCloud Analytics site ID
	 */
	public function addMvmcloudSite($blogId = null) {
		$isCurrent = ! self::$settings->checkNetworkActivation () || empty ( $blogId );
		// Do not add site if MVMCloud Analytics connection is unreliable
		if (! $this->request ( 'global.getMvmcloudVersion' ))
			return null;
		$id = WP_MVMCloud\Request::register ( 'SitesManager.addSite', array (
				'urls' => $isCurrent ? get_bloginfo ( 'url' ) : get_blog_details ( $blogId )->siteurl,
				'siteName' => urlencode( $isCurrent ? get_bloginfo ( 'name' ) : get_blog_details ( $blogId )->blogname )
		) );
		$result = $this->request ( $id );
		if ( is_array( $result ) && isset( $result['value'] ) ) {
			$result = (int) $result['value'];
		} else {
			$result = (int) $result;
		}
		self::$logger->log ( 'Create MVMCloud Analytics ID: WordPress site ' . ($isCurrent ? get_bloginfo ( 'url' ) : get_blog_details ( $blogId )->siteurl) . ' = MVMCloud Analytics ID ' . $result );
		if (empty ( $result ))
			return null;
		else {
            do_action('wp-mvmcloud_site_created', $result);
            return $result;
        }
	}

	/**
	 * Update a MVMCloud Analytics site's detail information
	 *
	 * @param int $siteId
	 *        	which MVMCloud Analytics site to updated
	 * @param int $blogId
	 *        	which blog's MVMCloud Analytics site ID to get, default is the current blog
	 */
	private function updateMvmcloudSite($siteId, $blogId = null) {
		$isCurrent = ! self::$settings->checkNetworkActivation () || empty ( $blogId );
		$id = WP_MVMCloud\Request::register ( 'SitesManager.updateSite', array (
				'idSite' => $siteId,
				'urls' => $isCurrent ? get_bloginfo ( 'url' ) : get_blog_details ( $blogId )->siteurl,
				'siteName' => $isCurrent ? get_bloginfo ( 'name' ) : get_blog_details ( $blogId )->blogname
		) );
		$this->request ( $id );
		self::$logger->log ( 'Update MVMCloud Analytics site: WordPress site ' . ($isCurrent ? get_bloginfo ( 'url' ) : get_blog_details ( $blogId )->siteurl) );
	}

	/**
	 * Update a site's tracking code
	 *
	 * @param int $siteId
	 *        	which MVMCloud Analytics site to updated
	 * @param int $blogId
	 *        	which blog's MVMCloud Analytics site ID to get, default is the current blog
	 * @return string tracking code
	 */
	public function updateTrackingCode($siteId = false, $blogId = null) {
		if (!$siteId)
			$siteId = $this->getMvmcloudSiteId ();
		if (self::$settings->getGlobalOption ( 'track_mode' ) == 'disabled' || self::$settings->getGlobalOption ( 'track_mode' ) == 'manually')
			return false;
		$id = WP_MVMCloud\Request::register ( 'SitesManager.getJavascriptTag', array (
				'idSite' => $siteId,
				'mergeSubdomains' => self::$settings->getGlobalOption ( 'track_across' ) ? 1 : 0,
				'mergeAliasUrls' => self::$settings->getGlobalOption ( 'track_across_alias' ) ? 1 : 0,
				'disableCookies' => self::$settings->getGlobalOption ( 'disable_cookies' ) ? 1 : 0,
				'crossDomain' => self::$settings->getGlobalOption ( 'track_crossdomain_linking' ) ? 1 : 0,
                'trackNoScript' => 1
			) );
		$code = $this->request ( $id );
		if (is_array($code) && isset($code['value']))
			$code = $code['value'];
		$result = !is_array ( $code ) ? html_entity_decode ( $code ) : '<!-- '.json_encode($code).' -->';
		self::$logger->log ( 'Delivered tracking code: ' . $result );
		$result = WP_MVMCloud\TrackingCode::prepareTrackingCode ( $result, self::$settings, self::$logger, true );
		if (isset ( $result ['script'] ) && ! empty ( $result ['script'] )) {
			self::$settings->setOption ( 'tracking_code', $result ['script'], $blogId );
			self::$settings->setOption ( 'noscript_code', $result ['noscript'], $blogId );
			self::$settings->setGlobalOption ( 'proxy_url', $result ['proxy'] );
		}
		return $result;
	}

	/**
	 * Update MVMCloud Analytics site if blog name changes
	 *
	 * @param string $oldValue
	 *        	old blog name
	 * @param string $newValue
	 *        	new blog name
	 */
	public function onBlogNameChange($oldValue, $newValue = null) {
		$this->updateMvmcloudSite ( self::$settings->getOption ( 'site_id' ) );
	}

	/**
	 * Update MVMCloud Analytics site if blog URL changes
	 *
	 * @param string $oldValue
	 *        	old blog URL
	 * @param string $newValue
	 *        	new blog URL
	 */
	public function onSiteUrlChange($oldValue, $newValue = null) {
		$this->updateMvmcloudSite ( self::$settings->getOption ( 'site_id' ) );
	}

	/**
	 * Register stats page meta boxes
	 *
	 * @param mixed $statsPageId
	 *        	WordPress stats page ID
	 */
	public function onloadStatsPage($statsPageId) {
		if (self::$settings->getGlobalOption ( 'disable_timelimit' ))
			set_time_limit ( 0 );
		wp_enqueue_script ( 'common' );
		wp_enqueue_script ( 'wp-lists' );
		wp_enqueue_script ( 'postbox' );
		wp_enqueue_script ( 'wp-mvmcloud', $this->getPluginURL() . 'js/wp-mvmcloud.js', array (), self::$version, true );
		wp_enqueue_script ( 'wp-mvmcloud-chartjs', $this->getPluginURL () . 'js/chartjs/chart.min.js', "3.4.1" );
		new \WP_MVMCloud\Widget\Chart ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Visitors ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Overview ( $this, self::$settings, $this->statsPageId );
        if (self::$settings->getGlobalOption ( 'stats_ecommerce' )) {
            new \WP_MVMCloud\Widget\Ecommerce ($this, self::$settings, $this->statsPageId);
            new \WP_MVMCloud\Widget\Items ($this, self::$settings, $this->statsPageId);
            new \WP_MVMCloud\Widget\ItemsCategory ($this, self::$settings, $this->statsPageId);
        }
		if (self::$settings->getGlobalOption ( 'stats_seo' ))
			new \WP_MVMCloud\Widget\Seo ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Pages ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Keywords ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Referrers ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Plugins ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Search ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Noresult ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Browsers ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\BrowserDetails ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Screens ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Types ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Models ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Systems ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\SystemDetails ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\City ( $this, self::$settings, $this->statsPageId );
		new \WP_MVMCloud\Widget\Country ( $this, self::$settings, $this->statsPageId );
	}

	/**
	 * Add per post statistics to a post's page
	 *
	 * @param mixed $postPageId
	 *        	WordPress post page ID
	 */
	public function onloadPostPage($postPageId) {
		global $post;
		$postUrl = get_permalink ( $post->ID );
		$this->log ( 'Load per post statistics: ' . $postUrl );
        $locations = apply_filters( 'wp-mvmcloud_meta_boxes_locations', get_post_types( array( 'public' => true ), 'names' ) );
		array (
				new Post ( $this, self::$settings, $locations, 'side', 'default', array (
				        'date' => self::$settings->getGlobalOption ( 'perpost_stats' ),
						'period' => 'day',
						'url' => $postUrl
				) ),
				'show'
		);
	}

	/**
	 * Stats page changes by POST submit
	 *
	 * @see http://tinyurl.com/5r5vnzs
	 */
	function onStatsPageSaveChanges() {
		if (! current_user_can ( 'manage_options' ))
			wp_die ( __ ( 'Cheatin&#8217; uh?' ) );
		check_admin_referer ( 'wp-mvmcloud_stats' );
		wp_redirect ( $_POST ['_wp_http_referer'] );
	}

	/**
	 * Get option value, choose method depending on network mode
	 *
	 * @param string $option option key
	 * @return string|array option value
	 */
	private function getWordPressOption($option, $default = null) {
		return ($this->isNetworkMode () ? get_site_option ( $option, $default ) : get_option ( $option, $default ));
	}

	/**
	 * Delete option, choose method depending on network mode
	 *
	 * @param string $option option key
	 */
	private function deleteWordPressOption($option) {
		if ( $this->isNetworkMode () )
			delete_site_option ( $option );
		else
			delete_option ( $option );
	}

	/**
	 * Set option value, choose method depending on network mode
	 *
	 * @param string $option option key
	 * @param mixed $value option value
	 */
	private function updateWordPressOption($option, $value) {
		if ( $this->isNetworkMode () )
			update_site_option ( $option, $value );
		else
			update_option ( $option, $value );
	}

	/**
	 * Check if WP-MVMCloud options page
	 *
	 * @return boolean True if current page is WP-MVMCloud's option page
	 */
	public static function isValidOptionsPost() {
		return is_admin() && check_admin_referer( 'wp-mvmcloud_settings' ) && current_user_can( 'manage_options' ) ;
	}
}
