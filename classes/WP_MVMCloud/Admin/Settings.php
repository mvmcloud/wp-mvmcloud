<?php

namespace WP_MVMCloud\Admin;

/**
 *
 */
class Settings extends \WP_MVMCloud\Admin {

	/**
	 * Builds and displays the settings page
	 */
	public function show() {
		if (isset($_GET['sitebrowser']) && $_GET['sitebrowser']) {
			new \WP_MVMCloud\Admin\Sitebrowser(self::$wpMvmcloud);
			return;
		}
		if (isset($_GET['clear']) && $_GET['clear'] && check_admin_referer()) {
			$this->clear($_GET['clear'] == 2);
			self::$wpMvmcloud->resetRequest();
			echo '<form method="post" action="?page='.htmlentities($_GET['page']).'"><input type="submit" value="'.__('Reload', 'wp-mvmcloud').'" /></form>';
			return;
		} elseif (self::$wpMvmcloud->isConfigSubmitted()) {
			$this->showBox ( 'updated', 'yes', __ ( 'Changes saved.' ) );
			self::$wpMvmcloud->resetRequest();
			self::$wpMvmcloud->updateTrackingCode();
		}
		global $wp_roles;
		?>
<div id="plugin-options-wrap" class="widefat">
	<?php
		echo $this->getHeadline ( 1, 'admin-generic', 'Settings', true );
		if (isset($_GET['testscript']) && $_GET['testscript'])
			$this->runTestscript();
	?>
	<?php
		if (self::$wpMvmcloud->isConfigured ()) {
			$mvmcloudVersion = self::$wpMvmcloud->request ( 'global.getMvmcloudVersion' );
			if (is_array ( $mvmcloudVersion ) && isset( $mvmcloudVersion['value'] ))
				$mvmcloudVersion = $mvmcloudVersion['value'];
		}
	?>
	<form method="post" action="?page=<?php echo htmlentities($_GET['page']); ?>">
		<input type="hidden" name="wp-mvmcloud[revision]" value="<?php echo self::$settings->getGlobalOption('revision'); ?>" />
		<?php wp_nonce_field('wp-mvmcloud_settings'); ?>
		<table class="wp-mvmcloud-form">
			<tbody>
			<?php
		$submitButton = '<tr><td colspan="2"><p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . esc_attr__ ( 'Save Changes' ) . '" /></p></td></tr>';
		printf ( '<tr><td colspan="2">%s</td></tr>', __ ( 'Thanks for using WP-MVMCloud!', 'wp-mvmcloud' ) );
		if (self::$wpMvmcloud->isConfigured ()) {
			if (! empty ( $mvmcloudVersion ) && !is_array( $mvmcloudVersion )) {
				$this->showText ( sprintf ( __ ( 'WP-MVMCloud %s is successfully connected to MVMCloud Analytics %s.', 'wp-mvmcloud' ), self::$wpMvmcloud->getPluginVersion (), $mvmcloudVersion ) . ' ' . (! self::$wpMvmcloud->isNetworkMode () ? sprintf ( __ ( 'You are running WordPress %s.', 'wp-mvmcloud' ), get_bloginfo ( 'version' ) ) : sprintf ( __ ( 'You are running a WordPress %s blog network (WPMU). WP-MVMCloud will handle your sites as different websites.', 'wp-mvmcloud' ), get_bloginfo ( 'version' ) )) );
			} else {
				$errorMessage = \WP_MVMCloud\Request::getLastError();
				if ( empty( $errorMessage ) )
					$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-MVMCloud %s was not able to connect to MVMCloud Analytics using your configuration. Check the &raquo;Connect to MVMCloud Analytics&laquo; section below.', 'wp-mvmcloud' ), self::$wpMvmcloud->getPluginVersion () ) );
				else
					$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-MVMCloud %s was not able to connect to MVMCloud Analytics using your configuration. During connection the following error occured: <br /><code>%s</code>', 'wp-mvmcloud' ), self::$wpMvmcloud->getPluginVersion (), $errorMessage ) );
			}
		} else
			$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-MVMCloud %s has to be connected to MVMCloud Analytics first. Check the &raquo;Connect to MVMCloud Analytics&laquo; section below.', 'wp-mvmcloud' ), self::$wpMvmcloud->getPluginVersion () ) );

		$tabs ['connect'] = array (
				'icon' => 'admin-plugins',
				'name' => __('Connect to MVMCloud Analytics', 'wp-mvmcloud')
		);
		if (self::$wpMvmcloud->isConfigured ()) {
			$tabs ['statistics'] = array (
					'icon' => 'chart-pie',
					'name' => __('Show Statistics', 'wp-mvmcloud')
			);
			$tabs ['tracking'] = array (
					'icon' => 'location-alt',
					'name' => __('Enable Tracking', 'wp-mvmcloud')
			);
		}
		$tabs ['expert'] = array (
				'icon' => 'shield',
				'name' => __('Expert Settings', 'wp-mvmcloud')
		);
		$tabs ['support'] = array (
				'icon' => 'lightbulb',
				'name' => __('Support', 'wp-mvmcloud')
		);

		echo '<tr><td colspan="2"><h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $details ) {
			$class = ($tab == 'connect') ? ' nav-tab-active' : '';
			echo '<a style="cursor:pointer;" id="tab-' . $tab . '" class="nav-tab' . $class . '" onclick="javascript:jQuery(\'table.wp-mvmcloud_menu-tab\').addClass(\'hidden\');jQuery(\'#' . $tab . '\').removeClass(\'hidden\');jQuery(\'a.nav-tab\').removeClass(\'nav-tab-active\');jQuery(\'#tab-' . $tab . '\').addClass(\'nav-tab-active\');">';
			$this->showHeadline ( 0, $details ['icon'], $details ['name'] );
			echo "</a>";
		}
		echo '</h2></td></tr></tbody></table><table id="connect" class="wp-mvmcloud_menu-tab"><tbody>';

		if (! self::$wpMvmcloud->isConfigured ())
			$this->showBox ( 'updated', 'info', __('WP-MVMCloud is a WordPress plugin to show a selection of MVMCloud Analytics stats in your WordPress admin dashboard and to add and configure your MVMCloud Analytics tracking code. To use this you will need your own MVMCloud Analytics instance. If you do not already have a MVMCloud Analytics setup, you can purchase a subscription at https://www.mvmcloud.net/analytics', 'wp-mvmcloud') );

		if (! function_exists ( 'curl_init' ) && ! ini_get ( 'allow_url_fopen' ))
			$this->showBox ( 'error', 'no', __ ('Neither cURL nor fopen are available. So WP-MVMCloud can not use the HTTP API and not connect to MVMCloud Analytics Cloud.') );

		//$description = sprintf ( '%s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s', __ ( 'You can choose between three connection methods:', 'wp-mvmcloud' ), __ ( 'Self-hosted (HTTP API, default)', 'wp-mvmcloud' ), __ ( 'This is the default option for a self-hosted MVMCloud Analytics and should work for most configurations. WP-MVMCloud will connect to MVMCloud Analytics using http(s).', 'wp-mvmcloud' ), __ ( 'Self-hosted (PHP API)', 'wp-mvmcloud' ), __ ( 'Choose this, if your self-hosted MVMCloud Analytics and WordPress are running on the same machine and you know the full server path to your MVMCloud Analytics instance.', 'wp-mvmcloud' ), __ ( 'Cloud-hosted', 'wp-mvmcloud' ), __ ( 'If you are using a cloud-hosted MVMCloud Analytics by InnoCraft, you can simply use this option. Be carefull to choose the option which fits to your cloud domain (mvmcloud.cloud or innocraft.cloud).', 'wp-mvmcloud' ) );
        $description = sprintf ( '%s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s',
            __ ( 'You can choose to enable/disable connection with MVMCloud Analytics.', 'wp-mvmcloud' ),
            __ ( 'Enable (HTTP API, default)', 'wp-mvmcloud' ),
            __ ( 'This is the default option for MVMCloud Analytics and should work for most configurations. WP-MVMCloud will connect to MVMCloud Analytics using http(s).', 'wp-mvmcloud' ),
            __ ( 'Disable', 'wp-mvmcloud' ),
            __ ( 'Choose this to disable WP-MVMCloud connection to MVMCloud Analytics.', 'wp-mvmcloud' ));
		$this->showSelect ( 'mvmcloud_mode', __ ( 'Mvmcloud Mode', 'wp-mvmcloud' ), array (
				'disabled' => __ ( 'Disable (WP-MVMCloud will not connect to MVMCloud Analytics)', 'wp-mvmcloud' ),
				'http' => __ ( 'Enable (HTTP API, default)', 'wp-mvmcloud' )
				//'php' => __ ( 'Self-hosted (PHP API)', 'wp-mvmcloud' ),
		), $description, 'jQuery(\'tr.wp-mvmcloud-mode-option\').addClass(\'hidden\'); jQuery(\'#wp-mvmcloud-mode-option-\' + jQuery(\'#mvmcloud_mode\').val()).removeClass(\'hidden\');', false, '', self::$wpMvmcloud->isConfigured () );

		$this->showInput ( 'mvmcloud_url', __ ( 'MVMCloud Analytics URL', 'wp-mvmcloud' ), __( 'Enter your MVMCloud Analytics URL. This is the same URL you use to access your MVMCloud Analytics instance, e.g. https://analytics.mvmcloud.net/.', 'wp-mvmcloud' ), self::$settings->getGlobalOption ( 'mvmcloud_mode' ) != 'http', 'wp-mvmcloud-mode-option', 'http', self::$wpMvmcloud->isConfigured (), true );
		$this->showInput ( 'mvmcloud_path', __ ( 'Mvmcloud path', 'wp-mvmcloud' ), __( 'Enter the file path to your MVMCloud Analytics instance, e.g. /var/www/mvmcloud/.', 'wp-mvmcloud' ), self::$settings->getGlobalOption ( 'mvmcloud_mode' ) != 'php', 'wp-mvmcloud-mode-option', 'php', self::$wpMvmcloud->isConfigured (), true );
		$this->showInput ( 'mvmcloud_token', __ ( 'Auth token', 'wp-mvmcloud' ), __( 'Enter your MVMCloud Analytics auth token here. It is an alphanumerical code like 0a1b2c34d56e78901fa2bc3d45678efa.', 'wp-mvmcloud' ).' '.sprintf ( __ ( 'See %sWP-MVMCloud FAQ%s.', 'wp-mvmcloud' ), '<a href="https://wordpress.org/plugins/wp-mvmcloud/faq/" target="_BLANK">', '</a>' ), false, '', '', self::$wpMvmcloud->isConfigured (), true );

		// Site configuration
		$mvmcloudSiteId = self::$wpMvmcloud->isConfigured () ? self::$wpMvmcloud->getMvmcloudSiteId () : false;
		if (! self::$wpMvmcloud->isNetworkMode() ) {
			$this->showCheckbox ( 'auto_site_config', __ ( 'Auto config', 'wp-mvmcloud' ), __ ( 'Check this to automatically choose your blog from your MVMCloud Analytics sites by URL. If your blog is not added to MVMCloud Analytics yet, WP-MVMCloud will add a new site.', 'wp-mvmcloud' ), false, 'jQuery(\'tr.wp-mvmcloud-auto-option\').toggle(\'hidden\');' . ($mvmcloudSiteId ? 'jQuery(\'#site_id\').val(' . $mvmcloudSiteId . ');' : '') );
			if (self::$wpMvmcloud->isConfigured ()) {
				$mvmcloudSiteList = self::$wpMvmcloud->getMvmcloudSiteDetails ();
				if (isset($mvmcloudSiteList['result']) && $mvmcloudSiteList['result'] == 'error') {
					$this->showBox ( 'error', 'no', sprintf ( __ ( 'WP-MVMCloud %s was not able to get sites with at least view access: <br /><code>%s</code>', 'wp-mvmcloud' ), self::$wpMvmcloud->getPluginVersion (), $errorMessage ) );
				} else {
					if (is_array($mvmcloudSiteList))
						foreach ($mvmcloudSiteList as $details)
							$mvmcloudSiteDetails[$details['idsite']] = $details;
					unset($mvmcloudSiteList);
					if ($mvmcloudSiteId != 'n/a' && isset($mvmcloudSiteDetails) && is_array($mvmcloudSiteDetails))
						$mvmcloudSiteDescription = $mvmcloudSiteDetails [$mvmcloudSiteId] ['name'] . ' (' . $mvmcloudSiteDetails [$mvmcloudSiteId] ['main_url'] . ')';
					else
						$mvmcloudSiteDescription = 'n/a';
					echo '<tr class="wp-mvmcloud-auto-option' . (!self::$settings->getGlobalOption('auto_site_config') ? ' hidden' : '') . '"><th scope="row">' . __('Determined site', 'wp-mvmcloud') . ':</th><td>' . $mvmcloudSiteDescription . '</td></tr>';
					if (isset ($mvmcloudSiteDetails) && is_array($mvmcloudSiteDetails))
						foreach ($mvmcloudSiteDetails as $key => $siteData)
							$siteList [$siteData['idsite']] = $siteData ['name'] . ' (' . $siteData ['main_url'] . ')';
					if (isset($siteList))
						$this->showSelect('site_id', __('Select site', 'wp-mvmcloud'), $siteList, 'Choose the MVMCloud Analytics site corresponding to this blog.', '', self::$settings->getGlobalOption('auto_site_config'), 'wp-mvmcloud-auto-option', true, false);
				}
			}
		} else echo '<tr class="hidden"><td colspan="2"><input type="hidden" name="wp-mvmcloud[auto_site_config]" value="1" /></td></tr>';

		echo $submitButton;

		echo '</tbody></table><table id="statistics" class="wp-mvmcloud_menu-tab hidden"><tbody>';
		// Stats configuration
		$this->showSelect ( 'default_date', __ ( 'Mvmcloud default date', 'wp-mvmcloud' ), array (
				'today' => __ ( 'Today', 'wp-mvmcloud' ),
				'yesterday' => __ ( 'Yesterday', 'wp-mvmcloud' ),
				'current_month' => __ ( 'Current month', 'wp-mvmcloud' ),
				'last_month' => __ ( 'Last month', 'wp-mvmcloud' ),
				'current_week' => __ ( 'Current week', 'wp-mvmcloud' ),
				'last_week' => __ ( 'Last week', 'wp-mvmcloud' )
		), __ ( 'Default date shown on statistics page.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'stats_seo', __ ( 'Show SEO data', 'wp-mvmcloud' ), __ ( 'Display SEO ranking data on statistics page.', 'wp-mvmcloud' ) . ' (' . __ ( 'Slow!', 'wp-mvmcloud' ) . ')' );
        $this->showCheckbox ( 'stats_ecommerce', __ ( 'Show e-commerce data', 'wp-mvmcloud' ), __ ( 'Display e-commerce data on statistics page.', 'wp-mvmcloud' ) );

		$this->showSelect ( 'dashboard_widget', __ ( 'Dashboard overview', 'wp-mvmcloud' ), array (
				'disabled' => __ ( 'Disabled', 'wp-mvmcloud' ),
				'yesterday' => __ ( 'Yesterday', 'wp-mvmcloud' ),
				'today' => __ ( 'Today', 'wp-mvmcloud' ),
				'last30' => __ ( 'Last 30 days', 'wp-mvmcloud' ),
                'last60' => __ ( 'Last 60 days', 'wp-mvmcloud' ),
                'last90' => __ ( 'Last 90 days', 'wp-mvmcloud' )
		), __ ( 'Enable WP-MVMCloud dashboard widget &quot;Overview&quot;.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'dashboard_chart', __ ( 'Dashboard graph', 'wp-mvmcloud' ), __ ( 'Enable WP-MVMCloud dashboard widget &quot;Graph&quot;.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'dashboard_seo', __ ( 'Dashboard SEO', 'wp-mvmcloud' ), __ ( 'Enable WP-MVMCloud dashboard widget &quot;SEO&quot;.', 'wp-mvmcloud' ) . ' (' . __ ( 'Slow!', 'wp-mvmcloud' ) . ')' );

        $this->showCheckbox ( 'dashboard_ecommerce', __ ( 'Dashboard e-commerce', 'wp-mvmcloud' ), __ ( 'Enable WP-MVMCloud dashboard widget &quot;E-commerce&quot;.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'toolbar', __ ( 'Show graph on WordPress Toolbar', 'wp-mvmcloud' ), __ ( 'Display a last 30 days visitor graph on WordPress\' toolbar.', 'wp-mvmcloud' ) );

		echo '<tr><th scope="row"><label for="capability_read_stats">' . __ ( 'Display stats to', 'wp-mvmcloud' ) . '</label>:</th><td>';
		$filter = self::$settings->getGlobalOption ( 'capability_read_stats' );
		foreach ( $wp_roles->role_names as $key => $name ) {
			echo '<input type="checkbox" ' . (isset ( $filter [$key] ) && $filter [$key] ? 'checked="checked" ' : '') . 'value="1" onchange="jQuery(\'#capability_read_stats-' . $key . '-input\').val(this.checked?1:0);" />';
			echo '<input id="capability_read_stats-' . $key . '-input" type="hidden" name="wp-mvmcloud[capability_read_stats][' . $key . ']" value="' . ( int ) (isset ( $filter [$key] ) && $filter [$key]) . '" />';
			echo $name . ' &nbsp; ';
		}
		echo '<span class="dashicons dashicons-editor-help" onclick="jQuery(\'#capability_read_stats-desc\').toggleClass(\'hidden\');"></span> <p class="description hidden" id="capability_read_stats-desc">' . __ ( 'Choose user roles allowed to see the statistics page.', 'wp-mvmcloud' ) . '</p></td></tr>';

        $this->showSelect ( 'perpost_stats', __ ( 'Show per post stats', 'wp-mvmcloud' ), array (
            'disabled' => __ ( 'Disabled', 'wp-mvmcloud' ),
            'yesterday' => __ ( 'Yesterday', 'wp-mvmcloud' ),
            'today' => __ ( 'Today', 'wp-mvmcloud' ),
            'last30' => __ ( 'Last 30 days', 'wp-mvmcloud' ),
            'last60' => __ ( 'Last 60 days', 'wp-mvmcloud' ),
            'last90' => __ ( 'Last 90 days', 'wp-mvmcloud' )
        ), __ ( 'Show stats about single posts at the post edit admin page.', 'wp-mvmcloud' ) );


            $this->showCheckbox ( 'mvmcloud_shortcut', __ ( 'Mvmcloud shortcut', 'wp-mvmcloud' ), __ ( 'Display a shortcut to MVMCloud Analytics itself.', 'wp-mvmcloud' ) );

		$this->showInput ( 'plugin_display_name', __ ( 'WP-MVMCloud display name', 'wp-mvmcloud' ), __ ( 'Plugin name shown in WordPress.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'shortcodes', __ ( 'Enable shortcodes', 'wp-mvmcloud' ), __ ( 'Enable shortcodes in post or page content.', 'wp-mvmcloud' ) );

		echo $submitButton;

		echo '</tbody></table><table id="tracking" class="wp-mvmcloud_menu-tab hidden"><tbody>';

		// Tracking Configuration
		$isNotTracking = self::$settings->getGlobalOption ( 'track_mode' ) == 'disabled';
		$isNotGeneratedTracking = $isNotTracking || self::$settings->getGlobalOption ( 'track_mode' ) == 'manually';
		$fullGeneratedTrackingGroup = 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-proxy';

		$description = sprintf ( '%s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s', __ ( 'You can choose between four tracking code modes:', 'wp-mvmcloud' ), __ ( 'Disabled', 'wp-mvmcloud' ), __ ( 'WP-MVMCloud will not add the tracking code. Use this, if you want to add the tracking code to your template files or you use another plugin to add the tracking code.', 'wp-mvmcloud' ), __ ( 'Default tracking', 'wp-mvmcloud' ), __ ( 'WP-MVMCloud will use MVMCloud Analytics\'s standard tracking code.', 'wp-mvmcloud' ), __ ( 'Use js/index.php', 'wp-mvmcloud' ), __ ( 'You can choose this tracking code, to deliver a minified proxy code and to avoid using the files called mvmcloud.js or mvmcloud.php.', 'wp-mvmcloud' ).' '.sprintf( __( 'See %sreadme file%s.', 'wp-mvmcloud' ), '<a href="http://demo.mvmcloud.org/js/README" target="_BLANK">', '</a>'), __ ( 'Use proxy script', 'wp-mvmcloud' ), __ ( 'Use this tracking code to not reveal the MVMCloud Analytics server URL.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud FAQ%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/faq/how-to/#faq_132" target="_BLANK">', '</a>' ) , __ ( 'Enter manually', 'wp-mvmcloud' ), __ ( 'Enter your own tracking code manually. You can choose one of the prior options, pre-configure your tracking code and switch to manually editing at last.', 'wp-mvmcloud' ).( self::$wpMvmcloud->isNetworkMode() ? ' '.__ ( 'Use the placeholder {ID} to add the MVMCloud Analytics site ID.', 'wp-mvmcloud' ) : '' ) );
		$this->showSelect ( 'track_mode', __ ( 'Add tracking code', 'wp-mvmcloud' ), array (
				'disabled' => __ ( 'Disabled', 'wp-mvmcloud' ),
				'default' => __ ( 'Default tracking', 'wp-mvmcloud' ),
				'js' => __ ( 'Use js/index.php', 'wp-mvmcloud' ),
				'proxy' => __ ( 'Use proxy script', 'wp-mvmcloud' ),
				'manually' => __ ( 'Enter manually', 'wp-mvmcloud' )
		), $description, 'jQuery(\'tr.wp-mvmcloud-track-option\').addClass(\'hidden\'); jQuery(\'tr.wp-mvmcloud-track-option-\' + jQuery(\'#track_mode\').val()).removeClass(\'hidden\'); jQuery(\'#tracking_code, #noscript_code\').prop(\'readonly\', jQuery(\'#track_mode\').val() != \'manually\');' );

		$this->showTextarea ( 'tracking_code', __ ( 'Tracking code', 'wp-mvmcloud' ), 15, 'This is a preview of your current tracking code. If you choose to enter your tracking code manually, you can change it here.', $isNotTracking, 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-proxy wp-mvmcloud-track-option-manually', true, '', (self::$settings->getGlobalOption ( 'track_mode' ) != 'manually'), false );

		$this->showSelect ( 'track_codeposition', __ ( 'JavaScript code position', 'wp-mvmcloud' ), array (
				'footer' => __ ( 'Footer', 'wp-mvmcloud' ),
				'header' => __ ( 'Header', 'wp-mvmcloud' )
		), __ ( 'Choose whether the JavaScript code is added to the footer or the header.', 'wp-mvmcloud' ), '', $isNotTracking, 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-proxy wp-mvmcloud-track-option-manually' );

		$this->showTextarea ( 'noscript_code', __ ( 'Noscript code', 'wp-mvmcloud' ), 2, 'This is a preview of your &lt;noscript&gt; code which is part of your tracking code.', self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-manually', true, '', (self::$settings->getGlobalOption ( 'track_mode' ) != 'manually'), false );

		$this->showCheckbox ( 'track_noscript', __ ( 'Add &lt;noscript&gt;', 'wp-mvmcloud' ), __ ( 'Adds the &lt;noscript&gt; code to your footer.', 'wp-mvmcloud' ) . ' ' . __ ( 'Disabled in proxy mode.', 'wp-mvmcloud' ), self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-manually' );

		$this->showCheckbox ( 'track_nojavascript', __ ( 'Add rec parameter to noscript code', 'wp-mvmcloud' ), __ ( 'Enable tracking for visitors without JavaScript (not recommended).', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud FAQ%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/faq/how-to/#faq_176" target="_BLANK">', '</a>' ) . ' ' . __ ( 'Disabled in proxy mode.', 'wp-mvmcloud' ), self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-manually' );

		$this->showSelect ( 'track_content', __ ( 'Enable content tracking', 'wp-mvmcloud' ), array (
				'disabled' => __ ( 'Disabled', 'wp-mvmcloud' ),
				'all' => __ ( 'Track all content blocks', 'wp-mvmcloud' ),
				'visible' => __ ( 'Track only visible content blocks', 'wp-mvmcloud' )
		), __ ( 'Content tracking allows you to track interaction with the content of a web page or application.' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/guides/content-tracking" target="_BLANK">', '</a>' ), '', $isNotTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' );

		$this->showCheckbox ( 'track_search', __ ( 'Track search', 'wp-mvmcloud' ), __ ( 'Use MVMCloud Analytics\'s advanced Site Search Analytics feature.' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/docs/site-search/#track-site-search-using-the-tracking-api-advanced-users-only" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' );

		$this->showCheckbox ( 'track_404', __ ( 'Track 404', 'wp-mvmcloud' ), __ ( 'WP-MVMCloud can automatically add a 404-category to track 404-page-visits.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud FAQ%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/faq/how-to/faq_60/" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' );

        echo '<tr class="' . $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' . ($isNotTracking ? ' hidden' : '') . '">';
        echo '<th scope="row"><label for="add_post_annotations">' . __ ( 'Add annotation on new post of type', 'wp-mvmcloud' ) . '</label>:</th><td>';
        $filter = self::$settings->getGlobalOption ( 'add_post_annotations' );
        foreach ( get_post_types(array(), 'objects') as $post_type )
            echo '<input type="checkbox" ' . (isset ( $filter [$post_type->name] ) && $filter [$post_type->name] ? 'checked="checked" ' : '') . 'value="1" name="wp-mvmcloud[add_post_annotations][' . $post_type->name . ']" /> ' . $post_type->label . ' &nbsp; ';
        echo '<span class="dashicons dashicons-editor-help" onclick="jQuery(\'#add_post_annotations-desc\').toggleClass(\'hidden\');"></span> <p class="description hidden" id="add_post_annotations-desc">' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/docs/annotations/" target="_BLANK">', '</a>' ) . '</p></td></tr>';

		$this->showCheckbox ( 'add_customvars_box', __ ( 'Show custom variables box', 'wp-mvmcloud' ), __ ( ' Show a &quot;custom variables&quot; edit box on post edit page.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/docs/custom-variables/" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' );

		$this->showInput ( 'add_download_extensions', __ ( 'Add new file types for download tracking', 'wp-mvmcloud' ), __ ( 'Add file extensions for download tracking, divided by a vertical bar (&#124;).', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/guides/tracking-javascript-guide#file-extensions-for-tracking-downloads" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

        $this->showSelect ( 'require_consent', __ ( 'Tracking or cookie consent', 'wp-mvmcloud' ), array (
            'disabled' => __ ( 'Disabled', 'wp-mvmcloud' ),
            'consent' => __ ( 'Require consent', 'wp-mvmcloud' ),
            'cookieconsent' => __ ( 'Require cookie consent', 'wp-mvmcloud' )
        ), __ ( 'Enable support for consent managers.' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/guides/tracking-consent" target="_BLANK">', '</a>' ), '', $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

        $this->showCheckbox ( 'disable_cookies', __ ( 'Disable cookies', 'wp-mvmcloud' ), __ ( 'Disable all tracking cookies for a visitor.', 'wp-mvmcloud' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'limit_cookies', __ ( 'Limit cookie lifetime', 'wp-mvmcloud' ), __ ( 'You can limit the cookie lifetime to avoid tracking your users over a longer period as necessary.', 'wp-mvmcloud' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup, true, 'jQuery(\'tr.wp-mvmcloud-cookielifetime-option\').toggleClass(\'wp-mvmcloud-hidden\');' );

		$this->showInput ( 'limit_cookies_visitor', __ ( 'Visitor timeout (seconds)', 'wp-mvmcloud' ), false, $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'limit_cookies' ), $fullGeneratedTrackingGroup.' wp-mvmcloud-cookielifetime-option'. (self::$settings->getGlobalOption ( 'limit_cookies' )? '': ' wp-mvmcloud-hidden') );

		$this->showInput ( 'limit_cookies_session', __ ( 'Session timeout (seconds)', 'wp-mvmcloud' ), false, $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'limit_cookies' ), $fullGeneratedTrackingGroup .' wp-mvmcloud-cookielifetime-option'. (self::$settings->getGlobalOption ( 'limit_cookies' )? '': ' wp-mvmcloud-hidden') );

		$this->showInput ( 'limit_cookies_referral', __ ( 'Referral timeout (seconds)', 'wp-mvmcloud' ), false, $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'limit_cookies' ), $fullGeneratedTrackingGroup .' wp-mvmcloud-cookielifetime-option'. (self::$settings->getGlobalOption ( 'limit_cookies' )? '': ' wp-mvmcloud-hidden') );

		$this->showCheckbox ( 'track_admin', __ ( 'Track admin pages', 'wp-mvmcloud' ), __ ( 'Enable to track users on admin pages (remember to configure the tracking filter appropriately).', 'wp-mvmcloud' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' );

		echo '<tr class="' . $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' . ($isNotTracking ? ' hidden' : '') . '">';
		echo '<th scope="row"><label for="capability_stealth">' . __ ( 'Tracking filter', 'wp-mvmcloud' ) . '</label>:</th><td>';
		$filter = self::$settings->getGlobalOption ( 'capability_stealth' );
		foreach ( $wp_roles->role_names as $key => $name )
			echo '<input type="checkbox" ' . (isset ( $filter [$key] ) && $filter [$key] ? 'checked="checked" ' : '') . 'value="1" name="wp-mvmcloud[capability_stealth][' . $key . ']" /> ' . $name . ' &nbsp; ';
		echo '<span class="dashicons dashicons-editor-help" onclick="jQuery(\'#capability_stealth-desc\').toggleClass(\'hidden\');"></span> <p class="description hidden" id="capability_stealth-desc">' . __ ( 'Choose users by user role you do <strong>not</strong> want to track.', 'wp-mvmcloud' ) . '</p></td></tr>';

		$this->showCheckbox ( 'track_across', __ ( 'Track subdomains in the same website', 'wp-mvmcloud' ), __ ( 'Adds *.-prefix to cookie domain.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/guides/tracking-javascript-guide#tracking-subdomains-in-the-same-website" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'track_across_alias', __ ( 'Do not count subdomains as outlink', 'wp-mvmcloud' ), __ ( 'Adds *.-prefix to tracked domain.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/guides/tracking-javascript-guide#outlink-tracking-exclusions" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showCheckbox ( 'track_crossdomain_linking', __ ( 'Enable cross domain linking', 'wp-mvmcloud' ), __ ( 'When enabled, it will make sure to use the same visitor ID for the same visitor across several domains. This works only when this feature is enabled because the visitor ID is stored in a cookie and cannot be read on the other domain by default. When this feature is enabled, it will append a URL parameter "pk_vid" that contains the visitor ID when a user clicks on a URL that belongs to one of your domains. For this feature to work, you also have to configure which domains should be treated as local in your MVMCloud Analytics website settings. This feature requires MVMCloud Analytics 3.0.2.', 'wp-mvmcloud' ), self::$settings->getGlobalOption ( 'track_mode' ) == 'proxy', 'wp-mvmcloud-track-option wp-mvmcloud-track-option-default wp-mvmcloud-track-option-js wp-mvmcloud-track-option-manually');

		$this->showCheckbox ( 'track_feed', __ ( 'Track RSS feeds', 'wp-mvmcloud' ), __ ( 'Enable to track posts in feeds via tracking pixel.', 'wp-mvmcloud' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually' );

		$this->showCheckbox ( 'track_feed_addcampaign', __ ( 'Track RSS feed links as campaign', 'wp-mvmcloud' ), __ ( 'This will add MVMCloud Analytics campaign parameters to the RSS feed links.' . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="http://mvmcloud.org/docs/tracking-campaigns/" target="_BLANK">', '</a>' ), 'wp-mvmcloud' ), $isNotTracking, $fullGeneratedTrackingGroup . ' wp-mvmcloud-track-option-manually', true, 'jQuery(\'tr.wp-mvmcloud-feed_campaign-option\').toggle(\'hidden\');' );

		$this->showInput ( 'track_feed_campaign', __ ( 'RSS feed campaign', 'wp-mvmcloud' ), __ ( 'Keyword: post name.', 'wp-mvmcloud' ), $isNotGeneratedTracking || ! self::$settings->getGlobalOption ( 'track_feed_addcampaign' ), $fullGeneratedTrackingGroup . ' wp-mvmcloud-feed_campaign-option' );

		$this->showInput ( 'track_heartbeat', __ ( 'Enable heartbeat timer', 'wp-mvmcloud' ), __ ( 'Enable a heartbeat timer to get more accurate visit lengths by sending periodical HTTP ping requests as long as the site is opened. Enter the time between the pings in seconds (Mvmcloud default: 15) to enable or 0 to disable this feature. <strong>Note:</strong> This will cause a lot of additional HTTP requests on your site.', 'wp-mvmcloud' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$this->showSelect ( 'track_user_id', __ ( 'User ID Tracking', 'wp-mvmcloud' ), array (
				'disabled' => __ ( 'Disabled', 'wp-mvmcloud' ),
				'uid' => __ ( 'WP User ID', 'wp-mvmcloud' ),
				'email' => __ ( 'Email Address', 'wp-mvmcloud' ),
				'username' => __ ( 'Username', 'wp-mvmcloud' ),
				'displayname' => __ ( 'Display Name (Not Recommended!)', 'wp-mvmcloud' )
		), __ ( 'When a user is logged in to WordPress, track their &quot;User ID&quot;. You can select which field from the User\'s profile is tracked as the &quot;User ID&quot;. When enabled, Tracking based on Email Address is recommended.', 'wp-mvmcloud' ), '', $isNotTracking, $fullGeneratedTrackingGroup );

		echo $submitButton;
		echo '</tbody></table><table id="expert" class="wp-mvmcloud_menu-tab hidden"><tbody>';

		$this->showText ( __ ( 'Usually, you do not need to change these settings. If you want to do so, you should know what you do or you got an expert\'s advice.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'cache', __ ( 'Enable cache', 'wp-mvmcloud' ), __ ( 'Cache API calls, which not contain today\'s values, for a week.', 'wp-mvmcloud' ) );

		if (function_exists('curl_init') && ini_get('allow_url_fopen'))
			$this->showSelect ( 'http_connection', __ ( 'HTTP connection via', 'wp-mvmcloud' ), array (
				'curl' => __ ( 'cURL', 'wp-mvmcloud' ),
				'fopen' => __ ( 'fopen', 'wp-mvmcloud' )
			), __('Choose whether WP-MVMCloud should use cURL or fopen to connect to MVMCloud Analytics in HTTP or Cloud mode.', 'wp-mvmcloud' ) );

		$this->showSelect ( 'http_method', __ ( 'HTTP method', 'wp-mvmcloud' ), array (
				'post' => __ ( 'POST', 'wp-mvmcloud' ),
				'get' => __ ( 'GET', 'wp-mvmcloud' )
		), __('Choose whether WP-MVMCloud should use POST or GET in HTTP or Cloud mode.', 'wp-mvmcloud' ) );

		$this->showCheckbox ( 'disable_timelimit', __ ( 'Disable time limit', 'wp-mvmcloud' ), __ ( 'Use set_time_limit(0) if stats page causes a time out.', 'wp-mvmcloud' ) );

        $this->showInput ( 'filter_limit', __ ( 'Filter limit', 'wp-mvmcloud' ), __ ( 'Use filter_limit if you need to get more than 100 results per page.', 'wp-mvmcloud' ) );

		$this->showInput ( 'connection_timeout', __ ( 'Connection timeout', 'wp-mvmcloud' ), 'Define a connection timeout for all HTTP requests done by WP-MVMCloud in seconds.' );

		$this->showCheckbox ( 'disable_ssl_verify', __ ( 'Disable SSL peer verification', 'wp-mvmcloud' ), '(' . __ ( 'not recommended', 'wp-mvmcloud' ) . ')' );
		$this->showCheckbox ( 'disable_ssl_verify_host', __ ( 'Disable SSL host verification', 'wp-mvmcloud' ), '(' . __ ( 'not recommended', 'wp-mvmcloud' ) . ')' );

		$this->showSelect ( 'mvmcloud_useragent', __ ( 'User agent', 'wp-mvmcloud' ), array (
				'php' => __ ( 'Use the PHP default user agent', 'wp-mvmcloud' ) . (ini_get ( 'user_agent' ) ? '(' . ini_get ( 'user_agent' ) . ')' : ' (' . __ ( 'empty', 'wp-mvmcloud' ) . ')'),
				'own' => __ ( 'Define a specific user agent', 'wp-mvmcloud' )
		), 'WP-MVMCloud can send the default user agent defined by your PHP settings or use a specific user agent below. The user agent is send by WP-MVMCloud if HTTP requests are performed.', 'jQuery(\'tr.wp-mvmcloud-useragent-option\').toggleClass(\'hidden\');' );
		$this->showInput ( 'mvmcloud_useragent_string', __ ( 'Specific user agent', 'wp-mvmcloud' ), 'Define a user agent description which is send by WP-MVMCloud if HTTP requests are performed.', self::$settings->getGlobalOption ( 'mvmcloud_useragent' ) != 'own', 'wp-mvmcloud-useragent-option' );

        $this->showCheckbox ( 'dnsprefetch', __ ( 'Enable DNS prefetch', 'wp-mvmcloud' ), __ ( 'Add a DNS prefetch tag.' . ' ' . sprintf ( __ ( 'See %sMvmcloud Blog%s.', 'wp-mvmcloud' ), '<a target="_BLANK" href="https://mvmcloud.org/blog/2017/04/important-performance-optimizations-load-mvmcloud-javascript-tracker-faster/">', '</a>' ), 'wp-mvmcloud' ) );

        $this->showCheckbox ( 'track_datacfasync', __ ( 'Add data-cfasync=false', 'wp-mvmcloud' ), __ ( 'Adds data-cfasync=false to the script tag, e.g., to ask Rocket Loader to ignore the script.' . ' ' . sprintf ( __ ( 'See %sCloudFlare Knowledge Base%s.', 'wp-mvmcloud' ), '<a href="https://support.cloudflare.com/hc/en-us/articles/200169436-How-can-I-have-Rocket-Loader-ignore-my-script-s-in-Automatic-Mode-" target="_BLANK">', '</a>' ), 'wp-mvmcloud' ) );

		$this->showInput ( 'track_cdnurl', __ ( 'CDN URL', 'wp-mvmcloud' ).' http://', 'Enter URL if you want to load the tracking code via CDN.' );

		$this->showInput ( 'track_cdnurlssl', __ ( 'CDN URL (SSL)', 'wp-mvmcloud' ).' https://', 'Enter URL if you want to load the tracking code via a separate SSL CDN.' );

		$this->showSelect ( 'force_protocol', __ ( 'Force MVMCloud Analytics to use a specific protocol', 'wp-mvmcloud' ), array (
				'disabled' => __ ( 'Disabled (default)', 'wp-mvmcloud' ),
				'http' => __ ( 'http', 'wp-mvmcloud' ),
				'https' => __ ( 'https (SSL)', 'wp-mvmcloud' )
		), __ ( 'Choose if you want to explicitly force MVMCloud Analytics to use HTTP or HTTPS. Does not work with a CDN URL.', 'wp-mvmcloud' ) );

        $this->showCheckbox ( 'remove_type_attribute', __ ( 'Remove type attribute', 'wp-mvmcloud' ), __ ( 'Removes the type attribute from MVMCloud Analytics\'s tracking code script tag.', 'wp-mvmcloud') );

        $this->showSelect ( 'update_notice', __ ( 'Update notice', 'wp-mvmcloud' ), array (
				'enabled' => __ ( 'Show always if WP-MVMCloud is updated', 'wp-mvmcloud' ),
				'script' => __ ( 'Show only if WP-MVMCloud is updated and settings were changed', 'wp-mvmcloud' ),
				'disabled' => __ ( 'Disabled', 'wp-mvmcloud' )
		), __ ( 'Choose if you want to get an update notice if WP-MVMCloud is updated.', 'wp-mvmcloud' ) );

		$this->showInput ( 'set_download_extensions', __ ( 'Define all file types for download tracking', 'wp-mvmcloud' ), __ ( 'Replace MVMCloud Analytics\'s default file extensions for download tracking, divided by a vertical bar (&#124;). Leave blank to keep MVMCloud Analytics\'s default settings.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud documentation%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/guides/tracking-javascript-guide#file-extensions-for-tracking-downloads" target="_BLANK">', '</a>' ) );

        $this->showInput ( 'set_download_classes', __ ( 'Set classes to be treated as downloads', 'wp-mvmcloud' ), __ ( 'Set classes to be treated as downloads (in addition to mvmcloud_download), divided by a vertical bar (&#124;). Leave blank to keep MVMCloud Analytics\'s default settings.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud JavaScript Tracking Client reference%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/api-reference/tracking-javascript" target="_BLANK">', '</a>' ) );

        $this->showInput ( 'set_link_classes', __ ( 'Set classes to be treated as outlinks', 'wp-mvmcloud' ), __ ( 'Set classes to be treated as outlinks (in addition to mvmcloud_link), divided by a vertical bar (&#124;). Leave blank to keep MVMCloud Analytics\'s default settings.', 'wp-mvmcloud' ) . ' ' . sprintf ( __ ( 'See %sMvmcloud JavaScript Tracking Client reference%s.', 'wp-mvmcloud' ), '<a href="https://developer.mvmcloud.org/api-reference/tracking-javascript" target="_BLANK">', '</a>' ) );

		echo $submitButton;
		?>
			</tbody>
		</table>
		<table id="support" class="wp-mvmcloud_menu-tab hidden">
			<tbody>
				<tr><td colspan="2"><?php
					echo $this->showSupport();
				?></td></tr>
			</tbody>
		</table>
		<input type="hidden" name="wp-mvmcloud[proxy_url]"
			value="<?php echo self::$settings->getGlobalOption('proxy_url'); ?>" />
	</form>
</div>
<?php
	}

	/**
	 * Show an option's description
	 *
	 * @param string $id option id
	 * @param string $description option description
	 * @param boolean $hideDescription set to false to show description initially (default: true)
	 * @return string full description HTML
	 */
	private function getDescription($id, $description, $hideDescription = true) {
		return sprintf ( '<span class="dashicons dashicons-editor-help" onclick="jQuery(\'#%s-desc\').toggleClass(\'hidden\');"></span> <p class="description' . ($hideDescription ? ' hidden' : '') . '" id="%1$s-desc">%s</p>', $id, $description );
	}

	/**
	 * Show a checkbox option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param string $onChange javascript for onchange event (default: empty)
	 */
	private function showCheckbox($id, $name, $description, $isHidden = false, $groupName = '', $hideDescription = true, $onChange = '') {
		printf ( '<tr class="' . $groupName . ($isHidden ? ' hidden' : '') . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><input type="checkbox" value="1"' . (self::$settings->getGlobalOption ( $id ) ? ' checked="checked"' : '') . ' onchange="jQuery(\'#%s\').val(this.checked?1:0);%s" /><input id="%2$s" type="hidden" name="wp-mvmcloud[%2$s]" value="' . ( int ) self::$settings->getGlobalOption ( $id ) . '" /> %s</td></tr>', $name, $id, $onChange, $this->getDescription ( $id, $description, $hideDescription ) );
	}

	/**
	 * Show a textarea option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param int $rows number of rows to show
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param string $onChange javascript for onchange event (default: empty)
	 * @param boolean $isReadonly set textarea to read only (default: false)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	private function showTextarea($id, $name, $rows, $description, $isHidden, $groupName, $hideDescription = true, $onChange = '', $isReadonly = false, $global = true) {
		printf (
			'<tr class="' . $groupName . ($isHidden ? ' hidden' : '') . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><textarea cols="80" rows="' . $rows . '" id="%s" name="wp-mvmcloud[%2$s]" onchange="%s"' . ($isReadonly ? ' readonly="readonly"' : '') . '>%s</textarea> %s</td></tr>', $name, $id, $onChange, ($global ? self::$settings->getGlobalOption ( $id ) : self::$settings->getOption ( $id )), $this->getDescription ( $id, $description, $hideDescription ) );
	}

	/**
	 * Show a simple text
	 *
	 * @param string $text Text to show
	 */
	private function showText($text) {
		printf ( '<tr><td colspan="2"><p>%s</p></td></tr>', $text );
	}

	/**
	 * Show an input option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param string $rowName define a class name to access the specific option row by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param boolean $wide Create a wide box (default: false)
	 */
	private function showInput($id, $name, $description, $isHidden = false, $groupName = '', $rowName = false, $hideDescription = true, $wide = false) {
		printf ( '<tr class="%s%s"%s><th scope="row"><label for="%5$s">%s:</label></th><td><input '.($wide?'class="wp-mvmcloud-wide" ':'').'name="wp-mvmcloud[%s]" id="%5$s" value="%s" /> %s</td></tr>', $isHidden ? 'hidden ' : '', $groupName ? $groupName : '', $rowName ? ' id="' . $groupName . '-' . $rowName . '"' : '', $name, $id, htmlentities(self::$settings->getGlobalOption( $id ), ENT_QUOTES, 'UTF-8', false), !empty($description) ? $this->getDescription ( $id, $description, $hideDescription ) : '' );
	}

	/**
	 * Show a select box option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param array $options list of options to show array[](option id => descriptive name)
	 * @param string $description option description
	 * @param string $onChange javascript for onchange event (default: empty)
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	private function showSelect($id, $name, $options = array(), $description = '', $onChange = '', $isHidden = false, $groupName = '', $hideDescription = true, $global = true) {
		$optionList = '';
		$default = $global ? self::$settings->getGlobalOption ( $id ) : self::$settings->getOption ( $id );
		if (is_array ( $options ))
			foreach ( $options as $key => $value )
				$optionList .= sprintf ( '<option value="%s"' . ($key == $default ? ' selected="selected"' : '') . '>%s</option>', $key, $value );
		printf ( '<tr class="' . $groupName . ($isHidden ? ' hidden' : '') . '"><th scope="row"><label for="%2$s">%s:</label></th><td><select name="wp-mvmcloud[%s]" id="%2$s" onchange="%s">%s</select> %s</td></tr>', $name, $id, $onChange, $optionList, $this->getDescription ( $id, $description, $hideDescription ) );
	}

	/**
	 * Show an info box
	 *
	 * @param string $type box style (e.g., updated, error)
	 * @param string $icon box icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $content box message
	 */
	private function showBox($type, $icon, $content) {
		printf ( '<tr><td colspan="2"><div class="%s"><p><span class="dashicons dashicons-%s"></span> %s</p></div></td></tr>', $type, $icon, $content );
	}

	/**
	 * Show headline
	 * @param int $order headline order (h?-tag), set to 0 to avoid headline-tagging
	 * @param string $icon headline icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $headline headline text
	 * @param string $addPluginName set to true to add the plugin name to the headline (default: false)
	 */
	private function showHeadline($order, $icon, $headline, $addPluginName = false) {
		echo $this->getHeadline ( $order, $icon, $headline, $addPluginName = false );
	}

	/**
	 * Get headline HTML
	 *
	 * @param int $order headline order (h?-tag), set to 0 to avoid headline-tagging
	 * @param string $icon headline icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $headline headline text
	 * @param string $addPluginName set to true to add the plugin name to the headline (default: false)
	 */
	private function getHeadline($order, $icon, $headline, $addPluginName = false) {
		echo ($order > 0 ? "<h$order>" : '') . sprintf ( '<span class="dashicons dashicons-%s"></span> %s%s', $icon, ($addPluginName ? self::$settings->getNotEmptyGlobalOption ( 'plugin_display_name' ) . ' ' : ''), __ ( $headline, 'wp-mvmcloud' ) ) . ($order > 0 ? "</h$order>" : '');
	}

	/**
	 * Register admin scripts
	 *
	 * @see \WP_MVMCloud\Admin::printAdminScripts()
	 */
	public function printAdminScripts() {
		wp_enqueue_script ( 'jquery' );
	}

	/**
	 * Extend admin header
	 *
	 * @see \WP_MVMCloud\Admin::extendAdminHeader()
	 */
	public function extendAdminHeader() {
	}

	/**
	 * Show support information
	 */
	public function showSupport() {
		?><ul>
			<li><?php _e('The best place to get help:', 'wp-mvmcloud'); ?> <a href="https://portal.mvmcloud.net/portal/submitticket.php?step=2&deptid=1" target="_BLANK"><?php _e('WP-MVMCloud support forum','wp-mvmcloud'); ?></a></li>
		</ul>
		<h3><?php _e('Debugging', 'wp-mvmcloud'); ?></h3>
		<p><?php _e('Either allow_url_fopen has to be enabled <em>or</em> cURL has to be available:', 'wp-mvmcloud'); ?></p>
		<ol>
			<li><?php
				_e('cURL is','wp-mvmcloud');
				echo ' <strong>'.(function_exists('curl_init')?'':__('not','wp-mvmcloud')).' ';
				_e('available','wp-mvmcloud');
			?></strong>.</li>
			<li><?php
				_e('allow_url_fopen is','wp-mvmcloud');
				echo ' <strong>'.(ini_get('allow_url_fopen')?'':__('not','wp-mvmcloud')).' ';
				_e('enabled','wp-mvmcloud');
			?></strong>.</li>
			<li><strong><?php echo (((function_exists('curl_init') && ini_get('allow_url_fopen') && self::$settings->getGlobalOption('http_connection') == 'curl') || (function_exists('curl_init') && !ini_get('allow_url_fopen')))?__('cURL', 'wp-mvmcloud'):__('fopen', 'wp-mvmcloud')).' ('.(self::$settings->getGlobalOption('http_method')=='post'?__('POST','wp-mvmcloud'):__('GET','wp-mvmcloud')).')</strong> '.__('is used.', 'wp-mvmcloud'); ?></li>
			<?php if (self::$settings->getGlobalOption('mvmcloud_mode') == 'php') { ?><li><?php
				_e('Determined MVMCloud Analytics base URL is', 'wp-mvmcloud');
				echo ' <strong>'.(self::$settings->getGlobalOption('proxy_url')).'</strong>';
			?></li><?php } ?>
		</ol>
		<p><?php _e('Tools', 'wp-mvmcloud'); ?>:</p>
		<ol>
			<li><a href="<?php echo admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&testscript=1' ); ?>"><?php _e('Run testscript', 'wp-mvmcloud'); ?></a></li>
			<li><a href="<?php echo admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&sitebrowser=1' ); ?>"><?php _e('Sitebrowser', 'wp-mvmcloud'); ?></a></li>
			<li><a href="<?php echo wp_nonce_url( admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&clear=1' ) ); ?>"><?php _e('Clear cache', 'wp-mvmcloud'); ?></a></li>
			<li><a onclick="return confirm('<?php _e('Are you sure you want to clear all settings?', 'wp-mvmcloud'); ?>')" href="<?php echo wp_nonce_url( admin_url( (self::$settings->checkNetworkActivation () ? 'network/settings' : 'options-general').'.php?page='.$_GET['page'].'&clear=2' ) ); ?>"><?php _e('Reset WP-MVMCloud', 'wp-mvmcloud'); ?></a></li>
		</ol>
        <?php
	}

	/**
	 * Clear cache and reset settings
	 *
	 * @param boolean $clearSettings set to true to reset settings (default: false)
	 */
	private function clear($clearSettings = false) {
		if ($clearSettings) {
			self::$settings->resetSettings();
			$this->showBox ( 'updated', 'yes', __ ( 'Settings cleared (except connection settings).' ) );
		}
		global $wpdb;
		if (self::$settings->checkNetworkActivation()) {
			$aryBlogs = \WP_MVMCloud\Settings::getBlogList();
			if (is_array($aryBlogs))
				foreach ($aryBlogs as $aryBlog) {
                    switch_to_blog($aryBlog['blog_id']);
					$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp-mvmcloud_%'");
					$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-mvmcloud_%'");
					restore_current_blog();
				}
		} else {
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp-mvmcloud_%'");
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-mvmcloud_%'");
		}
		$this->showBox ( 'updated', 'yes', __ ( 'Cache cleared.' ) );
	}

	/**
	 * Execute test script and display results
	 */
	private function runTestscript() { ?>
		<div class="wp-mvmcloud-debug">
		<h2>Testscript Result</h2>
		<?php
			if (self::$wpMvmcloud->isConfigured()) {
				if (isset($_GET['testscript_id']) && $_GET['testscript_id'])
					switch_to_blog((int) $_GET['testscript_id']);
		?>
		<textarea cols="80" rows="10"><?php
			echo '`WP-MVMCloud '.self::$wpMvmcloud->getPluginVersion()."\nMode: ".self::$settings->getGlobalOption('mvmcloud_mode')."\n\n";
		?>Test 1/3: global.getMvmcloudVersion<?php
			$GLOBALS ['wp-mvmcloud_debug'] = true;
			$id = \WP_MVMCloud\Request::register ( 'API.getMvmcloudVersion', array() );
			echo "\n\n"; var_dump( self::$wpMvmcloud->request( $id ) ); echo "\n";
			var_dump( self::$wpMvmcloud->request( $id, true ) ); echo "\n";
			$GLOBALS ['wp-mvmcloud_debug'] = false;
		?>Test 2/3: SitesManager.getSitesWithAtLeastViewAccess<?php
			$GLOBALS ['wp-mvmcloud_debug'] = true;
			$id = \WP_MVMCloud\Request::register ( 'SitesManager.getSitesWithAtLeastViewAccess', array() );
			echo "\n\n"; var_dump( self::$wpMvmcloud->request( $id ) ); echo "\n";
			var_dump( self::$wpMvmcloud->request( $id, true ) ); echo "\n";
			$GLOBALS ['wp-mvmcloud_debug'] = false;
		?>Test 3/3: SitesManager.getSitesIdFromSiteUrl<?php
			$GLOBALS ['wp-mvmcloud_debug'] = true;
			$id = \WP_MVMCloud\Request::register ( 'SitesManager.getSitesIdFromSiteUrl', array (
				'url' => get_bloginfo ( 'url' )
			) );
			echo "\n\n";  var_dump( self::$wpMvmcloud->request( $id ) ); echo "\n";
			var_dump( self::$wpMvmcloud->request( $id, true ) ); echo "\n";
			echo "\n\n";  var_dump( self::$settings->getDebugData() ); echo "`";
			$GLOBALS ['wp-mvmcloud_debug'] = false;
		?></textarea>
		<?php
				if (isset($_GET['testscript_id']) && $_GET['testscript_id'])
					restore_current_blog();
			} else echo '<p>Please configure WP-MVMCloud first.</p>';
		?>
		</div>
	<?php }

}
