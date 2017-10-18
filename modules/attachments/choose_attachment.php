<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   choose_attachment
* @author  dev <dev@maarch.org>
* @ingroup core
*/

$core_tools = new core_tools();
$core_tools->load_lang();
$func = new functions();
$core_tools->load_html();
$core_tools->load_header('', true, false);
$upFileOK = false;
?>
    
    <?php
    $_SESSION['upfile']['error'] = 0;
    if (isset($_FILES['file']['error']) && $_FILES['file']['error'] == 1) {
        $_SESSION['upfile']['error'] = $_FILES['file']['error'];
        if ($_SESSION['upfile']['error'] == 1) {
            $_SESSION['error'] = 'La taille du fichier telecharge excede la valeur de upload_max_filesize';
        }
    } elseif (!empty($_FILES['file']['tmp_name']) && $_FILES['file']['error'] <> 1) {
        $_SESSION['error'] = '';
        $_SESSION['upfile']['tmp_name'] = $_FILES['file']['tmp_name'];
        $extension = explode(".", $_FILES['file']['name']);
        $name_without_ext = substr($_FILES['file']['name'], 0, strrpos($_FILES['file']['name'], "."));
        echo '<script>window.parent.document.getElementById(\'title\').value=\''.$name_without_ext.'\';</script>';
        $count_level = count($extension)-1;
        $the_ext = $extension[$count_level];
        $fileNameOnTmp = 'tmp_file_' . $_SESSION['user']['UserId']
            . '_' . rand() . '.' . strtolower($the_ext);
            $_SESSION['upfile']['fileNameOnTmp'] = $fileNameOnTmp;
        $filePathOnTmp = $_SESSION['config']['tmppath'] . $fileNameOnTmp;

        if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
                $_SESSION['error'] = _FILE_NOT_SEND . ". " . _TRY_AGAIN
                    . ". " . _MORE_INFOS . " (<a href=\"mailto:"
                    . $_SESSION['config']['adminmail'] . "\">"
                    . $_SESSION['config']['adminname'] . "</a>)";
        } else {
            include_once 'core/docservers_tools.php';
            $arrayIsAllowed = array();
            $arrayIsAllowed = Ds_isFileTypeAllowed($_FILES['file']['tmp_name'], strtolower($the_ext));
            if ($arrayIsAllowed['status'] == false) {
                $_SESSION['error'] = _WRONG_FILE_TYPE . ' ' . $arrayIsAllowed['mime_type'];
                $_SESSION['upfile'] = array();
            } elseif (!@move_uploaded_file($_FILES['file']['tmp_name'], $filePathOnTmp)) {
                $_SESSION['error'] = _FILE_NOT_SEND . ". " . _TRY_AGAIN . ". "
                    . _MORE_INFOS . " (<a href=\"mailto:"
                    . $_SESSION['config']['adminmail'] . "\">"
                    . $_SESSION['config']['adminname'] . "</a>)";
            } else {
                $_SESSION['upfile']['size'] = $_FILES['file']['size'];
                $_SESSION['upfile']['mime'] = $_FILES['file']['type'];
                $_SESSION['upfile']['local_path'] = $filePathOnTmp;
                //$_SESSION['upfile']['name'] = $_FILES['file']['name'];
                $_SESSION['upfile']['name'] = $fileNameOnTmp;
                $_SESSION['upfile']['format'] = $the_ext;
                $upFileOK = true;

                if (strtolower($_SESSION['upfile']['format']) == 'pdf') {
                    echo '<script>window.parent.document.getElementById(\'viewframevalid_attachment\').src=\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=file_iframe&#navpanes=0'.$_SESSION['upfile']['local_path'].'\';window.parent.document.getElementById(\'liAttachement\').click()</script>';
                }
            }
        }
    }
    ?>
    <body>
    <form name="select_file_form" id="select_file_form" method="get" enctype="multipart/form-data" action="<?php
        echo $_SESSION['config']['businessappurl'];
        ?>index.php?display=true&module=attachments&page=choose_attachment" class="forms">
        <input type="hidden" name="display" value="true" />
        <input type="hidden" name="dir" value="indexing_searching" />
        <input type="hidden" name="page" value="choose_attachment" />
        <?php
        if (!empty($_SESSION['upfile']['local_path']) && empty($_SESSION['error'])) { 

                ?>
                <i class="fa fa-check-square fa-2x" title="<?php echo _DOWNLOADED_FILE; ?>"></i>
                <input type="button" id="fileButton" onclick="$j('#file').click();" class="button"
                       value="<?php if($_REQUEST['with_file'] == 'true'){ echo _WITHOUT_FILE; } else {echo $_FILES['file']['name']; }?>"
                       title="<?php if($_REQUEST['with_file'] == 'true'){ echo _WITHOUT_FILE; } else {echo $_FILES['file']['name']; }?>"
                       style="width: 85%;margin: 0px;margin-top: -10px;font-size: 12px;text-align: center;text-overflow: ellipsis;overflow: hidden;">
            <?php } elseif (!empty($_SESSION['error'])) { ?>
                <i class="fa fa-remove fa-2x" title="<?php echo $_SESSION['error']; ?>"></i>
                <input type="button" id="fileButton" onclick="$j('#file').click();" class="button" value="<?php echo $_SESSION['error']; ?>" style="width: 85%;margin: 0px;margin-top: -10px;font-size: 12px;text-align: center;text-overflow: ellipsis;overflow: hidden;">
            <?php } else { ?>
                <i class="fa fa-remove fa-2x" title="<?php echo _NO_FILE_SELECTED; ?>"></i>
                <input type="button" id="fileButton" onclick="$j('#file').click();" class="button" value="<?php echo _CHOOSE_FILE; ?>" style="width: 85%;margin: 0px;margin-top: -10px;font-size: 12px;text-align: center;text-overflow: ellipsis;overflow: hidden;">
            <?php } ?>
        <p style="display:none">
        <!-- window.parent.$('title').value = this.value.substring(0,this.value.indexOf('.')); -->
            <input type="file" name="file" id="file" onchange="$('with_file').value='false';this.form.method = 'post';this.form.submit();" value="<?php
                if (isset($_SESSION['file_path'])) {
                    echo $_SESSION['file_path'];
                } ?>" style="width:200px" />
        </p>
        <p style="display:none">
            <div align="center">
                <input type="radio" name="with_file" id="with_file" value="false" onclick="this.form.method = 'post';this.form.submit();" />
            </div>
        </p>
    </form>
    <?php 
    if (!empty($_SESSION['upfile']['local_path']) && empty($_SESSION['error'])) { 
        //launch auto convert in PDF
        if (in_array(strtolower($_SESSION['upfile']['format']), array('odt','docx','doc','docm'))) {
        //if (strtolower($_SESSION['upfile']['format']) == 'odt' || strtolower($_SESSION['upfile']['format']) == 'docx') {
            echo "<script>window.parent.$('add').value='Edition en cours ...';window.parent.editingDoc('superadmin');window.parent.$('add').disabled='disabled';window.parent.$('add').style.opacity='0.5';</script>";
            include_once 'modules/content_management/class/class_content_manager_tools.php';

            $cM = new content_management_tools();
            if (
                file_exists('custom'.DIRECTORY_SEPARATOR. $_SESSION['custom_override_id']
                            . DIRECTORY_SEPARATOR . 'modules'. DIRECTORY_SEPARATOR . 'content_management'
                            . DIRECTORY_SEPARATOR . 'applet_controller.php'
                )
            ) {
                $path = 'custom/'. $_SESSION['custom_override_id'] .'/modules/content_management/applet_controller.php';
            } else {
                $path = 'modules/content_management/applet_controller.php';
            }
            $path_appli = explode('/', $_SESSION['config']['coreurl']);
            if (count($path_appli) <> 5) {
                $path_appli = array_slice($path_appli, 0, 4);
                $path_appli = implode('/', $path_appli);
            } else {
                $path_appli = implode('/', $path_appli);
            }
            // require_once 'core/class/class_db_pdo.php';
            // $database = new Database();
            // $query = "select template_id from templates where template_type = 'OFFICE' and template_target = 'attachments'";
            // $stmt = $database->query($query);
            // $aTemplateId = $stmt->fetchObject()->template_id;
            $cookieKey = $_SESSION['sessionName'] . '=' . $_COOKIE[$_SESSION['sessionName']];

            $onlyConvert = "true";
            $cM->generateJNLP(
                $path_appli,
                $path_appli . '/' . $path,
                'newAttachment',
                'res_letterbox',
                //$aTemplateId,
                $_SESSION['doc_id'],
                '',
                $cookieKey,
                $_SESSION['user']['UserId'],
                $_SESSION['clientSideCookies'], 
                $_SESSION['modules_loaded']['attachments']['convertPdf'],
                $onlyConvert
            );
        }
    }
    $core_tools->load_js();?>
    </body>
</html>
!
