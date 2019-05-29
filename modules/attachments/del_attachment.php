<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   del_attachment
*
* @author  dev <dev@maarch.org>
* @ingroup attachments
*/
require_once 'core/class/class_security.php';
require_once 'core/class/class_request.php';
require_once 'core/class/class_resource.php';
require_once 'core/class/class_history.php';
require_once 'modules/attachments/attachments_tables.php';
require_once 'modules/attachments/class/attachments_controler.php';
$core = new core_tools();
$core->load_lang();

if (empty($_REQUEST['rest'])) {
    $core->load_js();
}

$func = new functions();
$ac = new attachments_controler();
$db = new Database();

if (!empty($_REQUEST['docId'])) {
    $_SESSION['doc_id'] = $_REQUEST['docId'];
}

//RETRIEVE INFO CURRENT ATTACHMENT
$info_doc = $ac->getAttachmentInfos($_REQUEST['id']);
$pdf_id = $ac->getCorrespondingPdf($_REQUEST['id']);

//DEL CURRENT ATTACHMENT
$stmt = $db->query("UPDATE {$info_doc['target_table_origin']}  SET status = 'DEL' WHERE res_id = ?", array($_REQUEST['id']));

//DEL CONVERTED PDF
if ($pdf_id != 0) {
    $stmt = $db->query('UPDATE '.RES_ATTACHMENTS_TABLE." SET status = 'DEL' WHERE res_id = ?", array($pdf_id));
}

// IS VERSION ATTACHMENT ?
if ($info_doc['is_version'] == true && $info_doc['status'] != 'TMP') {
    //DEL PREVIOUS ATTACHMENT VERSION
    $stmt = $db->query('SELECT attachment_id_master FROM res_version_attachments WHERE res_id = ?', array($_REQUEST['id']));
    $res = $stmt->fetchObject();
    $stmt = $db->query("UPDATE res_version_attachments SET status = 'DEL' WHERE attachment_id_master = ?", array($res->attachment_id_master));
    $stmt = $db->query("UPDATE res_attachments SET status = 'DEL' WHERE res_id = ?", array($res->attachment_id_master));
}

//LOG DELETE ACTION IN HISTORY
if ($_SESSION['history']['attachdel'] == 'true') {
    $hist = new history();
    $hist->add(
        $view,
        $resIdMaster,
        'DEL',
        'attachdel',
        _ATTACH_DELETED.' '._ON_DOC_NUM
        .$info_doc['res_id_master'].'  ('.$_REQUEST['id'].')',
        $_SESSION['config']['databasetype'],
        'attachments'
    );
    $hist->add(
        RES_ATTACHMENTS_TABLE,
        $_REQUEST['id'],
        'DEL',
        'attachdel',
        _ATTACH_DELETED.' : '
        .$_REQUEST['id'],
        $_SESSION['config']['databasetype'],
        'attachments'
    );
    if (empty($_REQUEST['rest'])) {
        echo '<script>$j("#main_error",window.parent.document).html(\''._ATTACH_DELETED.' : '.$_REQUEST['id'].'\').show().delay(5000).fadeOut();</script>';
    }
}

//SIGNATURE BOOK
if (!empty($_REQUEST['rest'])) {
    echo '{"status" : "ok"}';
    exit;
}

//REFRESH TABS
if (empty($_REQUEST['rest'])) {
    $query = "SELECT count(1) as total FROM res_view_attachments WHERE status NOT IN ('DEL','OBS','TMP') and res_id_master = ?";
    if (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'attachments') {
        $query .= " and (attachment_type <> 'response_project' and attachment_type <> 'outgoing_mail_signed' and attachment_type <> 'signed_response' and attachment_type <> 'converted_pdf' and attachment_type <> 'outgoing_mail' and attachment_type <> 'print_folder' and attachment_type <> 'aihp')";
    } elseif (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'response') {
        $query .= " and (attachment_type = 'response_project' or attachment_type = 'outgoing_mail_signed' or attachment_type = 'outgoing_mail' or attachment_type = 'signed_response' or attachment_type = 'aihp')";
    } else {
        $query .= " and attachment_type NOT IN ('converted_pdf','print_folder')";
    }
    $stmt = $db->query($query, array($info_doc['res_id_master']));
    $new_nb_attach = $stmt->total;
    ?>
    <script type="text/javascript">
        var eleframe1 =  parent.document.getElementsByName('list_attach');
        if(eleframe1[0] === undefined){
            eleframe1 =  parent.document.getElementsByName('uniqueDetailsIframe');
        }
        var nb_attach = '<?php functions::xecho($new_nb_attach); ?>';
        <?php if (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'attachments') {
        ?>
            eleframe1[0].src = "<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=frame_list_attachments&load&attach_type_exclude=response_project,signed_response,outgoing_mail_signed,converted_pdf,outgoing_mail,print_folder,aihp&fromDetail=attachments'; ?>";
        <?php
    } elseif (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'response') {
            ?>
            eleframe1[0].src = "<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=frame_list_attachments&load&attach_type=response_project,outgoing_mail_signed,signed_response,outgoing_mail,aihp&fromDetail=response'; ?>";
        <?php
        } else {
            ?>
            parent.document.getElementById('list_attach').src = "<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&template_selected=documents_list_attachments_simple&page=frame_list_attachments&load&attach_type_exclude=converted_pdf,print_folder'; ?>";
        <?php
        } ?>
    </script>
<?php
}
