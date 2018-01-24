<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/
/**
* @brief Maarch version test
*
* @file
* @author dev@maarch.org
* @date $date$
* @version $Revision$
* @ingroup admin
*/

core_tools::load_lang();
$core_tools = new core_tools();
//$core_tools->test_admin('admin_update_control', 'apps');

$init = false;
if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == 'true') {
    $init = true;
}

$pagePath  = $_SESSION['config']['businessappurl'] . 'index.php?page=' . 'update_control&admin=update_control';
$pageLabel = _ADMIN_UPDATE_CONTROL;
$pageId    = 'update_control';
$level     = '';
if (isset($_REQUEST['level'])
    && ($_REQUEST['level'] == 2 || $_REQUEST['level'] == 3
        || $_REQUEST['level'] == 4 || $_REQUEST['level'] == 1)) {
    $level = $_REQUEST['level'];
}
$core_tools->manage_location_bar($pagePath, $pageLabel, $pageId, $init, $level);

//retrives tags
$client = new \Gitlab\Client('https://labs.maarch.org/api/v4/');
//$client->authenticate('aSecretToken', \Gitlab\Client::AUTH_URL_TOKEN);

// $project = $client->api('projects')->show('12');

$tags = $client->api('tags')->all('12');

//retrieve current version
$db             = new Database();
$query          = "SELECT param_value_int, param_value_string FROM parameters WHERE id = 'database_version'";
$stmt           = $db->query($query, []);
$currentVersion = $stmt->fetchObject();

$versionBranch         = substr($currentVersion->param_value_int, 0, 2) . '.' . substr($currentVersion->param_value_int, 2);
$currentVersionNumeric = preg_replace("/[^0-9,]/", "", $currentVersion->param_value_int);
if (!empty($currentVersion->param_value_string)) {
    $currentVersionTagNumeric = preg_replace("/[^0-9,]/", "", $currentVersion->param_value_string);
}

$allCurrentTags        = [];
$allNextTags           = [];
$cptCurrentTags        = 0;
$isAnyAvailableTag     = false;
$isAnyAvailableVersion = false;

foreach ($tags as $key => $value) {
    //echo $tags[$key]['name'] . ' ' . preg_replace("/[^0-9,]/", "", $tags[$key]['name']) . '<br />';
    if (!preg_match("/^\d{2}\.\d{2}\.\d+$/", $tags[$key]['name'])) {
        continue;
    }
    $tagNumeric = preg_replace("/[^0-9,]/", "", $tags[$key]['name']);
    $pos        = strpos($tagNumeric, $currentVersionNumeric);
    if ($pos === false) {
        //echo 'tag not in currentVersion:';
        $isAnyAvailableVersion = true;
        $allNextTags[] = $tags[$key]['name'];
    } else {
        //echo 'tag in currentVersion:';
        $allCurrentTags[$cptCurrentTags]            = [];
        $allCurrentTags[$cptCurrentTags]['name']    = $tags[$key]['name'];
        $allCurrentTags[$cptCurrentTags]['numeric'] = $tagNumeric;
        if ($tagNumeric > $currentVersionTagNumeric) {
            $allCurrentTags[$cptCurrentTags]['enabled'] = true;
            $isAnyAvailableTag = true;
        } else {
            $allCurrentTags[$cptCurrentTags]['enabled'] = false;
        }
        $cptCurrentTags++;
    }
}

?>
<h1><i class="fa fa-download fa-2x"></i> <?php echo _ADMIN_UPDATE_CONTROL;?></h1>

<table align="center" style="margin-top:100px">
    <tr>
        <td><?php echo _YOUR_VERSION;?></td>
        <td>:</td>
        <td>
            <?php echo '<b>' . $currentVersion->param_value_string . '</b> (' . _BRANCH_VERSION . ' : <b>' . $versionBranch . '</b>)';?>
        </td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td><?php echo _AVAILABLE_VERSION_TO_UPDATE;?></td>
        <td>:</td>
        <td>
            <?php
            if (count($tags)>0) {
                ?>
                <select id="version" id="name">
                    <?php
                    for ($i=0;$i<count($allCurrentTags);$i++) {
                        if ($allCurrentTags[$i]['enabled']) {
                            echo '<option value="' . $allCurrentTags[$i]['name'] . '">';
                            echo $allCurrentTags[$i]['name'];
                            echo '</option>';
                        } else {
                            echo '<option value="' . $allCurrentTags[$i]['name'] . '" disabled>';
                            echo $allCurrentTags[$i]['name'];
                            echo '</option>';
                        }
                    } ?>
                </select>
                <?php
            } else {
                echo _NO_AVAILABLE_TAG_TO_UPDATE . '<br />';
            }
            ?>
        </td>
    </tr>
    <tr><td colspan="3">&nbsp;</td></tr>
</table>

<div align="center" style="margin-bottom:150px">
    <?php
        if ($isAnyAvailableTag && count($tags)>0) {
            if ($_SESSION['user']['UserId'] != 'superadmin') {
                echo _CONNECT_YOU_IN_SUPERADMIN;
            } else {
                echo '<a style="margin-top:100px" href="'.$_SESSION['config']['coreurl'].'install/index.php?step=update_language"><input class="button" value="'._CLICK_HERE_TO_GO_TO_UPDATE_MANAGEMENT.'" type="button"></a>';
            }
        }

        if ($isAnyAvailableVersion) {
            echo '<br><br><br><b>' . _NEW_MAJOR_VERSION_AVAILABLE . '</b> : <br>';
            for ($j=0;$j<count($allNextTags);$j++) {
                echo $allNextTags[$j] . '<br />';
            }
        }
    ?>
</div>
