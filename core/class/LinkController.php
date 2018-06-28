<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief  LinkController
*
* @author  dev <dev@maarch.org>
* @ingroup core
*/

//Loads the require class
require_once 'core/class/class_history.php';
include 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'definition_mail_categories.php';

class LinkController
{
    private $previousId = ' ';
    private $level = 0;

    public function formatMap($arrayToFormat, $sens)
    {
        $db = new Database();
        $core = new core_tools();
        $core->test_user();

        $return = '';

        ++$this->level;

        foreach ($arrayToFormat as $key => $value) {
            //GET RES INFOS
            $infos = get_general_data($_SESSION['current_basket']['coll_id'], $key, 'full');
            $stmt = $db->query('SELECT dest_user, status FROM res_letterbox WHERE res_id = ?', array($key));
            $otherInfos = $stmt->fetchObject();
            $chronoNumber = $this->getAltIdentifier($key);

            if (!empty($infos['exp_contact_id']['show_value'])) {
                $contact = $infos['exp_contact_id']['show_value'];
            } elseif (!empty($infos['dest_contact_id']['show_value'])) {
                $contact = $infos['dest_contact_id']['show_value'];
            } elseif (!empty($infos['exp_user_id']['show_value'])) {
                $contact = $infos['exp_user_id']['show_value'];
            } elseif (!empty($infos['dest_user_id']['show_value'])) {
                $contact = $infos['dest_user_id']['show_value'];
            } else {
                $contact = _MULTI_CONTACT;
            }

            $infos['subject'] = preg_replace("/\r\n|\r|\n/", '<br/>', $infos['subject']);

            $return .= '<div id="ged_'.$key.$sens.'" class="linkDiv">';
            $return .= '<table style="width:100%;text-align:left;font-size:15px;cursor:pointer;" title="'._ACCESS_TO_DETAILS.'">';
            $return .= '<tr>';
            $status = $this->getStatus($otherInfos->status);
            $img_class = substr($status['img_filename'], 0, 2);
            $return .= '<td style="width:14%;text-align:center;" title="'.$status['label_status'].'" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '<i class="'.$img_class.' '.$status['img_filename'].' '.$img_class.'-2x" ></i> ';
            $return .= '</td>';
            $return .= '<td colspan="2" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '<b>'.$infos['subject']['show_value'].'</b>';
            $return .= '</td>';
            $return .= '<td colspan="2" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '</td>';
            $return .= '<td colspan="3" style="font-size:12px;" align="right" title="'._CONTACT.'" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '<i class="fa fa-user fa-2x" style="font-size:10px;"></i> ';
            $return .= $contact;
            $return .= '</td>';
            $return .= '</tr>';
            $return .= '<tr>';
            $return .= '<td style="width:14%;text-align:center;" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= (_ID_TO_DISPLAY == 'res_id' ? $key : $chronoNumber);
            $return .= '</td>';
            $return .= '<td style="font-size:12px;width:16%;" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= $infos['category_id']['show_value'];
            $return .= '</td>';
            $return .= '<td style="font-size:12px;width:14%" title="'._DOC_DATE.'" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '<i class="fa fa-calendar-o fa-2x" style="font-size:10px;"></i> ';
            $date = explode('-', substr($infos['doc_date']['show_value'], 0, 10));
            $return .= $date[2].' '.$date[1].' '.$date[0];
            $return .= '</td>';
            $return .= '<td style="font-size:12px;width:14%" title="'._DEST_USER.'" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '<i class="fa fa-share-alt fa-2x" style="font-size:10px;"></i> ';
            $return .= $otherInfos->dest_user;
            $return .= '</td>';
            $return .= '<td style="font-size:12px;width:24%" title="'._DEPARTMENT_DEST.'" onclick="window.top.location.href=\'index.php?page=details&dir=indexing_searching&id='.$key.'\'">';
            $return .= '<i class="fa fa-sitemap fa-2x" style="font-size:10px;"></i> ';
            $return .= $infos['destination']['show_value'];
            $return .= '</td>';
            if ($core->is_module_loaded('visa')) {
                require_once 'modules'.DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR
                            .'class'.DIRECTORY_SEPARATOR
                            .'class_modules_tools.php';
                $return .= '<td style="font-size:12px;width:16%" title="'._VISA_USERS.'">';

                $visa = new visa();

                $users_visa_list = $visa->getUsersCurrentVis($key);
                if (!empty($users_visa_list)) {
                    $users_visa_list = implode(', ', $users_visa_list);
                    $return .= '<i class="fa fa-list-ol fa-2x" style="font-size:10px;"></i> ';
                    $return .= $users_visa_list;
                }
                $return .= '</td>';
            }

            if ($core->test_service('add_links', 'apps', false) && $this->level <= 1) {
                if ($sens == 'asc') {
                    $delParent = $key;
                    $delChild = $_SESSION['doc_id'];
                } else {
                    $delParent = $_SESSION['doc_id'];
                    $delChild = $key;
                }
                $return .= '<td align="right">';
                if ($core->is_module_loaded('thumbnails') === true) {
                    require_once 'modules'.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR
                                .'class'.DIRECTORY_SEPARATOR
                                .'class_modules_tools.php';

                    $tnl = new thumbnails();
                    $path = $tnl->getPathTnl($key, 'letterbox_coll');
                    if (is_file($path)) {
                        $return .= '<div align="center" class="iconDoc"><a href="index.php?display=true&dir=indexing_searching&page=view_resource_controler&id='.$key.'" target="_blank" title="'._VIEW_DOC.'"><i class="fa fa-download fa-2x" title="'._VIEW_DOC.'"></i><span><img src="index.php?page=doc_thumb&module=thumbnails&res_id='.$key.'&coll_id=letterbox_coll&display=true"></span></a></div>';
                    } else {
                        $return .= '<div align="center" class="iconDoc"><a href="index.php?display=true&dir=indexing_searching&page=view_resource_controler&id='.$key.'" target="_blank" title="'._VIEW_DOC.'"><i class="fa fa-download fa-2x" title="'._VIEW_DOC.'"></i><span id="no_doc"></span></a></div>';
                    }
                }
                $return .= '</td>';
                $return .= '<td align="right">';
                $return .= '<span onclick="';
                $return .= 'if(confirm(\'Voulez-vous supprimer la liaison ?\')){';
                $return .= 'addLinks(';
                $return .= '\''.$_SESSION['config']['businessappurl'].'index.php?page=add_links&display=true\', ';
                $return .= '\''.$delChild.'\' ,';
                $return .= '\''.$delParent.'\' ,';
                $return .= '\'del\',';

                if ($_SESSION['current_basket']['coll_id'] == 'letterbox_coll') {
                    $return .= '\'res_view_letterbox\'';
                } elseif ($_SESSION['current_basket']['coll_id'] == 'business_coll') {
                    $return .= '\'res_view_business\'';
                } else {
                    $return .= '\'\'';
                }
                $return .= ');}';
                $return .= '">';
                $return .= '<i class="fa fa-unlink fa-2x" title="'._DEL_LINK.'" style="cursor:pointer;"></i>';
                $return .= '</span>';
                $return .= '</td>';
            }
            $return .= '</tr>';
            $return .= '</table>';
            if (is_array($value)) {
                $return .= $this->formatMap($value, $sens);
            }
            $return .= '</div>';
        }

        --$this->level;

        return $return;
    }

    public function getMap($parentId, $collection, $sens)
    {
        if (!empty($parentId) && !empty($collection)) {
            if ($sens == 'asc') {
                $links = $this->getLinksAsc($parentId, $collection);
            } else {
                $links = $this->getLinksDesc($parentId, $collection);
            }
            $linksArray = explode('||', $links);

            for ($i = 0; $i < count($linksArray); ++$i) {
                if ($linksArray[$i] != '') {
                    if (!preg_match('/'.' '.$linksArray[$i].' '.'/', $this->previousId)) {
                        $this->previousId .= $parentId.' ';
                        $return[$linksArray[$i]] = $this->getMap($linksArray[$i], $collection, $sens);
                    }
                } else {
                    $return = 'last';
                }
            }
        }

        return $return;
    }

    private function getLinksDesc($parentId, $collection)
    {
        $db = new Database();

        $query = 'SELECT res_child FROM res_linked WHERE coll_id=? AND res_parent=?';
        $stmt = $db->query($query, array($collection, $parentId));
        if ($stmt) {
            $i = 0;
            $links = '';
            while ($row = $stmt->fetchObject()) {
                $links .= $row->res_child.'||';
                ++$i;
            }
            if ($i > 0) {
                $return = substr($links, 0, -2);
            }
        } else {
            $return = 'Problème lors de la requête : '.$query;
        }

        return $return;
    }

    private function getLinksAsc($parentId, $collection)
    {
        $db = new Database();

        $query = 'SELECT res_parent FROM res_linked WHERE coll_id=? AND res_child=?';
        $stmt = $db->query($query, array($collection, $parentId));
        if ($stmt) {
            $i = 0;
            $links = '';
            while ($row = $stmt->fetchObject()) {
                $links .= $row->res_parent.'||';
                ++$i;
            }
            if ($i > 0) {
                $return = substr($links, 0, -2);
            }
        } else {
            $return = 'Problème lors de la requête : '.$query;
        }

        return $return;
    }

    public function getStatus($status)
    {
        $db = new Database();

        $query = 'SELECT label_status, img_filename FROM status WHERE id = ?';
        $stmt = $db->query($query, array($status));
        if ($stmt) {
            $i = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $return = $row;
                ++$i;
            }
        }

        return $return;
    }

    public function nbDirectLink($id, $collection, $sens)
    {
        $db = new Database();

        $i = 0;
        if ($sens == 'desc' || $sens == 'all') {
            $query = 'SELECT res_child FROM res_linked WHERE coll_id=? AND res_parent=?';
            $stmt = $db->query($query, array($collection, $id));
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    ++$i;
                }
            }
        }
        if ($sens == 'asc' || $sens == 'all') {
            $query = 'SELECT res_parent FROM res_linked WHERE coll_id=? AND res_child=?';
            $stmt = $db->query($query, array($collection, $id));
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    ++$i;
                }
            }
        }

        return $i;
    }

    public function getAltIdentifier($resId)
    {
        $db = new Database();

        $altIdentifierRequest = 'SELECT alt_identifier FROM mlb_coll_ext where res_id = ?';
        $stmt = $db->query($altIdentifierRequest, array($resId));
        while ($altIdentifierResult = $stmt->fetchObject()) {
            $altIdentifier = $altIdentifierResult->alt_identifier;
        }

        return $altIdentifier;
    }

    public function getAltIdentifierConcatened($array)
    {
        $_SESSION['chronoNumber'] = '';
        for ($j = 0; $j < count($array); ++$j) {
            $_SESSION['chronoNumber'] .= $this->getAltIdentifier($array[$j]).', ';
        }

        return substr($_SESSION['chronoNumber'], 0, -2);
    }
}
