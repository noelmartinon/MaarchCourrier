<?php
/*
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ajaxDelAttachment
* @author  dev <dev@maarch.org>
* @ingroup attachments
*/
if (isset($_POST['index'])) {
    if (!empty($_SESSION['upfile'][$_POST['index']])) {
        //RESET UPFILE SESSION
        $tmpUpfile = $_SESSION['upfile'];
        unset($_SESSION['upfile']);

        $_SESSION['upfile']['error'] = 0;
        $j = 0;
        for ($i = 0; $i < count($tmpUpfile); ++$i) {
            if ($i != $_POST['index'] && !empty($tmpUpfile[$i])) {
                $_SESSION['upfile'][$j] = $tmpUpfile[$i];
                ++$j;
            }
        }
    }
    //RESET CHRONO SESSION
    $tmpSaveChonoNumber = $_SESSION['save_chrono_number'];
    unset($_SESSION['save_chrono_number']);
    $j = 0;
    for ($i = 0; $i < count($tmpSaveChonoNumber); ++$i) {
        if ($i != $_POST['index']) {
            $_SESSION['save_chrono_number'][$j] = $tmpSaveChonoNumber[$i];
            ++$j;
        }
    }

    $status = 0;
    $error = '';
} else {
    $status = 0;
    $error = 'no index';
}
echo '{"status" : "'.$status.'", "content" : "", "error" : "'.addslashes($error).'", "exec_js" : ""}';
exit();
