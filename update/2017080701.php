<?php

// Re-write MVMCloud Analytics Pro configuration to default http configuration
if ($this->isConfigured() && self::$settings->getGlobalOption ( 'mvmcloud_mode' ) == 'pro') {
    self::$settings->setGlobalOption ( 'mvmcloud_url', 'https://' . self::$settings->getGlobalOption ( 'mvmcloud_user' ) . '.mvmcloud.pro/');
    self::$settings->setGlobalOption ( 'mvmcloud_mode', 'http' );
}

// If post annotations are already enabled, choose all existing post types
if (self::$settings->getGlobalOption('add_post_annotations'))
    self::$settings->setGlobalOption('add_post_annotations', get_post_types());

self::$settings->save ();
