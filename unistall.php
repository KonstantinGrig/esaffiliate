<?php
if( ! defined('WP_UNINSTALL_PLUGIN') )
    exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once('DbService.php');
global $wpdb;
$dbService = new DbService();
$tableUsersPartnerLinksStats = $dbService->getTableUsersPartnerLinksStats();
$tableUsersPartnerRegistration = $dbService->getTableUsersPartnerRegistration();
$sql = "DROP TABLE IF EXISTS $tableUsersPartnerLinksStats";
$wpdb->query($sql);
dbDelta($sql);
$sql = "DROP TABLE IF EXISTS $tableUsersPartnerRegistration";
$wpdb->query($sql);
dbDelta($sql);


