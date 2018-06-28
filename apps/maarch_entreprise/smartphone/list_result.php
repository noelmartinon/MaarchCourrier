<?php
require_once('core/class/class_request.php');
require_once('core/class/class_security.php');
require_once('apps/' . $_SESSION['config']['app_id']
    . '/class/class_list_show.php'
);
require_once('core/class/class_manage_status.php');

require_once 'apps/' . $_SESSION['config']['app_id'] . '/class/class_contacts_v2.php';
$contact    = new contacts_v2();    
$list = new list_show();
$sec = new security();
$statusObj = new manage_status();
$select = array();
$select[$view] = array();
$db = new Database();
if ($view == "view_folders") {
    array_push($select[$view], 'folders_system_id', 'status', 'folder_name', 'creation_date');

}if ($view == "res_view_attachments") {
    array_push($select[$view], 'res_id_master','identifier', 'res_id', 'title', 'status', 'attachment_type', 'creation_date', 'typist', 'dest_user', 'dest_contact_id');

} else {
    array_push($select[$view], 'alt_identifier', 'category_id', 'res_id', 'status', 'subject',"contact_firstname", "contact_lastname", "contact_society", "user_lastname", "user_firstname", 'exp_user_id', 'creation_date');
}
$list = new list_show();
$orderstr = $list->define_order('desc', 'creation_date');
//$requestCount = new request();
//$tabCount = $requestCount->select($select, $whereRequest, $orderstr, $_SESSION['config']['databasetype'],$add_security);
//var_dump(count($tabCount));
//$nombreDeLignes = count($tabCount);
if (isset($whereRequest) && !empty($whereRequest)) {
    $request = new request();
    $tab = $request->PDOselect($select,
        $whereRequest,
        null,
        $orderstr,
        $_SESSION['config']['databasetype'],
        500,
        false,
        '',
        '',
        '',
        $add_security
    );
    $nombreDeLignes = count($tab);
    $nombreDeLignesAffiche = $_SESSION['config']['nblinetoshow'];

    if ($view == "res_view_attachments") echo '<ul id="list_ans" title="Liste des réponses">';
    else echo '<ul id ="list">';


    ?>
    <button id="boutonSuivant" class="whiteButton" style="display:block;"
            onclick="toggle_visibility_suivant(<?php functions::xecho($nombreDeLignes); ?>)">Suivant
    </button>
    <button id="boutonPrecedent" class="whiteButton" style="display:block;" disabled="disabled"
            onclick="toggle_visibility_precedent(<?php functions::xecho($nombreDeLignes); ?>)">Précédent
    </button>
    <input type="hidden" id="start" value=" <?php functions::xecho($nombreDeLignesAffiche); ?> "/>
    <input type="hidden" id="sendNbLineToShow" value=" <?php functions::xecho($nombreDeLignesAffiche); ?> "/>

    <?php
    if (count($tab) > 0) {
        for ($i = 0; $i < count($tab); $i++) {

            if ($view == "view_folders") {
                $line = '<li>';
            } else {
                if(!empty($_REQUEST['baskets'])){
                    $action = \Action\models\ActionModel::getById(['id' => $_SESSION['current_basket']['default_action'], 'select' => ['action_page']]);
                    if ($i < $nombreDeLignesAffiche) {
                        if (!empty($action) && $action['action_page'] == 'visa_mail' && $view != "res_view_attachments"){
                            $line = '<li id=res_' . $i . ' style="display:block;"><a href="view_attachments.php?id=';
                        } else {
                            $line = '<li id=res_' . $i . ' style="display:block;"><a href="details.php?id=';
                        }
                    } else {
                        if (!empty($action) && $action['action_page'] == 'visa_mail' && $view != "res_view_attachments"){
                            $line = '<li id=res_' . $i . ' style="display:block;"><a href="view_attachments.php?id=';
                        } else {
                            $line = '<li id=res_' . $i . ' style="display:none;"><a href="details.php?id=';
                        }
                    }
                } else {
                    $line = '<li id=res_'.$i.' style="display:block;"><a href="details.php?id=';
                }
                
            }
            for ($j = 0; $j < count($tab[$i]); $j++) {
                foreach (array_keys($tab[$i][$j]) as $value) {
                    if ($tab[$i][$j][$value] == "res_id_master") {
                        $res_id_master = $tab[$i][$j]['value'];
                    }
                    if ($tab[$i][$j][$value] == "alt_identifier") {
                        $alt_identifier = $tab[$i][$j]['value'];
                    }
                    if ($tab[$i][$j][$value] == "identifier") {
                        $identifier = $tab[$i][$j]['value'];
                    }
                    if ($tab[$i][$j][$value] == "status") {
                        $status = $tab[$i][$j]['value'];
                    }
                    if ($tab[$i][$j][$value] == 'res_id' || $tab[$i][$j][$value] == 'folders_system_id') {
                        if ($view == "view_folders") {
                            $line .= $tab[$i][$j]['value'] . '. ';
                        } elseif ($view == "res_view_attachments") {
                            $line .= $tab[$i][$j]['value'] . '&res_id_master='.$res_id_master.'"><span style="font-size:12px;">'
                                . $identifier . ' - </span> ';
                        } else {
                            $line .= $tab[$i][$j]['value'] . '"><span style="font-size:12px;">'
                                . $alt_identifier . ' ('.$tab[$i][$j]['value'].') - </span> ';
                        }
                    }
                    
                    if ($tab[$i][$j][$value] == "subject" || $tab[$i][$j][$value] == "folder_name") {
                        $line .= '<span style="font-weight:bold;">' . functions::cut_string(functions::show_string($tab[$i][$j]['value']), 80) . '</span><br/><br/>';
                    }
                    if ($tab[$i][$j][$value] == "attachment_type") {
                        $line .= '<span style="font-weight:bold;">' . $_SESSION['attachment_types'][$tab[$i][$j]['value']] ;
                        if ($tab[$i][$j]['value'] == 'signed_response')  $line .=' <i class="fa fa-certificate fa-1x"></i>';
                        $line .= '</span><br/><br/>';
                    }
                    
                    if($tab[$i][$j][$value]=="category_id")
                    {
                        $category_id = $tab[$i][$j]["value"];
                    }
                    if($tab[$i][$j][$value]=="contact_firstname")
                    {
                        $contact_firstname = $tab[$i][$j]["value"];
                    }
                    if($tab[$i][$j][$value]=="contact_lastname")
                    {
                        $contact_lastname = $tab[$i][$j]["value"];
                    }
                    if($tab[$i][$j][$value]=="contact_society")
                    {
                        $contact_society = $tab[$i][$j]["value"];
                    }
                    if($tab[$i][$j][$value]=="user_firstname")
                    {
                        $user_firstname = $tab[$i][$j]["value"];
                    }
                    if($tab[$i][$j][$value]=="user_lastname")
                    {
                        $user_lastname = $tab[$i][$j]["value"];
                    }
                    if ($tab[$i][$j][$value]=="exp_user_id") {
                        $contact_to_show = $contact->get_contact_information_from_view($category_id, $contact_lastname, $contact_firstname, $contact_society, $user_lastname, $user_firstname);
                        $line .= '<span style="font-style:italic;font-size:12px;">'.functions::cut_string(
                            functions::show_string($contact_to_show), 80
                            ).'</span><br/>' ;
                    }
                    
                    if ($tab[$i][$j][$value]=="creation_date") {
                        $line .= '<span style="font-style:italic;font-size:12px;"><i class="fa fa-calendar fa-2x"></i> ' . functions::format_date_db(
                            $tab[$i][$j]['value'], false
                        );
                    }

                    if ($tab[$i][$j][$value] == "typist") {
                            $stmt = $db->query("SELECT firstname, lastname FROM users WHERE user_id = ? ", [$tab[$i][$j]['value']]);
                            $res = $stmt->fetchObject();
                            $line .= ' par ' . functions::protect_string_db($res->firstname) .' '. functions::protect_string_db($res->lastname).'</span><br/>';
                    }

                    if ($tab[$i][$j][$value]=="dest_contact_id") {
                        if ($tab[$i][$j]['value'] <> 0 && $tab[$i][$j]['value'] <> '' ) {
                            $stmt = $db->query("SELECT firstname, lastname, society FROM contacts_v2 WHERE contact_id = ? ",array($tab[$i][$j]['value']));
                            $res = $stmt->fetchObject();
                             $line .= '<span style="font-style:italic;font-size:12px;">'."Pour ".functions::protect_string_db($res->firstname) .' '. functions::protect_string_db($res->lastname) . ' '. functions::protect_string_db($res->society).'</span>';
                        }
                    }
                    if ($tab[$i][$j][$value]=="dest_user") {
                        if ($tab[$i][$j]['value'] <> 0 && $tab[$i][$j]['value'] <> '' ) {
                            $stmt = $db->query("SELECT firstname, lastname FROM users WHERE user_id = ? ", [$tab[$i][$j]['value']]);
                            $res = $stmt->fetchObject();
                            $line .= '<span style="font-style:italic;font-size:12px;">'."Pour ".functions::protect_string_db($res->firstname) .' '. functions::protect_string_db($res->lastname).'</span>';
                        }
                    }
                }
            }

            if ($view == "view_folders") {
                echo $line . '</li>';
            } else {
                echo $line . '</a></li>';
            }
        }
    } else {
        echo '<p>' . _NO_RESULTS . '</p>';
    }
    echo '</ul>';


    //echo '<a href="#" onclick="toggle_visibility("foo","foo2");">style="text-decoration:none"><input type="button" class="whiteButton" value="Suivant"></a>';
    //echo '<a type="submit" href="search_result.php?" style="text-decoration:none"><input type="button" class="whiteButton" value="Suivant"></a>';

}

