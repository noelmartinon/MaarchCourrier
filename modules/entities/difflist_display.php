<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   difflist_display
*
* @author  dev <dev@maarch.org>
* @ingroup entities
*/
$contentDiffList = '';

$contentDiffList .= '<div style="max-height:490px;overflow:auto;">';

$empty = 0;
$nb_roles = 0;
foreach ($roles as $role_id => $role_label) {
    ++$nb_role;
    if ($category == 'outgoing' && $role_label == 'Destinataire') {
        $role_label = _SHIPPER;
    }
    if (($specific_role != $role_id && $specific_role.'_copy' != $role_id && $specific_role.'_info' != $role_id) && isset($specific_role) && $specific_role != '') {
        continue;
    }
    if (count($difflist[$role_id]['users']) > 0
        || count($difflist[$role_id]['entities']) > 0
    ) {
        ++$empty;
        $contentDiffList .= '<h3 class="sstit" style="font-size: 1.2em;">'.$role_label.'</h3>';

        if (count($difflist[$role_id]['users']) > 0) {
            $contentDiffList .= '<table id="diffListUser_'.$role_id.'" cellpadding="0" cellspacing="0" border="0" class="listingsmall liste_diff spec" style="width:100%;margin:0;">';

            $color = ' class="col"';

            for ($i = 0, $l = count($difflist[$role_id]['users']); $i < $l; ++$i) {
                $user = $difflist[$role_id]['users'][$i];

                if ($color == ' class="col"') {
                    $color = ' ';
                } else {
                    $color = ' class="col"';
                }

                if (preg_match("/\[DEL\]/", $difflist[$role_id]['users'][$i]['process_comment'])) {
                    $styleMoved = " style='text-decoration: line-through;opacity:0.5;' ";
                    $descMoved = $difflist[$role_id]['users'][$i]['process_comment'];
                } else {
                    $styleMoved = '';
                    $descMoved = '';
                }

                $contentDiffList .= '<tr id="'.$user['user_id'].'_'.$role_id.'" '.$color.$styleMoved.'  title="'.$descMoved.'">';
                $contentDiffList .= '<td style="width:15%;text-align:center;">';
                $contentDiffList .= '<i class="fa fa-user fa-2x" title="'._USER.'"></i>';
                $contentDiffList .= '</td>';
                $contentDiffList .= '<td style="width:37%;">'.$user['lastname'].' '.$user['firstname'].'</td>';
                $contentDiffList .= '<td style="width:43%;">'.$user['entity_label'].'</td>';
                $contentDiffList .= '<td class="movedest" style="width:5%;">';

                if (!empty($difflist['dest']['users'][0]) && $role_id != 'dest' && $origin != null && !$core->test_service('add_copy_in_indexing_validation', 'entities', false)) {
                    $contentDiffList .= '<i class="fa fa-arrow-up" style="cursor:pointer;" title="'._DEST.'" onclick="moveToDest(\''.$user['user_id'].'\',\''.$role_id.'\',\''.$origin.'\');"></i>';
                }
                $contentDiffList .= '</td>';

                if ($showStatus == true) {
                    if (!empty($difflist[$role_id]['users'][$i]['process_date'])) {
                        $contentDiffList .= '<td style="width:5%;"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></td>';
                    } else {
                        $contentDiffList .= '<td style="width:5%;"><i class="fa fa-hourglass-half" aria-hidden="true"></i></td>';
                    }
                }
                $contentDiffList .= '</tr>';
            }
            $contentDiffList .= '</table>';
        }
        if (count($difflist[$role_id]['entities']) > 0) {
            $contentDiffList .= '<table cellpadding="0" cellspacing="0" border="0" class="listingsmall liste_diff spec" style="width:100%;margin:0;">';
            $color = ' class="col"';

            for ($i = 0, $l = count($difflist[$role_id]['entities']); $i < $l; ++$i) {
                $entity = $difflist[$role_id]['entities'][$i];
                if ($color == ' class="col"') {
                    $color = '';
                } else {
                    $color = ' class="col"';
                }

                $contentDiffList .= '<tr '.$color.'>';
                $contentDiffList .= '<td style="width:15%;text-align:center;">';
                $contentDiffList .= '<i class="fa fa-sitemap fa-2x" title="'._ENTITY.' '.$role_label.'"></i>';
                $contentDiffList .= '</td>';
                $contentDiffList .= '<td style="width:37%;">'.$entity['entity_id'].'</td>';
                $contentDiffList .= '<td style="width:38%;">'.$entity['entity_label'].'</td>';
                $contentDiffList .= '<td style="width:10%;">';
                $contentDiffList .= '</td>';
                $contentDiffList .= '</tr>';
            }
            $contentDiffList .= '</table>';
        }
        $contentDiffList .= '<br/>';
    }
}

if ($empty == $nb_roles) {
    $contentDiffList .= '<div style="font-style:italic;text-align:center;color:#ea0000;margin:10px;">'._DIFF_LIST.' '._IS_EMPTY.'</div>';
}
$contentDiffList .= '</div>';

echo $contentDiffList;
