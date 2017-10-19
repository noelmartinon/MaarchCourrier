<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief    List of contact informations for autocompletion
*
* @file     contact_autocompletion.php
* @date     $date$
* @version  $Revision$
* @ingroup  sendmail
*/

$db = new Database();

$args     = explode(' ', $_REQUEST['what']);
$args[]   = $_REQUEST['what'];
$num_args = count($args);
if ($num_args == 0) {
    return "<ul></ul>";
}
   
$query    = "SELECT result, SUM(confidence) AS score, ca_id FROM (";

$subQuery[1]=
    "SELECT CASE WHEN is_corporate_person = 'Y' THEN UPPER(society) ELSE COALESCE(contact_firstname, '') || ' ' || UPPER(COALESCE(contact_lastname, '')) END || "
    . " ' - ' || external_contact_id || ' - ' || COALESCE(contact_purpose_label, '') || ' : ' || "
    . " COALESCE(firstname, '') || ' ' || UPPER(COALESCE(lastname, ''))|| ' ' || COALESCE(address_num, '') || ' ' || COALESCE(address_street, '') || ' ' || COALESCE(address_postal_code, '')|| ' ' || COALESCE(address_town, '')|| ' ' || UPPER(COALESCE(address_country, ''))"
    ." || '(Moyen de communication : ' || cc.value || ')' AS result, "
        . ' %d AS confidence, ca_id'
    . " FROM view_contacts left join contact_communication cc on view_contacts.contact_id = cc.contact_id"
    . " WHERE  "
        . " enabled = 'Y' AND cc.value <> '' AND external_contact_id <> '' AND external_contact_id IS NOT NULL"
        . " AND ("
            . " (LOWER(contact_lastname) LIKE LOWER('%s') OR LOWER(lastname) LIKE LOWER('%s'))"
            . " OR (LOWER(contact_firstname) LIKE LOWER('%s') OR LOWER(firstname) LIKE LOWER('%s'))"
            . " OR LOWER(society) LIKE LOWER('%s')"
            . " OR LOWER(society_short) LIKE LOWER('%s')"
    . " OR LOWER(email) LIKE LOWER('%s')"
        .")"
    ."and (is_private = 'N' or ( user_id = '".$_SESSION['user']['UserId']."' and is_private = 'Y'))";

$queryParts = array();

foreach ($args as $arg) {
    if (strlen($arg) == 0) {
        continue;
    }
    # Full match of one given arg
    $expr = $arg;
    $conf = 100;
    $queryParts[] = sprintf($subQuery[1], $conf, $expr, $expr, $expr, $expr, $expr, $expr, $expr);

    # Partial match (starts with)
    $expr = $arg . "%";
    $conf = 34; # If found, partial match contains will also be so score is sum of both confidences, i.e. 67)
    $queryParts[] = sprintf($subQuery[1], $conf, $expr, $expr, $expr, $expr, $expr, $expr, $expr);
  
    # Partial match (contains)
    $expr = "%" . $arg . "%";
    $conf = 33;
    $queryParts[] = sprintf($subQuery[1], $conf, $expr, $expr, $expr, $expr, $expr, $expr, $expr);
}

$query .= implode(' UNION ALL ', $queryParts);
$query .= ") matches"
    . " GROUP BY result, ca_id "
    . " ORDER BY score DESC, result ASC";

$stmt = $db->query($query);
$nb   = $stmt->rowCount();
$m    = 30;
if ($nb >= $m) {
    $l = $m;
} else {
    $l = $nb;
}

echo "<ul title='$l contacts found'>";
for ($i=0; $i<$l; $i++) {
    $res   = $stmt->fetchObject();
    $score = round($res->score / $num_args);
    if ($i%2==1) {
        $color = 'LightYellow';
    } else {
        $color = 'white';
    }
    echo "<li style='font-size: 8pt; background-color:$color;' title='confiance:".$score."%' id='".$res->ca_id."'>". $res->result ."</li>";
}
if($nb == 0) echo "<li></li>";
echo "</ul>";

if($nb == 0) echo "<p align='left' style='background-color:LemonChiffon;' title=\"Aucun résultat trouvé, veuillez compléter votre recherche.\" >...</p>"; 
if($nb > $m) echo "<p align='left' style='background-color:LemonChiffon;' title=\"La liste n'a pas pu être affichée intégralement, veuillez compléter votre recherche.\" >...</p>";