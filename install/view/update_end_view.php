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
* @author Arnaud Veber
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
<div class="blockWrapper">
    <div class="titleBlock">
        <h2 onClick="slide('welcome');" style="cursor: pointer;">
            <?php echo _UPDATE_END;?>
        </h2>
    </div>
    <div class="contentBlock" id="welcome">
        <p>
            <?php echo _UPDATE_DESC_END;?>
        </p>
    </div>
</div>
<br />
<div class="blockWrapper">
    <div class="contentBlock" id="welcome">
        <p>
            <div id="buttons">
<!--                 <div style="float: left;" class="previousButton" id="previous">
                    <a href="#" onClick="goTo('index.php?step=update_deploy');">
                        <?php echo _PREVIOUS_INSTALL;?>
                    </a>
                </div> -->
                <div style="float: right;" class="nextButton" id="start">
                    <a href="#" onClick="goTo('../index.php');">
                        <?php echo _START_MEP_1_3;?>
                    </a>
                </div>
            </div>
            <br />
            <br />
        </p>
    </div>
</div>

