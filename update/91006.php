<?php
$aryRemoveOptions = array (
		'wp-mvmcloud_siteid',
		'wp-mvmcloud_404',
		'wp-mvmcloud_scriptupdate',
		'wp-mvmcloud_dashboardid',
		'wp-mvmcloud_jscode' 
);
foreach ( $aryRemoveOptions as $strRemoveOption )
	delete_option ( $strRemoveOption );
