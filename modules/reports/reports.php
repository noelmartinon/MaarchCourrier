<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   reports
*
* @author  dev <dev@maarch.org>
* @ingroup reports
*/
$rep = new core_tools();
$db = new Database();

$rep->test_service('reports', 'reports');

/****************Management of the location bar  ************/
$init = false;
if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == 'true') {
    $init = true;
}
$level = '';
if (isset($_REQUEST['level']) && ($_REQUEST['level'] == 2
    || $_REQUEST['level'] == 3 || $_REQUEST['level'] == 4
    || $_REQUEST['level'] == 1)
) {
    $level = $_REQUEST['level'];
}
$pagePath = $_SESSION['config']['businessappurl']
    .'index.php?page=reports&module=reports';
$pageLabel = _REPORTS;
$pageId = 'reports';
$rep->manage_location_bar($pagePath, $pageLabel, $pageId, $init, $level);
/***********************************************************/

// RETRIEVE NB OF DOCUMENTS
$stmt = $db->query(
    'select count(1) as total from '.$_SESSION['collections'][0]['view']
    .' inner join mlb_coll_ext on '.$_SESSION['collections'][0]['view']
    .'.res_id = mlb_coll_ext.res_id where '.$_SESSION['collections'][0]['view']
    .".status not in ('DEL','BAD')"
);

// RETRIEVE NB OF FOLDERS
$countPiece = $stmt->fetchObject();
if ($rep->is_module_loaded('folder')) {
    $stmt2 = $db->query(
                'SELECT count(1) as total from '
                .$_SESSION['tablename']['fold_folders']." where status not in ('DEL','FOLDDEL')"
        );
    $countFolder = $stmt2->fetchObject();
}

// HEADER
echo '<h1><i class="fa fa-area-chart fa-2x"></i>'._REPORTS.'</h1>';
echo '<div id="inner_content" class="clearfix">';
echo '<div class="block">';

//SUB HEADER
echo '<h2><i class="fa fa-file fa-2x"></i> '._NB_TOTAL_DOC.' <b>'.$countPiece->total.'</b>';
if ($rep->is_module_loaded('folder')) {
    echo '&nbsp;&nbsp; <i class="fa fa-folder fa-2x"></i> '._NB_TOTAL_FOLDER.' <b>'.$countFolder->total.'</b>';
}
echo '</h2>';

//STATS CONTENT
include 'modules'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'user_reports.php';

echo '</div>';
echo '</div>';
