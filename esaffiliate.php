<?php
/*
Plugin Name: Esaffiliate
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Affiliate Plugin.
Version: 1.0
Author: Konstantin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

require_once('DbService.php');

class Esaffiliate {
    const KEY_REF = 'ref';
    const KEY_AFFILIATE_COOKIE = 'affiliate_user';
    public $dbService;
    public function __construct(DbServiceInterface $dbService) {
        $this->dbService = $dbService;
        register_activation_hook( __FILE__, [$this, 'activation'] );
        add_action('show_user_profile', [$this, 'addPartnerSection']);
        add_action('edit_user_profile', [$this, 'addPartnerSection']);
        add_action( 'wp', [$this, 'onPageLoad'] );
        add_action( 'user_register', [$this,'onUserRegister'] );
        add_action('admin_menu', [$this, 'registerSubmenuPage']);
    }

    public function activation() {
        $this->dbService->createTables();
    }

    public function registerSubmenuPage() {
        add_submenu_page( 'users.php', 'Статистика переходов по партнёрским ссылкам',
            'Партнёрская программа', 'administrator', 'affiliate_program', [$this, 'addAffiliateAdminPage']);
    }

    public function addPartnerSection($user) {
        $this->addPartnerLink($user);
        $this->addRegisteredUsersList($user);
        $this->addConversionStatistics($user);
    }

    public function addPartnerLink($user ) {
        $this->dbService->createTables();
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
        $url =  $protocol.'://'.$_SERVER['SERVER_NAME'].'?'.self::KEY_REF.'='.$user->ID;
        ?>
        <h2><?php _e("Партнёрская программа", "blank"); ?></h2>
        <h3><?php _e("Партнёрская ссылка", "blank"); ?></h3>
        <p><a href="<?php echo $url ?>"><?php echo $url ?></a></p>
    <?php }

    public function addRegisteredUsersList($user) {
        $rows = $this->dbService->getRegisteredUsers($user->ID);
        ?>
        <h3><?php _e("Пользователи зарегистрированные по партнёрской ссылке", "blank"); ?></h3>
        <table class="form-table">
            <tr>
                <td>
                    Имя пользователя
                </td>
                <td>
                    Nicename пользователя
                </td>
                <td>
                    Email
                </td>
                <td>
                    Дата и время
                </td>
            </tr>
            <?php foreach( $rows as $row ){ ?>
            <tr>
                <td>
                    <?php echo $row->user_login; ?>
                </td>
                <td>
                    <?php echo $row->user_nicename; ?>
                </td>
                <td>
                    <?php echo $row->user_email; ?>
                </td>
                <td>
                    <?php echo $row->created_at; ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    <?php }

    public function addConversionStatistics($user) {
        $rows = $this->dbService->getConversionStatistics($user->ID);
        ?>
        <h3><?php _e("Статистика переходов по партнёрской ссылке", "blank"); ?></h3>
        <table class="form-table">
            <tr>
                <td>
                    IP адресс
                </td>
                <td>
                    Переход со страницы
                </td>
                <td>
                    Переход на страницу
                </td>
                <td>
                    Дата и время
                </td>
            </tr>
            <?php foreach( $rows as $row ){ ?>
                <tr>
                    <td>
                        <?php echo $row->ip; ?>
                    </td>
                    <td>
                        <?php echo $row->url_from; ?>
                    </td>
                    <td>
                        <?php echo $row->url_to; ?>
                    </td>
                    <td>
                        <?php echo $row->created_at; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php }

    public function onPageLoad() {
        if (isset($_GET[self::KEY_REF])) {
            $this->dbService->createTables();
            $userIdPartner = (int)$_GET[self::KEY_REF];
            $this->dbService->insert( $this->dbService->getTableUsersPartnerLinksStats(), [
                'user_id_partner' => $userIdPartner,
                'ip' => $this->getUserIp(),
                'url_from' => wp_get_referer() ? wp_get_referer() : null,
                'url_to' => $_SERVER['REQUEST_URI']
            ] );
            setcookie(self::KEY_AFFILIATE_COOKIE, $userIdPartner, time()+YEAR_IN_SECONDS);
        }

        if (isset($_COOKIE[self::KEY_AFFILIATE_COOKIE])) {
            setcookie(self::KEY_AFFILIATE_COOKIE, $_COOKIE[self::KEY_AFFILIATE_COOKIE], time()+YEAR_IN_SECONDS);
        }
    }

    public function getUserIp() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function onUserRegister($userIdRegistered) {
        if (isset($_COOKIE[self::KEY_AFFILIATE_COOKIE])) {
            $userIdPartner = $_COOKIE[self::KEY_AFFILIATE_COOKIE];
            $this->dbService->insert( $this->dbService->getTableUsersPartnerRegistration(), ['user_id_partner' => $userIdPartner, 'user_id_registered' => $userIdRegistered] );
        }
    }

    public function addAffiliateAdminPage() {
        $rows = $this->dbService->getAllUsersStatistics();
        ?>
        <h2><?php _e("Статистика переходов по партнёрским ссылкам", "blank"); ?></h2>
        <table class="form-table">
            <tr>
                <td>
                    Партнёр
                </td>
                <td>
                    IP адресс
                </td>
                <td>
                    Переход со страницы
                </td>
                <td>
                    Переход на страницу
                </td>
                <td>
                    Дата и время
                </td>
            </tr>
            <?php foreach( $rows as $row ){ ?>
                <tr>
                    <td>
                        <?php echo $row->user_login; ?>
                    </td>
                    <td>
                        <?php echo $row->ip; ?>
                    </td>
                    <td>
                        <?php echo $row->url_from; ?>
                    </td>
                    <td>
                        <?php echo $row->url_to; ?>
                    </td>
                    <td>
                        <?php echo $row->created_at; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php }
}

$esaffiliate = new Esaffiliate(new DbService());
