<?php

//Table or view
$select = [];
$select["message_exchange"] = [];
    
//Fields
    array_push($select["message_exchange"], "message_id", "date", "reference", "sender_org_name", "account_id",
        "recipient_org_identifier", "recipient_org_name", "reception_date", "operation_date", "status", "data", "res_id_master");
    
//Where clause
    $where_tab = array();
    //
    $where_tab[] = " res_id_master = ? ";
    $where_tab[] = " type in ('ArchiveTransfer', 'ArchiveTransferReplySent') ";

    //Build where
    $where = implode(' and ', $where_tab);

//Order
    $orderstr = "order by date desc";

//Request
    $tab=$request->PDOselect($select, $where, [$identifier], $orderstr,$_SESSION['config']['databasetype']);

if(!empty($tab)){    
//Result Array
    for ($i=0;$i<count($tab);$i++)
    {
        for ($j=0;$j<count($tab[$i]);$j++)
        {
            foreach(array_keys($tab[$i][$j]) as $value)
            {
                if($tab[$i][$j][$value]=="message_id")
                {
                    $tab[$i][$j]["message_id"]  = $tab[$i][$j]['value'];
                    $tab[$i][$j]["label"]       = 'ID';
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = false;
                    $tab[$i][$j]["order"]       = 'message_id';
                }
                if($tab[$i][$j][$value]=="date")
                {
                    $tab[$i][$j]["label"]       = _CREATION_DATE;
                    $tab[$i][$j]["size"]        = "9";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'date';
                }
                if($tab[$i][$j][$value]=="reference")
                {
                    $tab[$i][$j]["label"]       = _IDENTIFIER;
                    $tab[$i][$j]["size"]        = "9";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'reference';
                }
                if($tab[$i][$j][$value]=="operation_date")
                {
                    $tab[$i][$j]["value"]       = $request->dateformat($tab[$i][$j]["value"]);
                    $tab[$i][$j]["label"]       = _OPERATION_DATE;
                    $tab[$i][$j]["size"]        = "9";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'operation_date';
                }
                if($tab[$i][$j][$value]=="reception_date")
                {
                    $tab[$i][$j]["value"]       = $request->dateformat($tab[$i][$j]["value"]);
                    $tab[$i][$j]["label"]       = _RECEPTION_DATE;
                    $tab[$i][$j]["size"]        = "9";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'reception_date';
                }
                if($tab[$i][$j][$value]=="recipient_org_name")
                {
                    $tab[$i][$j]["value"]       = $tab[$i][$j]["value"] . " (" . $recipient_org_identifier . ")";
                    $tab[$i][$j]["label"]       = _RECIPIENT;
                    $tab[$i][$j]["size"]        = "20";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'recipient_org_name';
                }
                if($tab[$i][$j][$value]=="sender_org_name")
                {
                    $sender_org_name = $tab[$i][$j]["value"];
                    $tab[$i][$j]["show"]        = false;
                }
                if($tab[$i][$j][$value]=="recipient_org_identifier")
                {
                    $recipient_org_identifier = $tab[$i][$j]["value"];
                    $tab[$i][$j]["show"]      = false;
                }
                if($tab[$i][$j][$value]=="account_id")
                {
                    $userInfo = \Core\Models\UserModel::getById(['userId' => $tab[$i][$j]["value"]]);
                    $tab[$i][$j]["value"]       = $userInfo['firstname'] . " " . $userInfo['lastname'] . " (".$sender_org_name.")";
                    $tab[$i][$j]["label"]       = _SENDER;
                    $tab[$i][$j]["size"]        = "20";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'account_id';
                }
                if($tab[$i][$j][$value]=="status")
                {
                    $tab[$i][$j]["value"] = '<img src="'
                        .$_SESSION['config']['businessappurl'].'static.php?module=sendmail&filename='
                        .$_SESSION['sendmail']['status'][$tab[$i][$j]["value"]]['img'].'" title="'
                        .$_SESSION['sendmail']['status'][$tab[$i][$j]["value"]]['label'].'" width="20" height="20" />';
                    $tab[$i][$j]["label"]       = _STATUS;
                    $tab[$i][$j]["size"]        = "1";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'status';
                }
            }
        }
    }
    
    //List
    $listKey                        = 'message_id';                                       //Cle de la liste
    $paramsTab                      = array();                                            //Initialiser le tableau de param�tres
    $paramsTab['bool_sortColumn']   = false;                                              //Affichage Tri
    $paramsTab['pageTitle']         ='<br><br>'._NUMERIC_PACKAGE_SENT;                    //Titre de la page
    $paramsTab['bool_bigPageTitle'] = false;                                              //Affichage du titre en grand
    $paramsTab['urlParameters']     = 'identifier='.$identifier."&origin=".$origin.'&display=true'.$parameters;                            //Parametres d'url supplementaires   
    $paramsTab['listHeight']        = '100%';                                             //Hauteur de la liste
    $paramsTab['listCss']           = $css;                                               //CSS
    
    //Action icons array
    $paramsTab['actionIcons'] = array();      
    $read = array(
    "script"        => "showEmailForm('".$_SESSION['config']['businessappurl']
                                ."index.php?display=true&module=sendmail&page=sendmail_ajax_content"
                                ."&mode=read&id=@@message_id@@&identifier=".$identifier."&origin=".$origin.'&formContent=messageExchange'
                                . $parameters."');",
        "icon"      =>  'eye',
        "tooltip"   =>  _READ
    );
    array_push($paramsTab['actionIcons'], $read);  

    array_push($paramsTab['actionIcons'], []);  

    //Output
    $status = 0;
    $contentMessageExchange = $list->showList($tab, $paramsTab, $listKey);
}
