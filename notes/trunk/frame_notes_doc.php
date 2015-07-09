<?php  /**
* File : frame_notes_doc.php
*
* Frame, shows the notes of a document
*
* @package Maarch LetterBox 2.3
* @version 1.0
* @since 06/2006
* @license GPL
* @author  Claire Figueras  <dev@maarch.org>
*/

$core = new core_tools();
//here we loading the lang vars
$core->load_lang();
$core->test_service('manage_notes_doc', 'notes');
$func = new functions();

if (empty($_SESSION['collection_id_choice'])) {
    $_SESSION['collection_id_choice'] = $_SESSION['user']['collections'][0];
}

require_once "core" . DIRECTORY_SEPARATOR . "class" . DIRECTORY_SEPARATOR
    . "class_request.php";
require_once "apps" . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
    . DIRECTORY_SEPARATOR . "class" . DIRECTORY_SEPARATOR
    . "class_list_show.php";
require_once 'modules/notes/notes_tables.php';
require_once 'modules/entities/entities_tables.php';
require_once 'core/core_tables.php';
require_once "modules" . DIRECTORY_SEPARATOR . "notes" . DIRECTORY_SEPARATOR
    . "class" . DIRECTORY_SEPARATOR
    . "class_modules_tools.php";
$notes_tools = new notes();

$func = new functions();
$select[NOTES_TABLE] = array();
array_push(
    $select[NOTES_TABLE], "id", "date_note",
    "note_text", "user_id"
);
$select[USERS_TABLE] = array();
array_push(
    $select[USERS_TABLE], "user_id", "lastname", "firstname"
);

$where = " identifier = ?";
$arrayPDO = array($_SESSION['doc_id']);

$request = new request;

$tabNotes = $request->PDOselect(
    $select, $where, $arrayPDO, "order by " . NOTES_TABLE. ".date_note desc",
    $_SESSION['config']['databasetype'], "500", true, NOTES_TABLE, USERS_TABLE,
    "user_id"
);

//$request->show();
//$request->show_array($tabNotes);

$indNotes1d = '';


if (isset($_GET['size']) && $_GET['size'] == "full") {
    $sizeMedium = "15";
    $sizeSmall = "10";
    $sizeFull = "300";
    $css = "listing spec detailtabricatordebug";
    $body = "";
    $cutString = 300;
    $extendUrl = "&size=full";
} elseif (isset($_GET['size']) && $_GET['size'] == "middle") {
    $sizeMedium = "15";
    $sizeSmall = "10";
    $sizeFull = "50";
    $css = "listingsmall spec";
    $body = "";
    $cutString = 100;
    $extendUrl = "&size=middle";
} else {
    $sizeMedium = "18";
    $sizeSmall = "10";
    $sizeFull = "30";
    $css = "listingsmall";
    $body = "iframe";
    $cutString = 20;
    $extendUrl = "";
}

$arrayToUnset = array();

for ($indNotes1 = 0; $indNotes1 < count($tabNotes); $indNotes1 ++ ) {
    for ($indNotes2 = 0; $indNotes2 < count($tabNotes[$indNotes1]); $indNotes2 ++) {
        foreach (array_keys($tabNotes[$indNotes1][$indNotes2]) as $value) {
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "id") {
                $tabNotes[$indNotes1][$indNotes2]["id"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = 'ID';
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeSmall;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = true;
                $indNotes1d = $tabNotes[$indNotes1][$indNotes2]['value'];
                //echo $tabNotes[$indNotes1][$indNotes2]['value'] . '<br>';
                if (!$notes_tools->getNotes(
                    $tabNotes[$indNotes1][$indNotes2]['value'], 
                    $_SESSION['user']['UserId'], 
                    $_SESSION['user']['primaryentity']['id']
                    )
                ) {
                    //unset($tabNotes[$indNotes1]);
                    //echo 'sort ' . $indNotes1 . '<br>';
                    array_push($arrayToUnset, $indNotes1);
                } else {
                    //echo 'garde ' . $indNotes1 . '<br>';
                }
            }
        }
    }
}

for ($cptUnset=0;$cptUnset<count($arrayToUnset);$cptUnset++ ) {
    unset($tabNotes[$arrayToUnset[$cptUnset]]);
}
array_multisort($tabNotes, SORT_DESC);
//$request->show_array($tabNotes);
for ($indNotes1 = 0; $indNotes1 < count($tabNotes); $indNotes1 ++ ) {
    for ($indNotes2 = 0; $indNotes2 < count($tabNotes[$indNotes1]); $indNotes2 ++) {
        foreach (array_keys($tabNotes[$indNotes1][$indNotes2]) as $value) {
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "id") {
                $tabNotes[$indNotes1][$indNotes2]["id"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = 'ID';
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeSmall;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = false;
                $indNotes1d = $tabNotes[$indNotes1][$indNotes2]['value'];
            }
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "user_id") {
                $tabNotes[$indNotes1][$indNotes2]["user_id"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = _ID;
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeSmall;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = false;
            }
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "lastname") {
                $tabNotes[$indNotes1][$indNotes2]['value'] = $request->show_string(
                    $tabNotes[$indNotes1][$indNotes2]['value']
                );
                $tabNotes[$indNotes1][$indNotes2]["lastname"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = _LASTNAME;
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeSmall ;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = true;
            }
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "date_note") {
                $tabNotes[$indNotes1][$indNotes2]["date_note"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = _DATE;
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeMedium;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = true;
            }
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "firstname") {
                $tabNotes[$indNotes1][$indNotes2]["firstname"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = _FIRSTNAME;
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeSmall;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "center";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "center";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = true;
            }
            if ($tabNotes[$indNotes1][$indNotes2][$value] == "note_text") {
                $tabNotes[$indNotes1][$indNotes2]['value'] = '<a href="javascript://"'
                    . ' onclick="ouvreFenetre(\''
                    . $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&module=notes&page=note_details&id='
                    . $indNotes1d . '&amp;resid=' . $_SESSION['doc_id']
                    . '&amp;coll_id=' . $_SESSION['collection_id_choice']
                    . $extendUrl . '\', 1024, 650)">'
                    . $func->cut_string(
                        $request->show_string(
                            $tabNotes[$indNotes1][$indNotes2]['value']
                        ), $cutString
                    ) . '<span class="sstit"> > ' . _READ . '</span>';
                $tabNotes[$indNotes1][$indNotes2]["note_text"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                $tabNotes[$indNotes1][$indNotes2]["label"] = _NOTES;
                $tabNotes[$indNotes1][$indNotes2]["size"] = $sizeFull;
                $tabNotes[$indNotes1][$indNotes2]["label_align"] = "center";
                $tabNotes[$indNotes1][$indNotes2]["align"] = "center";
                $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                $tabNotes[$indNotes1][$indNotes2]["show"] = true;
            }
        }
    }
}

//$request->show_array($tabNotes);
$core->load_html();
//here we building the header
$core->load_header('', true, false);
?>
<body id="<?php functions::xecho($body);?>">
<?php
$title = '';
$listNotes = new list_show();
$listNotes->list_simple(
    $tabNotes, count($tabNotes), $title, 'id', 'id', false, '', $css
);
$core->load_js();
?>
</body>
</html>
