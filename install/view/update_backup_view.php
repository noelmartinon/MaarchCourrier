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
?>
<script>
    function launchProcess(
        myVar
    ) {
        $(document).ready(function() {
            var oneIsEmpty = false;
            if (myVar.length < 1) {
                var oneIsEmpty = true;
            }

            if (oneIsEmpty) {
                $('#ajaxReturn_ko').html('<?php echo _MUST_CHOOSE_PATH_ROOT;?>');
                $('#ajaxReturn_button').css('display', 'block');
                return;
            }
            $('#ajaxReturn_ko').html('');

            ajax(
                'backupVersion',
                'myVar|' + myVar,
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
                <?php echo _UPDATE_BACKUP_INFOS;?>
            </h2>
        </div>
        <div class="contentBlock" id="database">
            <p>
                <h6>
                    <?php echo _UPDATE_BACKUP_DETAILS;?>
                </h6>
                <form>
                    <table>
                        <tr>
                            <td>
                                <?php echo _ACTUAL_VERSION_PATH;?>
                            </td>
                            <td>
                                :
                            </td>
                            <td>
                                <input type="text" id="actualPath" size="60" disabled name="actualPath" value="<?php echo $_SESSION['config']['corepath'];?>" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo _UPDATE_BACKUP_PATH;?>
                            </td>
                            <td>
                                :
                            </td>
                            <td>
                                <input type="text" id="path" size="60" name="backupPath" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td>
                                <input type="button" name="Submit" id="ajaxReturn_button" value="<?php echo _BACKUP_ACTUAL_VERSION;?>" onClick="$(this).css('display', 'none');launchProcess($('#path').val());" />
                            </td>
                        </tr>
                    </table>
                </form>
                <br />
                <div id="ajaxReturn_ko"></div>
                <div align="center">
                    <img src="img/wait.gif" width="100" class="wait" style="display: none; background-color: rgba(0, 0, 0, 0.2);" />
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
                    <a href="#" onClick="goTo('index.php?step=update_welcome');">
                        <?php echo _PREVIOUS_INSTALL;?>
                    </a>
                </div>
                <div style="float: right;" class="nextButton" id="next">
                    <a href="#" onClick="goTo('index.php?step=update_download');" id="ajaxReturn" style="display: none;">
                        <?php echo _NEXT_INSTALL;?>
                    </a>
                </div>
            </div>
            <br />
            <br />
        </p>
    </div>
</div>