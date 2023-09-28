=== WP-MVMCloud Integration ===

Contributors: MVMCloud
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 2.0.28
Tags: mvmcloud, tracking, statistics, stats, analytics

Adds MVMCloud Analytics statistics to your WordPress dashboard and is also able to add the MVMCloud Analytics Tracking Code to your blog.

== Description ==

If you are using MVMCloud Analytics and you would like to add the statistics of your site to your WordPress dashboard , please use the [MVMCloud for WordPress plugin](https://github.com/mvmcloud/wp-mvmcloud).

This plugin uses the MVMCloud Analytics API to show your MVMCloud Analytics statistics in your WordPress dashboard. It's also able to add the MVMCloud Analytics tracking code to your blog and to do some modifications to the tracking code. Additionally, WP-MVMCloud supports WordPress networks and manages multiple sites and their tracking codes.

To use this plugin the MVMCloud Analytics web analytics application is required. If you don't have a subscription you can get one at [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics).

**Requirements:** PHP 7.0 (or higher), WordPress 5.0 (or higher), MVMCloud Analytics 4.0 (or higher)

== Supported Languages ==

This plugin is supported in Englich and Brazilian Portuguese. You can get the brazilian portuguese version of this file at (você pode obter a versão em Portguguês deste arquivo em) [WP-MVMCloud brazilian Portuguese](https://github.com/mvmcloud/wp-mvmcloud/blob/master/README_pt-br.txt)


= What is MVMCloud Analytics? =

MVMCloud Analytics is a web analytics software platform. It provides detailed reports on your website and its visitors, including the search engines and keywords they used, the language they speak, which pages they like,
the files they download and so much more.

= First steps =
- You need a subscription of the MVMCloud Analytics. If you don't have one you can get it at [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics);
- Install and activate this plugin on your WordPress installation;
- Configure the plugin to access your MVMCloud Analytics instance;
- Install, using this plugin, the MVMCloud Analytics tracking code on your WordPress head tag;
- Navigate to your WordPress dashboard, and you will see a new menu item called WP-MVMCLOUD;
- Click on it to see the metrics of your site.

= Shortcodes =
You can use following shortcodes if activated:

    [wp-mvmcloud module="overview" title="" period="day" date="yesterday"]
Shows overview table like WP-MVMCloud's overview dashboard. Multiple data arrays will be cumulated. If you fill the title attribute, its content will be shown in the table's title.

    [wp-mvmcloud module="opt-out" language="en" width="100%" height="200px"]
Shows the MVMCloud Analytics opt-out Iframe. You can change the Iframe's language by the language attribute (e.g. pt-br for brazilian Portuguese) and its width and height using the corresponding attributes.

    [wp-mvmcloud module="post" range="last30" key="sum_daily_nb_uniq_visitors"]
Shows the chosen keys value related to the current post. You can define a range (format: lastN, previousN or YYYY-MM-DD,YYYY-MM-DD) and the desired value's key (e.g., sum_daily_nb_uniq_visitors, nb_visits or nb_hits - for details see MVMCloud Analytics's API method Actions.getPageUrl using a range).

    [wp-mvmcloud]
is equal to *[wp-mvmcloud module="overview" title="" period="day" date="yesterday"]*.

Thank you all!

== Frequently Asked Questions ==

= Where can I find the MVMCloud Analytics URL and the MVMCloud Analytics auth token? =

To use this plugin you will need your own MVMCloud Analytics subscription. If you don't have one you can get it at [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics).

As soon as MVMCloud Analytics works, you'll be able to configure WP-MVMCloud: The MVMCloud Analytics URL is the same URL you use to access your MVMCloud Analytics, e.g. for the demo site: https://analytics.mvmcloud.net. The auth token is some kind of a secret password, which allows WP-MVMCloud to get the necessary data from MVMCloud Analytics. To get your auth token, log in to MVMCloud Analytics, click at the Administration gear icon (top right) and click at "Personal" and "Security" (left sidebar menu).

You can create as many tokens as you want, we recommend that you create one just to use in this plugin.

= I get this message: "WP-MVMCloud (WP-MVMCloud) was not able to connect to MVMCloud Analytics (Mvmcloud) using our configuration". How to proceed? =

First, please make sure your configuration is valid, e.g., if you are using the right MVMCloud Analytics URL (see description above). Then, go to the "Support" tab and run the test script. This test script will try to get some information from MVMCloud Analytics and shows the full response. Usually, the response output gives a clear hint what's wrong:

The response output contains...

* **bool(false)** and **HTTP/1.1 403 Forbidden**: WP-MVMCloud is not allowed to connect to MVMCloud Analytics. Please check your MVMCloud Analytics server's configuration. Maybe you are using a password protection via .htaccess or you are blocking requests from localhost/127.0.0.1. If you aren’t sure about this, please contact your web hoster for support.
* **bool(false)** and **HTTP/1.1 404 Not Found**: The MVMCloud Analytics URL is wrong. Try to copy & paste the URL you use to access MVMCloud Analytics itself via browser.
* **bool(false)** and no further HTTP response code: The MVMCloud Analytics server does not respond. Very often, this is caused by firewall or mod_security settings. Check your server logfiles to get further information. If you aren’t sure about this, please contact your web hoster for support.

= PHP Compatibility Checker reports PHP7 compatbility issues with WP-MVMCloud. =

The Compatibility Checker shows two false positives. WP-MVMCloud is 100% PHP7 compatible, you can ignore the report.

= WP-MVMCloud only shows the first 100 sites of my multisite network. How can I get all other sites? =

The MVMCloud Analytics API is limited to 100 sites by default. You can open a support ticket to ask for more sites.

= Tracking does not work on HostGator! =

Try to enable the "avoid mod_security" option (WP-MVMCloud settings, Tracking tab) or create a mod_security whitelist.

Thank you very much! :-)

== Installation ==

= General Notes =

* First, you have to set up a running MVMCloud Analytics instance. If you don't have one you can get it at [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics).

= Install WP-MVMCloud on a simple WordPress blog =

1. Upload the full `wp-mvmcloud` directory into your `wp-content/plugins` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open the new 'Settings/WP-MVMCloud (WP-MVMCloud) Settings' menu and follow the instructions to configure your MVMCloud Analytics connection. Save settings.
4. If you have view access to multiple site stats and did not enable "auto config", choose your blog and save settings again.
5. Look at 'Dashboard/WP-MVMCloud' to see your site stats.

= Install WP-MVMCloud on a WordPress blog network (WPMU/WP multisite) =

There are two differents methods to use WP-MVMCloud in a multisite environment:

* As a Site Specific Plugin it behaves like a plugin installed on a simple WordPress blog. Each user can enable, configure and use WP-MVMCloud on his own. Users can even use their own MVMCloud Analytics instances (and accordingly they have to).
* Using WP-MVMCloud as a Network Plugin equates to a central approach. A single MVMCloud Analytics instance is used and the site admin configures the plugin completely. Users are just allowed to see their own statistics, site admins can see each blog's stats.

*Site Specific Plugin*

Just add WP-MVMCloud to your /wp-content/plugins folder and enable the Plugins page for individual site administrators. Each user has to enable and configure WP-MVMCloud on his own if he want to use the plugin.

*Network Plugin*

The Network Plugin support is still experimental. Please test it on your own (e.g. using a local copy of your WP multisite) before you use it in an user context.

Add WP-MVMCloud to your /wp-content/plugins folder and enable it as [Network Plugin](http://codex.wordpress.org/Create_A_Network#WordPress_Plugins). Users can access their own statistics, site admins can access each blog's statistics and the plugin's configuration.

== Screenshots ==

1. WP-MVMCloud settings.
2. WP-MVMCloud statistics page.
3. Closer look to a pie chart.
