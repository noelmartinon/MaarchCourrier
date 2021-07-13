<?php

//Table or view
$select = [];
$select["acknowledgement_receipts"] = [];
    
//Fields
array_push(
    $select["acknowledgement_receipts"],
    "id",
    "type",
    "format",
    "user_id",
    "contact_address_id",
    "creation_date",
    "send_date",
    "docserver_id"
);
    
//Where clause
$where_tab = array();
//
$where_tab[] = " res_id = ? ";

//Build where
$where = implode(' and ', $where_tab);


//Order
$list->setOrderField('send_date');
$orderstr = 'order by send_date desc NULLS LAST';
//Request
$tab=$request->PDOselect($select, $where, [$identifier], $orderstr, $_SESSION['config']['databasetype'], 10000);

if (!empty($tab)) {
    //Result Array
    for ($i=0; $i<count($tab); $i++) {
        for ($j=0; $j<count($tab[$i]); $j++) {
            foreach (array_keys($tab[$i][$j]) as $value) {
                if ($tab[$i][$j][$value]=="id") {
                    $tab[$i][$j]["label"]       = 'id';
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = false;
                    $tab[$i][$j]["order"]       = 'id';
                }
                if ($tab[$i][$j][$value]=="type") {
                    $tab[$i][$j]["label"]       = _TYPE;
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'type';
                }
                if ($tab[$i][$j][$value]=="format") {
                    $tab[$i][$j]["label"]       = _FORMAT;
                    if ($tab[$i][$j]["value"] == 'html') {
                        $tab[$i][$j]["value"]       = _ELECTRONIC;
                    } else {
                        $tab[$i][$j]["value"]       = _ISPAPER;
                    }
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'format';
                }
                if ($tab[$i][$j][$value]=="user_id") {
                    $tab[$i][$j]["label"]       = _CREATE_BY;
                    $user = _UNDEFINED;
                    if (!empty($tab[$i][$j]["value"])) {
                        $userInfo = \User\models\UserModel::getById(['id' => $tab[$i][$j]["value"]]);
                        $user = $userInfo['firstname'] . " " . $userInfo['lastname'];
                    }
                    $tab[$i][$j]["value"]       = $user;
                    $tab[$i][$j]["size"]        = "3";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'user_id';
                }
                if ($tab[$i][$j][$value]=="contact_address_id") {
                    $tab[$i][$j]["label"]       = _CONTACT;
                    $contactInfo = _UNDEFINED;
                    if (!empty($tab[$i][$j]["value"])) {
                        $contactInfo = \Contact\models\ContactModel::getContactFullLabel(['addressId' => $tab[$i][$j]["value"]]);
                    }
                    $tab[$i][$j]["value"]       = $contactInfo;
                    $tab[$i][$j]["size"]        = "4";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = false;
                }
                if ($tab[$i][$j][$value]=="creation_date") {
                    $tab[$i][$j]["label"]       = _CREATION_DATE;
                    $tab[$i][$j]["value"]       = $request->dateformat($tab[$i][$j]["value"]);
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'creation_date';
                }
                if ($tab[$i][$j][$value]=="send_date") {
                    $tab[$i][$j]["label"]       = _SENT_DATE;
                    $tab[$i][$j]["value"]       = $request->dateformat($tab[$i][$j]["value"]);
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'send_date';
                }
            }
        }
    }
    
    //List
    $listKey                        = 'id';                                       //Cle de la liste
    $paramsTab                      = array();                                            //Initialiser le tableau de parametres
    $paramsTab['bool_sortColumn']   = false;                                              //Affichage Tri
    $paramsTab['pageTitle']         = '<br><br>'._ACKNOWLEDGEMENT_RECEIPTS;                   //Titre de la page
    $paramsTab['bool_bigPageTitle'] = false;                                              //Affichage du titre en grand
    $paramsTab['bool_showToolbar'] = false;                                              //Affichage de la toolbar
    $paramsTab['bool_showBottomToolbar'] = false;
    $paramsTab['urlParameters']     = 'identifier='.$identifier.'&origin=acknowledgement&display=true'.$parameters;            //Parametres d'url supplementaires
    $paramsTab['listHeight']        = '100%';                                             //Hauteur de la liste
    $paramsTab['listCss']           = $css;                                               //CSS
    $paramsTab['linesToShow']       = $_SESSION['save_list']['full_count'];
    
    
    //Action icons array
    $paramsTab['actionIcons'] = array();

    // TO DO : LINK ROUTE
    $download = array(
        "script"    => "window.open('../../rest/res/".$identifier."/acknowledgementReceipt/@@id@@','acknowledgementReceipt');",
        "icon"      =>  'eye',
        "tooltip"   =>  _VIEW_DOC,
        "disabledRules" => "empty(@@id@@) || empty(@@docserver_id@@)"
    );
    array_push($paramsTab['actionIcons'], $download);
    
    $paramsTab['visualizeDocumentLink'] = $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=view_attachment&res_id_master='.$_SESSION['doc_id'].'&viewpdf=true';


    //Output
    $status = 0;
    $contentAcknowledgementReceipts = $list->showList($tab, $paramsTab, $listKey);
}
