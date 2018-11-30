<?php

interface DbServiceInterface
{
    public function createTables();
    public function insert($table, $data, $format = null);
    public function getRegisteredUsers($userIdPartner);
    public function getConversionStatistics($userIdPartner);
    public function getAllUsersStatistics();
    public function getTableUsersPartnerLinksStats();
    public function getTableUsersPartnerRegistration();
}

class DbService implements DbServiceInterface
{
    private $tableUsersPartnerLinksStats;
    private $tableUsersPartnerRegistration;
    private $tableUsers;

    public function getTableUsersPartnerLinksStats() {
        return $this->tableUsersPartnerLinksStats;
    }

    public function getTableUsersPartnerRegistration() {
        return $this->tableUsersPartnerRegistration;
    }

    public function __construct() {
        global $wpdb;
        $this->tableUsersPartnerLinksStats = $wpdb->prefix . "users_partner_links_stats";
        $this->tableUsersPartnerRegistration = $wpdb->prefix . "users_partner_registration";
        $this->tableUsers = $wpdb->prefix . "users";
    }

    public function createTables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        if($wpdb->get_var("SHOW TABLES LIKE '$this->tableUsersPartnerLinksStats'") != $this->tableUsersPartnerLinksStats) {
            $sql = "CREATE TABLE " . $this->tableUsersPartnerLinksStats . " (
              id bigint(20) NOT NULL AUTO_INCREMENT,
              user_id_partner bigint(20) NOT NULL,
              ip VARCHAR(15) NOT NULL,
              url_from VARCHAR(100),
              url_to VARCHAR(100) NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY id (id)
            );";

            dbDelta($sql);
        }
        if($wpdb->get_var("SHOW TABLES LIKE '$this->tableUsersPartnerRegistration'") != $this->tableUsersPartnerRegistration) {
            $sql = "CREATE TABLE " . $this->tableUsersPartnerRegistration . " (
              id bigint(20) NOT NULL AUTO_INCREMENT,
              user_id_partner bigint(20) NOT NULL,
              user_id_registered bigint(20),
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY id (id)
            );";
            dbDelta($sql);
        }
    }

    public function insert($table, $data, $format = null) {
        global $wpdb;
        $wpdb->insert($table, $data, $format);
    }

    public function getRegisteredUsers($userIdPartner) {
        global $wpdb;

        $rows = $wpdb->get_results( "SELECT  $this->tableUsersPartnerRegistration.user_id_partner, $this->tableUsersPartnerRegistration.created_at, $this->tableUsers.user_login, $this->tableUsers.user_email, $this->tableUsers.user_nicename FROM $this->tableUsersPartnerRegistration
                LEFT JOIN $this->tableUsers ON $this->tableUsers.ID = $this->tableUsersPartnerRegistration.user_id_registered
		        WHERE user_id_partner = $userIdPartner" );

        return $rows;
    }

    public function getConversionStatistics($userIdPartner) {
        global $wpdb;

        $rows = $wpdb->get_results( "SELECT * FROM $this->tableUsersPartnerLinksStats WHERE user_id_partner = $userIdPartner" );

        return $rows;
    }

    public function getAllUsersStatistics() {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT  $this->tableUsersPartnerLinksStats.user_id_partner, $this->tableUsersPartnerLinksStats.ip, $this->tableUsersPartnerLinksStats.url_from, $this->tableUsersPartnerLinksStats.url_to, $this->tableUsersPartnerLinksStats.created_at, $this->tableUsers.user_login, $this->tableUsers.user_email, $this->tableUsers.user_nicename 
                FROM $this->tableUsersPartnerLinksStats
                LEFT JOIN $this->tableUsers ON $this->tableUsers.ID = $this->tableUsersPartnerLinksStats.user_id_partner" );

        return $rows;
    }
}