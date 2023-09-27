<?php

namespace WP_MVMCloud\Admin;

if (! class_exists ( 'WP_List_Table' ))
	require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Sitebrowser extends \WP_List_Table {

	private $data = array (), $wpMvmcloud;

	public function __construct($wpMvmcloud) {
		$this->wpMvmcloud = $wpMvmcloud;
        if( isset($_POST['s']) ){
            $cnt = $this->prepare_items ($_POST['s']);
        } else {
            $cnt = $this->prepare_items ();
        }
		global $status, $page;
		$this->showSearchForm();
		parent::__construct ( array (
				'singular' => __ ( 'site', 'wp-mvmcloud' ),
				'plural' => __ ( 'sites', 'wp-mvmcloud' ),
				'ajax' => false
		) );
		if ($cnt > 0)
			$this->display ();
		else
			echo '<p>' . __ ( 'No site configured yet.', 'wp-mvmcloud' ) . '</p>';
	}

	public function get_columns() {
		$columns = array (
				'id' => __ ( 'Blog ID', 'wp-mvmcloud' ),
				'name' => __ ( 'Title', 'wp-mvmcloud' ),
				'siteurl' => __ ( 'URL', 'wp-mvmcloud' ),
				'mvmcloudid' => __ ( 'Site ID (Mvmcloud)', 'wp-mvmcloud' )
		);
		return $columns;
	}

	public function prepare_items($search = '') {
		$current_page = $this->get_pagenum ();
		$per_page = 10;
		global $blog_id;
		global $wpdb;
		global $pagenow;
		if (is_plugin_active_for_network ( 'wp-mvmcloud/wp-mvmcloud.php' )) {
			$total_items = $wpdb->get_var ( $wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->blogs . ' WHERE CONCAT(domain, path) LIKE "%%%s%%" AND spam = 0 AND deleted = 0', $search));
			$blogs = \WP_MVMCloud\Settings::getBlogList($per_page, $current_page, $search);
			foreach ( $blogs as $blog ) {
            	$blogDetails = get_blog_details ( $blog['blog_id'], true );
				$this->data [] = array (
						'name' => $blogDetails->blogname,
						'id' => $blogDetails->blog_id,
						'siteurl' => $blogDetails->siteurl,
						'mvmcloudid' => $this->wpMvmcloud->getMvmcloudSiteId ( $blogDetails->blog_id )
				);
			}
		} else {
			$blogDetails = get_bloginfo ();
			$this->data [] = array (
					'name' => get_bloginfo ( 'name' ),
					'id' => '-',
					'siteurl' => get_bloginfo ( 'url' ),
					'mvmcloudid' => $this->wpMvmcloud->getMvmcloudSiteId ()
			);
			$total_items = 1;
		}
		$columns = $this->get_columns ();
		$hidden = array ();
		$sortable = array ();
		$this->_column_headers = array (
				$columns,
				$hidden,
				$sortable
		);
		$this->set_pagination_args ( array (
				'total_items' => $total_items,
				'per_page' => $per_page
		) );
		foreach ( $this->data as $key => $dataset ) {
			if (empty ( $dataset ['mvmcloudid'] ) || $dataset ['mvmcloudid'] == 'n/a')
				$this->data [$key] ['mvmcloudid'] = __ ( 'Site not created yet.', 'wp-mvmcloud' );
			if ($this->wpMvmcloud->isNetworkMode ())
				$this->data [$key] ['name'] = '<a href="index.php?page=wp-mvmcloud_stats&wpmu_show_stats=' . $dataset ['id'] . '">' . $dataset ['name'] . '</a>';
		}
		$this->items = $this->data;
		return count ( $this->items );
	}

	public function column_default($item, $column_name) {
		switch ($column_name) {
			case 'id' :
			case 'name' :
			case 'siteurl' :
			case 'mvmcloudid' :
				return $item [$column_name];
			default :
				return print_r ( $item, true );
		}
	}

	private function showSearchForm() {
        ?>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING) ?>" />
            <?php $this->search_box('Search domain and path', 'wpMvmcloudSiteSearch'); ?>
        </form>
        <?php
    }
}
