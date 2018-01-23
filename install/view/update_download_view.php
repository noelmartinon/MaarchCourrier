<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief class of install tools
*
* @file
* @author Laurent Giovannoni
* @date $date$
* @version $Revision$
* @ingroup install
*/
if ($_SESSION['user']['UserId'] <> 'superadmin') {
    header('location: ' . $_SESSION['config']['businessappurl']
        . 'index.php?page=update_control&admin=update_control');
    exit();
}

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
        $allCurrentTags[$cptCurrentTags] = [];
        $allCurrentTags[$cptCurrentTags]['name'] = $tags[$key]['name'];
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

// require_once('install/class/Class_Install.php');
// $Class_Install = new Install;

?>
<script>
    function launchProcess(myVar) {
        $(document).ready(function() {
            var oneIsEmpty = false;
            if (myVar.length < 1) {
                var oneIsEmpty = true;
            }

            if (oneIsEmpty) {
                $('#ajaxReturn_ko').html('<?php echo _MUST_CHOOSE_VERSION;?>');
                $('#ajaxReturn_button').css('display', 'block');
                return;
            }
            $('#ajaxReturn_ko').html('');

            ajax(
                'downloadVersion',
                'myVar|'+myVar,
                'ajaxReturn',
                'false'
            );

        });
    }
</script>

<div class="ajaxReturn_testConnect">
    <div class="blockWrapper">
        <div class="titleBlock">
            <h2 onClick="slide('database');" style="cursor: pointer;">
                <?php echo _LAST_RELEASE_INFOS;?>
            </h2>
        </div>
        <div class="contentBlock" id="database">
            <p>
                <h6>
                    <?php echo _LAST_RELEASE_DETAILS;?>
                </h6>
                <form>
                    <table align="center" style="margin-top:50px">
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
                            <td><?php echo _CHOOSE_VERSION_TO_UPDATE;?></td>
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

                        <?php if(!$Class_Install->isPhpRequirements('zip')){ ?>
                        <tr><td colspan="3">&nbsp;</td></tr>
                        <tr><td><?php echo _MISSING_PREREQUISITE_UPDATE ;?></td><td colspan="2"></td></tr>
                        <tr>
                            <td>
                                <?php echo $Class_Install->checkPrerequisites(false);?>
                            </td>
                            <td></td>
                            <td>
                                <?php echo _ZIP_LIB;?>
                            </td>
                        </tr>
                        <?php } else { ?>
                        <tr>
                            <td colspan="3">
                                <input
                                  type="button"
                                  name="Submit" id="ajaxReturn_button"  value="<?php echo _DOWNLOAD_VERSION;?>"
                                  onClick="$(this).css('display', 'none');launchProcess($('#version').val());"
                                />
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                    <div align="center" style="margin-bottom:50px">
                        <?php
                            if ($isAnyAvailableVersion) {
                                echo '<br><br><br><b>' . _NEW_MAJOR_VERSION_AVAILABLE . '</b> : <br>';
                                for ($j=0;$j<count($allNextTags);$j++) {
                                    echo $allNextTags[$j] . '<br />';
                                }
                            }
                        ?>
                    </div>
                </form>
                <br />
                <div id="ajaxReturn_ko"></div>
                <div align="center">
                    <img src="img/wait.gif" width="100" class="wait" style="display: none; background-color: rgba(0, 0, 0, 0.2);"/>
                </div>
            </p>
        </div>
    </div>
</div>
<br />
<div class="blockWrapper">
    <div class="contentBlock">
        <p>
            <div id="buttons">
                <div style="float: left;" class="previousButton" id="previous">
                    <a href="#" onClick="goTo('index.php?step=update_backup');">
                        <?php echo _PREVIOUS_INSTALL;?>
                    </a>
                </div>
                <div style="float: right;" class="nextButton" id="next">
                    <a href="#" onClick="goTo('index.php?step=update_deploy');" class="ajaxReturn" id="ajaxReturn_ok" style="display: none;">
                        <?php echo _NEXT_INSTALL;?>
                    </a>
                </div>
            </div>
            <br />
            <br />
        </p>
    </div>
</div>
