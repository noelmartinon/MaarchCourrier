<?php

/*
 *  Copyright 2008-2015 Maarch
 *
 *  This file is part of Maarch Framework.
 *
 *  Maarch Framework is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Maarch Framework is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @brief Batch to extract data from last months
 *
 * @file
 * @author <dev@maarch.org>
 * @date $date$
 * @version $Revision$
 * @ingroup life_cycle
 */

/*****************************************************************************
WARNING : THIS BATCH ERASE RESOURCES IN DATABASE AND IN DOCSERVERS
Please note this batch deletes resources in the database
and storage spaces (docservers).
You need to run only if it is set -> Make especially careful to
define the where clause.
FOR THE CASE OF AIP : to be used only if the AIP are single resources.
*****************************************************************************/

/**
 * *****   LIGHT PROBLEMS without an error semaphore
 *  101 : Configuration file missing
 *  102 : Configuration file does not exist
 *  103 : Error on loading config file
 *  104 : SQL Query Error
 *  105 : a parameter is missing
 *  106 : Maarch_CLITools is missing
 *  107 : Stack empty for the request
 *  108 : There are still documents to be processed
 *  109 : An instance of the batch for the required policy and cyle is already
 *        in progress
 *  110 : Problem with collection parameter
 *  111 : Problem with the php include path
 *  112 : AIP not able to be purged
 *  113 : Security problem with where clause
 * ****   HEAVY PROBLEMS with an error semaphore
 *  13  : Docserver not found
 */

date_default_timezone_set('Europe/Paris');
try {
    include('load_extract_data_2.php');
} catch (IncludeFileError $e) {
    echo "Maarch_CLITools required ! \n (pear.maarch.org)\n";
    exit(106);
}
//chdir('../../..');
/********************************   DEFINITION   **********************************************/
$fichierDuJour = date('Y-m-d-Hi');
$chemin = $GLOBALS['exportFolder'].'History_Stats_2_'.$fichierDuJour.'.csv';
$delimiteur = ";";
$extractData = fopen($chemin, 'w+');
$countMail = 0;
$columns = [
    "Num Chrono",  
    "Date d'enregistrement",
    "Date du courrier",
    "Objet du courrier",
    "Type du courrier",
    "Nature",
    "Département",  
    "Thésaurus",
    "Nombre d'expéditeurs",
    "Civilité de l'expéditeur",
    "Prénom de l'expéditeur",
    "Nom de l'expéditeur",
    "Organisme de l'expéditeur",
    "Type de contact",  
    "Nombre de tiers bénéficiaire",
    "Tiers Bénéficiaires",
    "Destinataire",
    "Service destinataire",
    "Services en copie", 
    "Statut du courrier",
    "Date limite de traitement",
    "Priorité",
    "Nombre de réponses",
    "Numéro chrono réponse", 
    "Viseurs",
    "Signataire",
    "Date de signature",
    "Date d'envoi en visa",
    "Date de départ",
    "Nombre de transmissions",
    "Numéro chrono transmission 0",
    "Destinataire transmission 0",
    "Date de retour attendue 0", 
    "Date de retour 0",
    "Numéro chrono transmission 1",
    "Destinataire transmission 1",
    "Date de retour attendue 1", 
    "Date de retour 1",
    "Numéro chrono transmission 2",
    "Destinataire transmission 2",
    "Date de retour attendue 2", 
    "Date de retour 2",
    "Numéro chrono transmission 3",
    "Destinataire transmission 3",
    "Date de retour attendue 3", 
    "Date de retour 3",
    "Numéro chrono transmission 4",
    "Destinataire transmission 4",
    "Date de retour attendue 4", 
    "Date de retour 4",
];


/********************************   PROCESS   **********************************************/

$GLOBALS['logger']->write('Create the file ' . $chemin, 'INFO');

fprintf($extractData, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($extractData, $columns, $delimiteur);
foreach ($GLOBALS['FromDateList'] as $key => $fromdatelist) {
$GLOBALS['logger']->write('Select incoming mails created from last ' . $fromdatelist['FromDate'], 'INFO');

/**** Retrieve Civilities ****/
$civilities = \SrcCore\models\CurlModel::exec([
    'url'           => rtrim($GLOBALS['MaarchUrl'], '/') . '/rest/civilities' ,
    'user'          => $GLOBALS['WsUser'],
    'password'      => $GLOBALS['WsPasswd'],
    'headers'       => ['Content-Type:application/json'],
    'method'        => 'GET'
]);
$civilities = $civilities['civilities'];


$querySelectedFile = "SELECT *  FROM res_view_letterbox r
                    LEFT JOIN status s on s.id = r.status
                    LEFT JOIN priorities p on p.id = r.priority
                    WHERE cast(res_id as character varying) 
                    NOT IN (select distinct(record_id) 
                    as record_id from history 
                    where event_type like 'ACTION%' 
                    AND event_date >= current_timestamp - interval '".$fromdatelist['FromDate']."') 
                    AND destination = '".$fromdatelist['EntityId']."' 
                    AND status <> 'DEL' 
                    AND status <> 'END' 
                    ORDER BY creation_date";


$stmt = Bt_doQuery($GLOBALS['db'], $querySelectedFile);
while($selectedFile = $stmt->fetchObject()) {
    $line = [];
    foreach ($columns as $value) {
        $line[$value] = "";
    }
    $customfields = "SELECT custom_fields FROM res_letterbox WHERE res_id = $selectedFile->res_id;";
    $stmtCustom = Bt_doQuery($GLOBALS['db'], $customfields);
    while ($customfield = $stmtCustom->fetch()){
        $customfields_array = json_decode($customfield['custom_fields']);
    }
    foreach ($customfields_array as $key => $value) {
/*      if ($key==3){
            if($value <> ""){
                $department_name = $value . " - " .\Resource\controllers\DepartmentController::FRENCH_DEPARTMENTS[$value];
            }
            $line["Département"]= $department_name; 
        }*/
        if ($key==1){
            $line["Nature"]= $value; 
        }
        elseif ($key==2){
            $line["Date de départ"]= format_date_db($value); 
        }
        elseif ($key==7){
            $line["Nombre de tiers bénéficiaire"]=count($value);
            $index =count($value);
            foreach ($value as $key => $value) {
                $contact = NomPrenomContact($value->id);
                $line["Tiers Bénéficiaires"].= $contact;
                if ($key < $index -1){
                    $line["Tiers Bénéficiaires"].= ", \n";
                }
            }
        }
    }

    /**** "Thésaurus" ****/
    $stmt2 = Bt_doQuery(
        $GLOBALS['db'], 
        "SELECT tags.label FROM tags, resources_tags WHERE tags.id = resources_tags.tag_id and resources_tags.res_id = ?", array($selectedFile->res_id)
    );
    $iTags = 0;
    while($resTags = $stmt2->fetchObject()){
        if ($iTags >0) {
            $line["Thésaurus"] .= ", ";
        }
        $line["Thésaurus"].= $resTags->label;
        $iTags++;
    }

    /**** "Destinataire" ****/
    $stmt2 = Bt_doQuery(
        $GLOBALS['db'], 
        "SELECT concat(firstname,' ',lastname) as fullname from users where user_id = ?", array($selectedFile->dest_user)
    );
    $destuserFullname=$stmt2->fetchObject();
    $line["Destinataire"]=$destuserFullname->fullname;

    /**** "Services en copies" ****/
    $stmt2 = Bt_doQuery(
        $GLOBALS['db'], 
        "SELECT e.short_label FROM entities e LEFT JOIN listinstance l on l.item_id = e.entity_id WHERE l.item_mode = 'cc' and l.item_type = 'entity_id' and res_id = ?", array($selectedFile->res_id)
    );        
    $array_entities_cc = array();
    while($entities = $stmt2->fetchObject()){
        $array_entities_cc[] = $entities->short_label;
    }
    $line["Services en copie"] = implode(", ", $array_entities_cc);
    $stmt2 = Bt_doQuery(
        $GLOBALS['db'], 
        "SELECT concat(u.firstname,' ',u.lastname) as fullname,l.process_date as process_date FROM listinstance l left join users u ON u.user_id = l.item_id WHERE l.item_mode = 'sign' and l.res_id = ?", array($selectedFile->res_id)
    );    
    $signatory=$stmt2->fetchObject();
    $line["Signataire"] = $signatory->fullname;
    $line["Date de signature"] = format_date_db($signatory->process_date);
       
    /**** "Viseurs" ****/
    $stmt2 = Bt_doQuery(
        $GLOBALS['db'], 
        "SELECT lastname,firstname,process_date from listinstance l RIGHT JOIN users u ON l.item_id = u.user_id WHERE l.res_id = ? and item_mode = 'visa' ORDER BY sequence ASC", array($selectedFile->res_id)
    );   
    $visa_list_arr = array();
    while ($visa_list = $stmt2->fetchObject()) {
        $visa_list_arr[] = $visa_list->lastname . ' ' . $visa_list->firstname . ' (date de visa :'.$visa_list->process_date . ')';
    }
    if(count($visa_list_arr)<>0){
        $line["Viseurs"] = implode(' → ', $visa_list_arr);
    }else{
        $line["Viseurs"] = '';
    }
        
    /**** "Nombre d'expéditeurs" ****/
    $sqlCountExp = "select count(rc.id) as count from resource_contacts rc left join contacts c on c.id = rc.item_id where rc.mode = 'sender' and rc.res_id = $selectedFile->res_id;";
    $stmtCountExp = Bt_doQuery($GLOBALS['db'], $sqlCountExp);
    $countExp = $stmtCountExp->fetchObject();
    $line["Nombre d'expéditeurs"] = $countExp->count;

    /**** "Infos expéditeurs" ****/
    $sqlExp = "select * from resource_contacts rc left join contacts c on c.id = rc.item_id where rc.mode = 'sender' and rc.res_id = $selectedFile->res_id;";
    $stmtExp = Bt_doQuery($GLOBALS['db'], $sqlExp);
    $ivarExpediteur = 0;
    while($exp = $stmtExp->fetchObject()){
        if ($ivarExpediteur > 0){
            $line["Civilité de l'expéditeur"] .= "\n".$civilities[$exp->civility]['label'];
            $line["Prénom de l'expéditeur"] .= "\n".$exp->firstname;
            $line["Nom de l'expéditeur"] .= "\n".$exp->lastname;
            $line["Organisme de l'expéditeur"] .= "\n".$exp->company; 
            $line["Type de contact"] .= "\n".$exp->function;
        }
        else {
            $line["Civilité de l'expéditeur"] .= $civilities[$exp->civility]['label'];
            $line["Prénom de l'expéditeur"] .= $exp->firstname;
            $line["Nom de l'expéditeur"] .= $exp->lastname;
            $line["Organisme de l'expéditeur"] .= $exp->company;
            $line["Type de contact"] .= $exp->function;
        }
        $ivarExpediteur++;

    }
    $sqlDptExp = "select * from resource_contacts rc left join contacts c on c.id = rc.item_id where rc.mode = 'sender' and rc.res_id = $selectedFile->res_id limit 1;";
    $stmtDptExp = Bt_doQuery($GLOBALS['db'], $sqlDptExp);
    while($dptExp = $stmtDptExp->fetchObject()){
        $dptExp = $dptExp->address_postcode;
        $line["Département"] = retrieveDepartment($dptExp);
    }

    /**** "Date d'envoi en visa" ****/
    $stmtVisa = Bt_doQuery(
        $GLOBALS['db'],
        "SELECT event_date FROM history WHERE (event_type = 'ACTION#38' OR event_type = 'ACTION#78') and record_id = ? LIMIT 1",   
        array($selectedFile->res_id)
    );
    $line["Date d'envoi en visa"] = $stmtVisa->fetchObject()->event_date;

    /**** "Nombre de réponses" ****/
    $sqlCountAttachments = "select count(*) as count from res_attachments where attachment_type = 'response_project' and status <> 'DEL' and status <> 'OBS' and res_id_master = $selectedFile->res_id;";
    $stmtCountAttachments = Bt_doQuery($GLOBALS['db'], $sqlCountAttachments);
    $countAttachments = $stmtCountAttachments->fetchObject();
    $line["Nombre de réponses"] = $countAttachments->count;

    /**** "Numéro chrono réponse" ****/
    $sqlAttachments = "select identifier,creation_date from res_attachments where attachment_type = 'response_project' and status <> 'DEL' and status <> 'OBS' and res_id_master = $selectedFile->res_id;";
    $stmtAttachments = Bt_doQuery($GLOBALS['db'], $sqlAttachments);
    $iResponses = 0;
    while($resAttachments = $stmtAttachments->fetchObject()){
        if ($iResponses > 0) {
            $line["Numéro chrono réponse"] .= ", "."\n".$resAttachments->identifier;
        }
        else {
            $line["Numéro chrono réponse"].= $resAttachments->identifier;
        }
        $iResponses++;
    }

    /**** "Nombre de transmissions" ****/
    $sqlCountTransmission = "select count(*) as count from res_attachments where attachment_type = 'transmission' and res_id_master = $selectedFile->res_id;";
    $stmtCountTransmission = Bt_doQuery($GLOBALS['db'], $sqlCountTransmission);
    $countTransmission = $stmtCountTransmission->fetchObject();
    $line["Nombre de transmissions"] = $countTransmission->count;

    $sqlTransmission = "select identifier, validation_date, effective_date, concat(lastname,' ',firstname) as name from res_attachments ra left join contacts c on c.id = ra.recipient_id where attachment_type = 'transmission' and res_id_master = $selectedFile->res_id limit 4;";
    $stmtTransmission = Bt_doQuery($GLOBALS['db'], $sqlTransmission);
    //function to read all line and split in a table.
    $arrayTransmission = splitInColumn($stmtTransmission);

    /**** "Main Request Values" ****/
    $line["Num Chrono"] = $selectedFile->alt_identifier;
    $line["Date d'enregistrement"] = format_date_db($selectedFile->creation_date);//format_date_db
    $line["Date du courrier"] = format_date_db($selectedFile->doc_date);//format_date_db
    $line["Objet du courrier"] = $selectedFile->subject;
    $line["Type du courrier"] = $selectedFile->type_label;
    $line["Date limite de traitement"] = format_date_db($selectedFile->process_limit_date, true);
    $line["Statut du courrier"] = $selectedFile->label_status;
    $line["Priorité"] = $selectedFile->label;
    $line["Nombre de transmissions"] = $stmtTransmission->count;
    $line["Service destinataire"] = $selectedFile->destination;

    
    $stmtCsvColumnsTransmissions = [
        "identifier" => "Numéro chrono transmission ",
        "validation_date" => "Date de retour ",
        "effective_date" => "Date de retour attendue ", 
        "name" => "Destinataire transmission "
    ];
    
    foreach ($arrayTransmission as $key => $value) {
            foreach ($stmtCsvColumnsTransmissions as $stmtValue => $csvColumns) {
                     $line[$csvColumns.$key] = $value->$stmtValue;
            }
    }

    fputcsv($extractData, $line, $delimiteur);
    $countMail++;
}
}
fclose($extractData);


/********************************   END   **********************************************/
$GLOBALS['logger']->write($countMail . ' incoming mails selected', 'INFO');
$GLOBALS['logger']->write('End of process', 'INFO');


//unlink($GLOBALS['lckFile']);
exit($GLOBALS['exitCode']);




/********************************   FUNCTIONS   **********************************************/
/**
* Returns a formated date for SQL queries
*
* @param  $date date Date to format
* @return Formated date or empty string if any error
*/
function format_date_db($date, $withTimeZone=false)
{
    if ($date <> "" ) {
        $var = explode('-', $date);

        if (preg_match('/\s/', $var[2])) {
            $tmp = explode(' ', $var[2]);
            $var[2] = $tmp[0];
            $var[3] = substr($tmp[1],0,8);
        }

        if (preg_match('/^[0-3][0-9]$/', $var[0])) {
            $day = $var[0];
            $month = $var[1];
            $year = $var[2];
            $hours = $var[3];
        } else {
            $year = $var[0];
            $month = $var[1];
            $day = substr($var[2], 0, 2);
            $hours = $var[3];
        }
        if ($year <= "1900") {
            return '';
        } else {
            if ($withTimeZone) {
                return $day . "-" . $month . "-" . $year . "  &nbsp; " . $hours;
            }else{
                return $day . "-" . $month . "-" . $year;
            }
        }
    } else {
        return '';
    }
}


/**
* Returns a formated date for SQL queries
*
* @param stmt statement with multiple line
* @return array for each line
*/
function splitInColumn($stmt){
    $stmtParsed = [];
    $i = 0;
    while($line = $stmt->fetchObject()) {
        $stmtParsed[$i] = $line;
        $i ++;
    }
    return $stmtParsed;
}

function NomPrenomContact($id){
    $sqlContact = "select concat(firstname,' ',lastname) as fullname from contacts where id = $id;";
    $fullnameContact = Bt_doQuery($GLOBALS['db'], $sqlContact);
    $fullnameContact=$fullnameContact->fetch();
    return $fullnameContact['fullname'];
}
function retrieveDepartment($postcode){
    //pour les DOMTOM
    $postcode = trim($postcode);
    $postcodetest = substr($postcode,0,2);
    if ($postcodetest == "97"){
        $postcode = substr($postcode,0,3);
        $department_name = $postcode . " - " .\Resource\controllers\DepartmentController::FRENCH_DEPARTMENTS[$postcode];
    }
    else{
        $postcode = $postcodetest;
        $department_name = $postcode . " - " .\Resource\controllers\DepartmentController::FRENCH_DEPARTMENTS[$postcode];
    }
    return $department_name;
}
