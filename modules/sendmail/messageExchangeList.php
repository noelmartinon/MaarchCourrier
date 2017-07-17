<?php

//Table or view
$select = [];
$select["message_exchange"] = [];
    
//Fields
    array_push($select["message_exchange"], "message_id", "date", "reference", 
        "account_id", "sender_org_identifier", "recipient_org_identifier", "status", "data", "res_id_master");
    
//Where clause
    $where_tab = array();
    //
    $where_tab[] = " res_id_master = ? ";

    //Build where
    $where = implode(' and ', $where_tab);

//Order
    $orderstr = "order by date desc";

//Request
    $tab=$request->PDOselect($select, $where, [$identifier], $orderstr,$_SESSION['config']['databasetype']);
    
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
                    $tab[$i][$j]["size"]        = "11";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'date';
                }
                if($tab[$i][$j][$value]=="reference")
                {
                    $tab[$i][$j]["label"]       = _IDENTIFIER;
                    $tab[$i][$j]["size"]        = "11";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'reference';
                }
                if($tab[$i][$j][$value]=="sender_org_identifier")
                {
                    $tab[$i][$j]["label"]       = "sender_org_identifier";
                    $tab[$i][$j]["size"]        = "11";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'sender_org_identifier';
                }
                if($tab[$i][$j][$value]=="recipient_org_identifier")
                {
                    $tab[$i][$j]["label"]       = "recipient_org_identifier";
                    $tab[$i][$j]["size"]        = "11";
                    $tab[$i][$j]["label_align"] = "left";
                    $tab[$i][$j]["align"]       = "left";
                    $tab[$i][$j]["valign"]      = "bottom";
                    $tab[$i][$j]["show"]        = true;
                    $tab[$i][$j]["order"]       = 'recipient_org_identifier';
                }
                if($tab[$i][$j][$value]=="account_id")
                {
                    $userInfo = \Core\Models\UserModel::getById(['userId' => $tab[$i][$j]["value"]]);
                    $tab[$i][$j]["value"]       = $userInfo['firstname'] . " " . $userInfo['lastname'];
                    $tab[$i][$j]["label"]       = _USER;
                    $tab[$i][$j]["size"]        = "5";
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
                // if($tab[$i][$j][$value]=="firstname")
                // {
                //     $firstname =  $request->show_string($tab[$i][$j]["value"]);
                // }
                // if($tab[$i][$j][$value]=="lastname")
                // {
                //     $tab[$i][$j]["value"] = $request->show_string($tab[$i][$j]["value"]). ' ' .$firstname ;
                //     $tab[$i][$j]["label"]=_USER;
                //     $tab[$i][$j]["size"]=$sizeUser;
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=false;
                //     $tab[$i][$j]["order"]='lastname';
                // }
                
                // if($tab[$i][$j][$value]=="email_destinataire")
                // {
                //     $tab_dest = explode(',', $tab[$i][$j]['value']);
                //     $tab[$i][$j]['value'] = implode(', ', $tab_dest);
                //     $tab[$i][$j]["value"] = $tab[$i][$j]['value'];
                //     $tab[$i][$j]["label"]=_RECIPIENT;
                //     $tab[$i][$j]["size"]=$sizeObject;
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=true;
                //     $tab[$i][$j]["order"]='email_destinataire';
                // }
                // if($tab[$i][$j][$value]=="email_object")
                // {
                //     $tab[$i][$j]["value"] = addslashes($tab[$i][$j]["value"]);
                //     $tab[$i][$j]["label"]=_EMAIL_OBJECT;
                //     $tab[$i][$j]["size"]=$sizeObject;
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=false;
                //     $tab[$i][$j]["order"]='email_object';
                // }
                // if($tab[$i][$j][$value]=="email_object_short")
                // {
                //     $tab[$i][$j]["value"] = $request->cut_string( $request->show_string($tab[$i][$j]["value"]), $cutString);
                //     $tab[$i][$j]["label"]=_EMAIL_OBJECT;
                //     $tab[$i][$j]["size"]=$sizeObject;
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=true;
                //     $tab[$i][$j]["order"]='email_object_short';
                // }
                // if($tab[$i][$j][$value]=="status_label")
                // {
                //     $tab[$i][$j]["value"] =  addslashes($_SESSION['sendmail']['status'][$tab[$i][$j]["value"]]['label']);
                //     $tab[$i][$j]["label"]=_STATUS;
                //     $tab[$i][$j]["size"]="1";
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=false;
                //     $tab[$i][$j]["order"]='status_label';
                // }

                // if($tab[$i][$j][$value]=="mail")
                // {
                //     $tab[$i][$j]["value"] = $request->show_string($tab[$i][$j]["value"]) ;
                //     $tab[$i][$j]["label"]=_SENDER;
                //     $tab[$i][$j]["size"]=$sizeUser;
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=false;
                //     $tab[$i][$j]["order"]='mail';
                // }
                // if($tab[$i][$j][$value]=="sender_email")
                // {

                //     $tab[$i][$j]["value"] = $sendmail_tools->explodeSenderEmail($tab[$i][$j]["value"]);

                //     $tab[$i][$j]["label"]=_SENDER;
                //     $tab[$i][$j]["size"]="20";
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=true;
                //     $tab[$i][$j]["order"]='sender_email';
                // }
                // if($tab[$i][$j][$value]=="id")
                // {
                //     $tab[$i][$j]["value"] = ($sendmail_tools->haveJoinedFiles($tab[$i][$j]["value"]))? 
                //         '<i class="fa fa-paperclip fa-2x" title="'. _JOINED_FILES.'"></i>' : 
                //             '';
                //     $tab[$i][$j]["label"]=false;
                //     $tab[$i][$j]["size"]="1";
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=true;
                //     $tab[$i][$j]["order"]=false;
                // }
                // if($tab[$i][$j][$value]=="email_status")
                // {
                //     $tab[$i][$j]["label"]=_STATUS;
                //     $tab[$i][$j]["size"]="1";
                //     $tab[$i][$j]["label_align"]="left";
                //     $tab[$i][$j]["align"]="left";
                //     $tab[$i][$j]["valign"]="bottom";
                //     $tab[$i][$j]["show"]=false;
                //     $tab[$i][$j]["order"]='email_status';
                // }
            }
        }
    }
    
    //List
    $listKey = 'message_id';                                                              //Cle de la liste
    $paramsTab = array();                                                               //Initialiser le tableau de param�tres
    $paramsTab['bool_sortColumn'] = false;                                               //Affichage Tri
    $paramsTab['pageTitle'] ='<hr><br>Envoi des paquets numérique';                             //Titre de la page
    $paramsTab['bool_bigPageTitle'] = false;                                            //Affichage du titre en grand
    $paramsTab['urlParameters'] = 'identifier='.$identifier
            ."&origin=".$origin.'&display=true'.$parameters;                            //Parametres d'url supplementaires   
    $paramsTab['listHeight'] = '100%';                                                  //Hauteur de la liste
    $paramsTab['bool_showSmallToolbar'] = true;                                         //Mini barre d'outils
    $paramsTab['listCss'] = $css;                                                       //CSS
    
    //Action icons array
    $paramsTab['actionIcons'] = array();      
    $read = array(
    "script"        => "showEmailForm('".$_SESSION['config']['businessappurl']
                                ."index.php?display=true&module=sendmail&page=sendmail_ajax_content"
                                ."&mode=read&id=@@email_id@@&identifier=".$identifier."&origin=".$origin
                                . $parameters."');",
        "icon"      =>  'eye',
        "tooltip"   =>  _READ
    );
    array_push($paramsTab['actionIcons'], $read);  

    array_push($paramsTab['actionIcons'], []);  

    //Output
    $status = 0;
    $contentMessageExchange = $list->showList($tab, $paramsTab, $listKey);
