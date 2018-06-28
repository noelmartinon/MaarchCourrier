<?php

/*
*   Copyright 2015 Maarch
*
*   This file is part of Maarch Framework.
*
* @author  dev <dev@maarch.org>
* @ingroup attachments
*/
$core = new core_tools();
$core->test_user();

?>

<script type="text/javascript">
var input_res = window.opener.$('res_id_link');
<?php 
if (_ID_TO_DISPLAY == 'chrono_number') {
    ?>
    var input_chrono_id = window.opener.$('input_chrono_id');
<?php 
    require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';
    $db = new Database();
    $arrayChrono = array();
    foreach ($_SESSION['stockCheckbox'] as $key => $value) {
        $stmt = $db->query('SELECT alt_identifier FROM mlb_coll_ext WHERE res_id = ?', array($value));
        $res = $stmt->fetchObject();
        $chrono = $res->alt_identifier;
        array_push($arrayChrono, $chrono);
    }
} ?>

//VALIDATE_MAIL
if (input_res) {
	input_res.value=<?php echo json_encode($_SESSION['stockCheckbox']); ?>;
        <?php 
        if (_ID_TO_DISPLAY == 'chrono_number') {
            ?>
            input_chrono_id.value=<?php echo json_encode($arrayChrono); ?>;       
        <?php

        } ?>
	window.opener.$('attach_link').click();
} else { //INDEX_MLB
	window.opener.$('res_id').value=<?php echo json_encode($_SESSION['stockCheckbox']); ?>;
    <?php 
    if (_ID_TO_DISPLAY == 'chrono_number') {
        ?>
        window.opener.$('chrono_id').value=<?php echo json_encode($arrayChrono); ?>;
    <?php

    } ?>
}

self.close();
</script>