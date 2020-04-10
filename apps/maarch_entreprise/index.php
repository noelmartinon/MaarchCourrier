<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   index (THIS PAGE CAN NOT BE OVERWRITTEN IN A CUSTOM)
* @author  dev <dev@maarch.org>
* @ingroup apps
*/

/**
 * [PROCESS REQUEST]
 */
if (isset($_REQUEST['dir']) && !empty($_REQUEST['dir'])) {
    $_REQUEST['dir'] = str_replace("\\", "", $_REQUEST['dir']);
    $_REQUEST['dir'] = str_replace("/", "", $_REQUEST['dir']);
    $_REQUEST['dir'] = str_replace("..", "", $_REQUEST['dir']);
}

//reset orders in previous basket list
if (empty($_SESSION['current_basket'])) {
    $_SESSION['save_list']['start'] = 0;
    $_SESSION['save_list']['lines'] = "";
    $_SESSION['save_list']['order'] = "";
    $_SESSION['save_list']['order_field'] = "";
    $_SESSION['save_list']['template'] = "";
    $_SESSION['save_list']['full_count'] = 0;
}

// Useless ???
if (isset($_GET['show'])) {
    $show = $_GET['show'];
} else {
    $show = 'true';
}

// Useless ???
if (isset($_SESSION['user']['UserId'])
    && isset($_GET['page'])
    && !empty($_SESSION['user']['UserId']) && $_GET['page'] <> 'login'
    && $_GET['page'] <> 'log' && $_GET['page'] <> 'logout'
) {
    $db = new Database();
    $key = md5(
        time() . '%' . $_SESSION['user']['FirstName'] . '%'
        . $_SESSION['user']['UserId'] . '%' . $_SESSION['user']['UserId']
        . '%' . date('dmYHmi') . '%'
    );
}

/**
 * [Includes]
 */
include_once '../../core/class/class_functions.php';
include_once '../../core/class/class_db_pdo.php';
include_once '../../core/init.php';
include 'apps/maarch_entreprise/tools/maarchIVS/MaarchIVS.php';

if ($_SESSION['config']['usePHPIDS'] == 'true') {
    include 'apps/maarch_entreprise/phpids_control.php';
}

//SET custom path
if (isset($_SESSION['config']['corepath'])) {
    require_once 'core/class/class_db.php';
    require_once 'core/class/class_core_tools.php';
    $core = new core_tools();
    if (! isset($_SESSION['custom_override_id'])
        || empty($_SESSION['custom_override_id'])
    ) {
        $_SESSION['custom_override_id'] = $core->get_custom_id();
        if (! empty($_SESSION['custom_override_id'])) {
            $path = $_SESSION['config']['corepath'] . 'custom/'
                  . $_SESSION['custom_override_id'] . '/';
            set_include_path(
                $path . '/' . $_SESSION['config']['corepath']
            );
        }
    }
} else {
    require_once '../../core/class/class_db.php';
    require_once '../../core/class/class_core_tools.php';
    $core = new core_tools();
    $_SESSION['custom_override_id'] = $core->get_custom_id();
    chdir('../..');
    if (! empty($_SESSION['custom_override_id'])) {
        $path = $_SESSION['config']['corepath'] . 'custom/'
              . $_SESSION['custom_override_id'] . '/';
        set_include_path(
            $path . '/' . $_SESSION['config']['corepath']
        );
    }
}

if (!empty($_SESSION['user']['UserId'])) {
    $GLOBALS['login'] = $_SESSION['user']['UserId'];
}

if (!isset($_SESSION['user']['UserId'])
    && $_REQUEST['page'] <> 'login'
    && $_REQUEST['page'] <> 'log'
    && $_REQUEST['page'] <> 'logout'
) {
    $_SESSION['HTTP_REFERER'] = Url::requestUri();
    if (trim($_SERVER['argv'][0]) <> '') {
        header('location: reopen.php?' . $_SERVER['argv'][0]);
    } else {
        header('location: reopen.php');
    }
    exit();
}

if (!empty($_REQUEST['page']) && empty($_REQUEST['triggerAngular'])) {
    //V1
    $started = MaarchIVS::start(__DIR__ . '/xml/IVS/requests_definitions.xml', 'xml');
    $valid = MaarchIVS::run('silent');
    if (!$valid) {
        $validOutpout = MaarchIVS::debug();
        $cptValid = count($validOutpout['validationErrors']);
        $error = '';
        for ($cptV=0; $cptV<=$cptValid; $cptV++) {
            $message = $validOutpout['validationErrors'][$cptV]->message;
            if ($message == "Length id below the minimal length") {
                $message = _IVS_LENGTH_ID_BELOW_MIN_LENGTH;
            } elseif ($message == "Length exceeds the maximal length") {
                $message = _IVS_LENGTH_EXCEEDS_MAX_LENGTH;
            } elseif ($message == "Length is not allowed") {
                $message = _IVS_LENGTH_NOT_ALLOWED;
            } elseif ($message == "Value is not allowed") {
                $message = _IVS_VALUE_NOT_ALLOWED;
            } elseif ($message == "Format is not allowed") {
                $message = _IVS_FORMAT_NOT_ALLOWED;
            } elseif ($message == "Value is below the minimal value") {
                $message = _IVS_VALUE_BELOW_MIN_VALUE;
            } elseif ($message == "Value exceeds the maximal value") {
                $message = _IVS_LENGTH_EXCEEDS_MAX_LENGTH;
            } elseif ($message == "Too many digits") {
                $message = _IVS_TOO_MANY_DIGITS;
            } elseif ($message == "Too many decimal digits") {
                $message = _IVS_TOO_MANY_DECIMAL_DIGITS;
            }
            $error .= $message . PHP_EOL;
            $error .= $validOutpout['validationErrors'][$cptV]->parameter . PHP_EOL;
            $error .= $validOutpout['validationErrors'][$cptV]->value . PHP_EOL;
        }
        foreach ($_REQUEST as $name => $value) {
            if (is_string($value) && strpos($value, "<") !== false) {
                $value = preg_replace('/(<\/?script[^>]*>|<\?php|<\?[\s|\n|\r])/i', "", $value);
                $_REQUEST[$name] = $value;
                if (isset($_GET[$name]) && $_GET[$name] <> '') {
                    $_GET[$name] = $value;
                }
                if (isset($_POST[$name]) && $_POST[$name] <> '') {
                    $_POST[$name] = $value;
                }
            }
            $value = str_replace("\\", "", $value);
            $value = str_replace("/", "", $value);
            $value = str_replace("..", "", $value);
            $_REQUEST[$name] = $value;
            if (isset($_GET[$name]) && $_GET[$name] <> '') {
                $_GET[$name] = $value;
            }
            if (isset($_POST[$name]) && $_POST[$name] <> '') {
                $_POST[$name] = $value;
            }
        }
        //process error for ajax request
        if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            echo $error;
            exit;
        } else {
            //process error for standard request
            $_SESSION['error'] = $error;
        }
    }
    if (!isset($_SESSION['user']['UserId'])
        && $_REQUEST['page'] <> 'login'
        && $_REQUEST['page'] <> 'log'
        && $_REQUEST['page'] <> 'logout'
    ) {
        $_SESSION['HTTP_REFERER'] = Url::requestUri();
        if (trim($_SERVER['argv'][0]) <> '') {
            header('location: reopen.php?' . $_SERVER['argv'][0]);
        } else {
            header('location: reopen.php');
        }
        exit();
    }
    $core->load_lang();


    /**
     * [New Authentication System]
     */
    if ($_REQUEST['page'] <> 'login' && $_REQUEST['page'] <> 'log' && $_REQUEST['page'] <> 'logout') {
        $cookie = \SrcCore\models\AuthenticationModel::getCookieAuth();
        if (!empty($cookie) && \SrcCore\models\AuthenticationModel::cookieAuthentication($cookie)) {
            \SrcCore\models\AuthenticationModel::setCookieAuth(['userId' => $cookie['userId']]);
        } else {
            header('location: index.php?display=true&page=logout&logout=true');
        }
    }

    //INSERT PART OF PAGE
    if (isset($_REQUEST['display'])) {
        $core->insert_page();
        exit();
    }

    //DISPLAY FULL PAGE
    // if (isset($_SESSION['HTTP_REFERER'])) {
    //     $url = $_SESSION['HTTP_REFERER'];
    //     unset($_SESSION['HTTP_REFERER']);
    //     header('location: '.$url);
    // }
    $core->load_html();
    $core->load_header();

    
    /**
     * [Initialize cookie expiration]
     */
    echo "<script>checkCookieAuth();</script>";

    if (isset($_GET['body_loaded'])) {
        echo '<body style="background:#f2f2f2;" id="maarch_body">';
        echo "<div id='maarch_content' style='display:block;'>";
    } else {
        echo '<body style="background: url(\'static.php?filename=loading_big.gif\') no-repeat fixed center;" onload="$j(\'#maarch_body\').css(\'background\',\'f2f2f2\');$j(\'#maarch_body\').css(\'backgroundImage\',\'\');$j(\'#maarch_body\').css(\'backgroundUrl\', \'\');$j(\'#maarch_content\').css(\'display\',\'block\');" id="maarch_body">';
        echo "<div id='maarch_content' style='display:none;'>";
    }

    //GET COOKIE CLIENT SIDE
    if (empty($_SESSION['clientSideCookies'])) { ?>
        <script type="text/javascript">
            var path_manage_script = '<?php echo $_SESSION["config"]["businessappurl"]; ?>' + 'index.php?display=true&page=setProxyCookies';
            $j.ajax(
            {
                url: path_manage_script,
                type:'POST',
                dataType:'json',
                data: {
                    cookies : document.cookie
                },
                success: function(answer)
                {

                }
            });
        </script>
    <?php
    }

    $path = $_SESSION['config']['corepath'] . 'custom/'
      . $_SESSION['custom_override_id'] . '/apps/maarch_entreprise/template/header.html';

    //Display header
    if (file_exists($path)) {
        include_once('custom/' . $_SESSION['custom_override_id']
            . '/apps/maarch_entreprise/template/header.html');
    } else {
        include_once('apps/maarch_entreprise/template/header.html');
    }

    echo '<div id="container">';
    echo '<div id="content">';
    echo '<div class="error" id="main_error" onclick="this.hide();"></div>';

    echo '<div class="error" id="main_error_popup" onclick="this.hide();">';
    echo functions::xssafe($_SESSION['error']);
    echo '</div>';


    echo '<div class="info" id="main_info" onclick="this.hide();">';
    echo functions::xssafe($_SESSION['info']);
    echo '</div>';


    if (isset($_SESSION['error']) && $_SESSION['error'] <> '') {
        ?>
        <script>
            var main_error = $j('#main_error_popup');
            if (main_error != null) {
                main_error.css({"display":"table-cell"});
                Element.hide.delay(10, 'main_error_popup');
            }
        </script>
    <?php
    }

    if (isset($_SESSION['info']) && $_SESSION['info'] <> '') {
        ?>
        <script>
            var main_info = $j('#main_info');
            if (main_info != null) {
                main_info.css({"display":"table-cell"});
                Element.hide.delay(10, 'main_info');
            }
        </script>
        <?php
    }

    $core->insert_page();

    //FOOTER
    echo '<p id="footer">';
    if (isset($_SESSION['config']['showfooter']) && $_SESSION['config']['showfooter'] == 'true') {
        $core->load_footer();
    }
    echo '</p>';

    $_SESSION['error'] = '';
    $_SESSION['info'] = '';

    echo '</div>';
    echo '</div>';
    
    $core->view_debug();
    echo '</body>';
    echo '</html>';
    exit();
} else {
    $cookie = \SrcCore\models\AuthenticationModel::getCookieAuth();
    if (empty($cookie)) {
        header('location: index.php?display=true&page=logout&logout=true');
        exit();
    }
    $user = \User\models\UserModel::getByLogin(['login' => $cookie['userId'], 'select' => ['password_modification_date', 'status']]);

    //HTML CONTENT OF ANGULAR
    echo \SrcCore\models\CoreConfigModel::initAngularStructure();
    if ($user['status'] == 'ABS') {
        $_REQUEST['triggerAngular'] = 'activateUser';
    }

    $loggingMethod = \SrcCore\models\CoreConfigModel::getLoggingMethod();
    if (!in_array($loggingMethod['id'], ['sso', 'cas', 'ldap', 'keycloak', 'shibboleth'])) {
        $passwordRules = \SrcCore\models\PasswordModel::getEnabledRules();
        if (!empty($passwordRules['renewal'])) {
            $currentDate = new \DateTime();
            $lastModificationDate = new \DateTime($user['password_modification_date']);
            $lastModificationDate->add(new DateInterval("P{$passwordRules['renewal']}D"));

            if ($currentDate > $lastModificationDate) {
                $_REQUEST['triggerAngular'] = 'changePass';
            }
        }
    }
    
    if (isset($_SESSION['HTTP_REFERER'])) {
        $url = $_SESSION['HTTP_REFERER'];
        unset($_SESSION['HTTP_REFERER']);
        header('location: '.$url);
        exit;
    }

    //INIT ANGULAR MODE
    if (!empty($_REQUEST['triggerAngular']) && $_REQUEST['triggerAngular'] == 'changePass') {
        ?>
<script>
    triggerAngular('#/password-modification')
</script><?php
    } elseif (!empty($_REQUEST['triggerAngular']) && $_REQUEST['triggerAngular'] == 'activateUser') {
        ?><script>
    triggerAngular('#/activate-user')
</script><?php
    } elseif ($cookie['userId'] == 'superadmin' && !empty($_REQUEST['administration'])) {
        ?><script>
    triggerAngular('#/administration')
</script><?php
    } elseif (!empty($_REQUEST['scanGroupId']) && !empty($_REQUEST['tmpfilename'])) {
        ?><script>
    triggerAngular('#/indexing/<?php echo $_REQUEST['scanGroupId']?>?tmpfilename=<?php echo $_REQUEST['tmpfilename']?>')
</script><?php
    } elseif (empty($_REQUEST['page'])) {
        ?>
            <script>
                var route = '#/home';
                if(localStorage.getItem('PreviousV2Route') != null) {
                    route = '#' + localStorage.getItem('PreviousV2Route');
                }
                triggerAngular(route);
            </script>
        <?php
    }
}
