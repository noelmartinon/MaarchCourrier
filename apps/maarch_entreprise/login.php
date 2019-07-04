<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   login
*
* @author  dev <dev@maarch.org>
* @ingroup apps
*/
if (isset($_GET['target_page']) && trim($_GET['target_page']) != '') {
    $_SESSION['target_page'] = $_GET['target_page'];
    if (trim($_GET['target_module']) != '') {
        $_SESSION['target_module'] = $_GET['target_module'];
    } elseif (trim($_GET['target_admin']) != '') {
        $_SESSION['target_admin'] = $_GET['target_admin'];
    }
}

$serverPath = '';

echo '<script>';
echo "localStorage.removeItem('PreviousV2Route');";
echo '</script>';

if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN'
    && strtoupper(substr(PHP_OS, 0, 3)) != 'WINNT'
) {
    $serverPath = str_replace('\\', DIRECTORY_SEPARATOR, $serverPath);
} else {
    $serverPath = str_replace('/', DIRECTORY_SEPARATOR, $serverPath);
}
$_SESSION['slash_env'] = DIRECTORY_SEPARATOR;
$tmpPath = explode(
    DIRECTORY_SEPARATOR, str_replace(
        '/', DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']
    )
);
$serverPath = implode(
    DIRECTORY_SEPARATOR, array_slice(
        $tmpPath, 0, array_search('apps', $tmpPath)
    )
).DIRECTORY_SEPARATOR;

$_SESSION['urltomodules'] = $_SESSION['config']['coreurl'].'modules/';
$_SESSION['urltocore'] = $_SESSION['config']['coreurl'].'core/';

if (isset($_SESSION['config']['corepath'])
    && !empty($_SESSION['config']['corepath'])
) {
    require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
        .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
        .'class_business_app_tools.php';
    require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
        .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
        .'class_login.php';
    $configCorePath = 'core'.DIRECTORY_SEPARATOR.'xml'
                      .DIRECTORY_SEPARATOR.'config.xml';
} else {
    require_once 'class'.DIRECTORY_SEPARATOR.'class_business_app_tools.php';
    require_once 'class'.DIRECTORY_SEPARATOR.'class_login.php';
    $configCorePath = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR
                      .'core'.DIRECTORY_SEPARATOR.'xml'
                      .DIRECTORY_SEPARATOR.'config.xml';
}

$core = new core_tools();
$businessAppTools = new business_app_tools();
$func = new functions();

$core->build_core_config($configCorePath);
$businessAppTools->build_business_app_config();

$core->load_modules_config($_SESSION['modules']);
$core->load_lang();
$core->load_app_services();
$core->load_modules_services($_SESSION['modules']);

//Reading base version
$businessAppTools->compare_base_version(
    'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
    .DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'applicationVersion.xml'
);

//LGI TEST FOR SMARTPHONE
// if ($core->detectSmartphone()) {
//     $confirmScript = '<script>';
//     $confirmScript .= 'if(confirm("' . _ACCESS_SMARTPHONE . '")){';
//     $confirmScript .= 'window.location.href="smartphone/hello.php"';
//     $confirmScript .= '}';
//     $confirmScript .= '</script>';
    
//     echo $confirmScript;
// }

$core->load_html();
$core->load_header('', true, false);
$time = $core->get_session_time_expire();

$loginObj = new login();
$loginMethods = array();
$loginMethods = $loginObj->build_login_method();
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
} else {
    $error = '';
}
$core->load_js();

if (
    file_exists('apps/maarch_entreprise/img/bodylogin.jpg') ||
    file_exists('custom/' . $_SESSION['custom_override_id'] . '/apps/maarch_entreprise/img/bodylogin.jpg')

) {
    echo "<body id='bodyloginCustom0'>";
} else {
    echo "<body id='bodylogin'>";
}

echo '<div id="bodyloginCustom">';

if (isset($_SESSION['error'])) {
    echo '<div class="error" id="main_error_popup" onclick="this.hide();">';
    echo $_SESSION['error'];
    echo '</div>';
}

if (isset($_SESSION['info'])) {
    echo '<div class="info" id="main_info" onclick="this.hide();">';
    echo $_SESSION['info'];
    echo '</div>';
}


//retrieve login message version
$db = new Database();
$query = "SELECT param_value_string FROM parameters WHERE id = 'loginpage_message'";
$stmt = $db->query($query, []);
$loginMessage = $stmt->fetchObject();

echo '<div id="loginpage">';
echo "<p id='logo'><img src='{$_SESSION['config']['businessappurl']}static.php?filename=logo.svg' alt='Maarch'/></p>";

echo '<div align="center">';

if ($loginMessage->param_value_string <> '') {
    echo $loginMessage->param_value_string;
}

echo '<h3>';
echo $_SESSION['config']['applicationname'];
echo '</h3>';
echo '</div>';

if (isset($_SESSION['error']) && $_SESSION['error'] != '') {
    echo '<script>';
    echo "var main_error = $('main_error_popup');";
    echo 'if (main_error != null) {';
    echo "main_error.style.display = 'table-cell';";
    echo "Element.hide.delay(10, 'main_error_popup');";
    echo '}';
    echo '</script>';
}

if (isset($_SESSION['info']) && $_SESSION['info'] != '') {
    echo '<script>';
    echo "var main_info = $('main_info');";
    echo 'if (main_info != null) {';
    echo "main_info.style.display = 'table-cell';";
    echo "Element.hide.delay(10, 'main_info');";
    echo '}';
    echo '</script>';
}
$loginObj->execute_login_script($loginMethods);

echo '</div>';

echo '</div>';

echo '</body>';
echo '</html>';
