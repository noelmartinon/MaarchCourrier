<?php
/*
*   Copyright 2008-2011 Maarch
*
*  This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief Maarch index page : every php page is loaded with this page
*
* @file
* @author  Claire Figueras  <dev@maarch.org>
* @author  Laurent Giovannoni <dev@maarch.org>
* @author  Loic Vinet  <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup apps
*/
include_once('../../core/class/class_functions.php');
include_once '../../core/init.php';
if (isset($_SESSION['config']['corepath'])) {
    require_once 'core/class/class_functions.php';
    require_once 'core/class/class_db.php';
    require_once 'core/class/class_core_tools.php';
    $core = new core_tools();
    if (! isset($_SESSION['custom_override_id'])
        || empty($_SESSION['custom_override_id'])
    ) {
        $_SESSION['custom_override_id'] = $core->get_custom_id();
        if (! empty($_SESSION['custom_override_id'])) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                  . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                  . DIRECTORY_SEPARATOR;
            set_include_path(
                $path . PATH_SEPARATOR . $_SESSION['config']['corepath']
            );
        }
    }
} else {
    require_once '../../core/class/class_functions.php';
    require_once '../../core/class/class_db.php';
    require_once '../../core/class/class_core_tools.php';
    $core = new core_tools();
    $_SESSION['custom_override_id'] = $core->get_custom_id();
    chdir('../..');
    if (! empty($_SESSION['custom_override_id'])) {
        $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
              . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR;
        set_include_path(
            $path . PATH_SEPARATOR . $_SESSION['config']['corepath']
        );
    }
}
if (isset($_SESSION['user']['UserId']) && isset($_GET['page'])
    && ! empty($_SESSION['user']['UserId']) && $_GET['page'] <> 'login'
    && $_GET['page'] <> 'log' && $_GET['page'] <> 'logout'
) {
    $db = new dbquery();
    $db->connect();
    $key = md5(
        time() . '%' . $_SESSION['user']['FirstName'] . '%'
        . $_SESSION['user']['UserId'] . '%' . $_SESSION['user']['UserId']
        . '%' . date('dmYHmi') . '%'
    );

    $db->query(
        'update ' . $_SESSION['tablename']['users'] . " set cookie_key = '"
        . $key . "', cookie_date = ".$db->current_datetime()." where user_id = '"
        . $_SESSION['user']['UserId'] . "' and mail = '"
        . $_SESSION['user']['Mail'] . "'", 1
    );

    setcookie(
        'maarch', 'UserId=' . $_SESSION['user']['UserId'] . '&key=' . $key,
        time() + ($_SESSION['config']['cookietime'] * 1000)
    );
}

// CV 31 oct 2014 : clean request
foreach ($_REQUEST as $name => $value) {
    if (is_string($value) && strpos($value, "<") !== false) {
        $value = preg_replace('/(<\/?script[^>]*>|<\?php)/i', "", $value);
        $_REQUEST[$name] = $value;
    }
}

if (isset($_REQUEST['display'])) {
     $core->insert_page();
     exit();
}

if (! isset($_SESSION['user']['UserId'])) {

    $_SESSION['HTTP_REFERER'] = Url::requestUri();
    if (trim($_SERVER['argv'][0]) <> '') {
        header('location: reopen.php?' . $_SERVER['argv'][0]);
    } else {
        header('location: reopen.php');
    }
    exit();
}

if (isset($_GET['show'])) {
    $show = $_GET['show'];
} else {
    $show = 'true';
}

$core->start_page_stat();
$core->configPosition();
if (isset($_SESSION['HTTP_REFERER'])) {
    $url = $_SESSION['HTTP_REFERER'];
    unset($_SESSION['HTTP_REFERER']);
    header('location: '.$url);
}
$core->load_lang();
$core->load_html();
$core->load_header();
$time = $core->get_session_time_expire();

?>
<body onload="session_expirate(<?php echo $time;?>, '<?php 
    echo $_SESSION['config']['businessappurl'];
    ?>index.php?display=true&page=logout&coreurl=<?php 
    echo $_SESSION['config']['coreurl'];
    ?>&logout=true');" id="maarch_body">
<div id="header">
        <div id="nav">
            <div id="menu" onmouseover="ShowHideMenu('menunav','on');" onmouseout="ShowHideMenu('menunav','off');" class="off">
                <p>
                    <img src="<?php  echo $_SESSION['config']['businessappurl'];?>static.php?filename=but_menu.gif" alt="<?php  echo _MENU;?>" />
                </p>
                <div id="menunav" style="display: none;">
                <?php
                echo '<div class="header_menu"><div class="user_name_menu">'.$_SESSION['user']['FirstName'].' '.$_SESSION['user']['LastName'].'</div></div>';
                echo '<div class="header_menu_blank">&nbsp;</div>';?>
                <ul  >
                    <?php
                    //here we building the maarch menu
                    $core->build_menu($_SESSION['menu']);
                   ?>
                </ul>
                     <?php
                    echo '<div class="header_menu_blank">&nbsp;</div>';
                    echo '<div class="footer_menu"><a style="color:white;" href="'.$_SESSION['config']['businessappurl'].'index.php?page=maarch_credits">';
                    echo ''._MAARCH_CREDITS.'</a></div>';?>
                </div>
            </div>
            <div><p id="ariane"><?php
            ?></p></div>
            <p id="gauchemenu"><img src="<?php  echo $_SESSION['config']['businessappurl'];?>static.php?filename=bando_tete_gche.gif" alt=""/></p>
            <p id="logo"><a href="index.php"><img src="<?php  echo $_SESSION['config']['businessappurl'];?>static.php?filename=bando_tete_dte.gif" alt="<?php  echo _LOGO_ALT;?>" /></a></p>
       </div>
</div>
    <div id="container">
        <div id="content">
            <div class="error" id="main_error">
                <?php  echo $_SESSION['error'];?>
            </div>
            <div class="info" id="main_info">
                <?php  if(isset($_SESSION['info'])){echo $_SESSION['info'];}?>
            </div>
            <?php
            if ($core->is_module_loaded('basket')
                && isset($_SESSION['abs_user_status'])
                && $_SESSION['abs_user_status'] == true) {
                include
                    'modules' . DIRECTORY_SEPARATOR . 'basket'
                    . DIRECTORY_SEPARATOR . 'advert_missing.php';
            } else {
              $core->insert_page();
            }
            ?>
        </div>
        <p id="footer">
            <?php
            if (isset($_SESSION['config']['showfooter'])
                && $_SESSION['config']['showfooter'] == 'true'
            ) {
                $core->load_footer();
            }
            ?>
        </p>
        <?php
        $_SESSION['error'] = '';
        $_SESSION['info'] = '';
        $core->view_debug();
        ?>
    </div>

<script type="text/javascript">//HideMenu('menunav');</script>
</body>
</html>
