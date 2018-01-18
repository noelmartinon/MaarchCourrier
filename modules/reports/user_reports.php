<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   user_reports
*
* @author  dev <dev@maarch.org>
* @ingroup reports
*/
require_once 'modules'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_admin_reports.php';
require_once 'modules'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';

$core_tools = new core_tools();
$func = new functions();
$admin_reports = new admin_reports();
$report = new reports();

$core_tools->test_user();
$userReports = array();
$user_id = $_SESSION['user']['UserId'];

$enabled_reports = $report->get_reports_from_xml();

$userReports = $admin_reports->load_user_reports($user_id, '');
$authorized_reports_sort_by_parent = array();

//Sort reports by parents
$j = 0;
$last_val = '';
foreach (array_keys($userReports) as $key) {
    if ($enabled_reports[$key]['module'] != $last_val) {
        $j = 0;
    }
    $authorized_reports_sort_by_parent[$enabled_reports[$key]['module']][$j] = $enabled_reports[$key];
    ++$j;
    $last_val = $enabled_reports[$key]['module'];
}

if (count($userReports) > 0) {
    echo '<span class="form_title">'._CLICK_LINE_BELOW_TO_SEE_REPORT.'</span><br/><br/>';
    echo '<div class="block">';

    $_SESSION['cpt'] = 0;

    //GENERATED STAT FILES (LIFE_CYCLE MODULE)
    if ($rep->is_module_loaded('life_cycle')) {
        echo "<h5 onmouseover='' style='cursor: pointer;' onclick='showStatFiles();'>";
        echo "<i class='fa fa-plus fa-2x'></i>&nbsp;<b>Fichiers de statistiques générés</b>";
        echo '</h5>';

        echo '<br/>';

        //LIST STATS IN CURRENT TAB
        echo "<div class='block_light' id='div_generatedStatFiles' style='display:none;'>";
        echo '</div>';
    }

    foreach (array_keys($authorized_reports_sort_by_parent) as $value) {
        //TAB STATS
        echo "<h5 onmouseover='' style='cursor: pointer;' onclick='\$j(\"#div_{$authorized_reports_sort_by_parent[$value][0]['module']}\").slideToggle(\"slow\");'>";
        echo "<i class='fa fa-plus fa-2x'></i>&nbsp;<b>{$authorized_reports_sort_by_parent[$value][0]['module_label']}</b>";
        echo '</h5>';

        echo '<br/>';

        //LIST STATS IN CURRENT TAB
        echo "<div class='block_light' id='div_{$authorized_reports_sort_by_parent[$value][0]['module']}' style='display:none;'>";
        echo '<div>';
        echo '<ul class="reports_list">';
        echo '<table>';
        for ($i = 0; $i < count($authorized_reports_sort_by_parent[$value]); ++$i) {
            echo '<tr>';
            echo '<td nowrap align="left">';
            echo "<li><a class='printlink' href='#' onclick='fill_report_result(\"{$authorized_reports_sort_by_parent[$value][$i]['url']}\")'>{$authorized_reports_sort_by_parent[$value][$i]['label']}</a></li>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';

        ++$_SESSION['cpt'];
    }
    echo '</div>';
    echo '<div class="block_end">&nbsp;</div>';
    echo '<br/>';

    //STAT GRAPH CONTENT
    echo '<div id="result_report"></div>';
} else {
    echo '<span class="form_title">'._NO_REPORTS_FOR_USER.'</span><br/><br/>';
}
