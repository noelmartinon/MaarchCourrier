<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
* Module : Tags
*
* This module is used to store ressources with any keywords
*
* @file
* @author dev
* @date $date$
* @version $Revision$
*/

/*********************** TAGS ***********************************/
if (!defined('_TAG_DEFAULT')) {
    define('_TAG_DEFAULT', 'Sleutelwoord');
}
if (!defined('_TAGS_DEFAULT')) {
    define('_TAGS_DEFAULT', 'Sleutelwoorden');
}
if (!defined('_TAGS_COMMENT')) {
    define('_TAGS_COMMENT', 'Sleutelwoorden');
}
if (!defined('_TAGS_LIST')) {
    define('_TAGS_LIST', 'Lijst van de '.strtolower(_TAGS_DEFAULT));
}
if (!defined('_MODIFY_TAG')) {
    define('_MODIFY_TAG', 'De '.strtolower(_TAG_DEFAULT).' wijzigen');
}
if (!defined('_MANAGE_TAGS')) {
    define('_MANAGE_TAGS', 'De '.strtolower(_TAGS_DEFAULT).' beheren');
}
if (!defined('_ADMIN_TAGS')) {
    define('_ADMIN_TAGS', 'Sleutelwoorden');
}
if (!defined('_ADMIN_TAGS_DESC')) {
    define('_ADMIN_TAGS_DESC', 'Voor het wijzigen / verwijderen / toevoegen of fusioneren van '.strtolower(_TAGS_DEFAULT).' vanuit de beheerinterface');
}
if (!defined('_ALL_TAGS')) {
    define('_ALL_TAGS', 'Alle '.strtolower(_TAGS_DEFAULT));
}
if (!defined('_TAG_DELETED')) {
    define('_TAG_DELETED', _TAG_DEFAULT.' verwijderd');
}
if (!defined('_TAG_ADDED')) {
    define('_TAG_ADDED', _TAG_DEFAULT.' toegevoegd');
}
if (!defined('_TAG_UPDATED')) {
    define('_TAG_UPDATED', _TAG_DEFAULT.' gewijzigd');
}
if (!defined('_TAG_LABEL_IS_EMPTY')) {
    define('_TAG_LABEL_IS_EMPTY', 'De omschrijving is leeg');
}
if (!defined('_NO_TAG')) {
    define('_NO_TAG', 'Geen '._TAG_DEFAULT);
}
if (!defined('_TAG_VIEW')) {
    define('_TAG_VIEW', 'De '.strtolower(_TAGS_DEFAULT).' van de documenten bekijken');
}
if (!defined('_TAG_VIEW_DESC')) {
    define('_TAG_VIEW_DESC', 'Om het veld '.strtolower(_TAGS_DEFAULT).' weer te geven vanuit de actiepagina’s en de gedetailleerde fiche.');
}
if (!defined('_ADD_TAG')) {
    define('_ADD_TAG', 'Toevoegen van een '.strtolower(_TAG_DEFAULT));
}
if (!defined('_ADD_TAG_TO_RES')) {
    define('_ADD_TAG_TO_RES', 'Beschikbare '.strtolower(_TAGS_DEFAULT).' voor een document toevoegen');
}
if (!defined('_CREATE_TAG')) {
    define('_CREATE_TAG', strtolower(_TAGS_DEFAULT).' aanmaken vanuit de actiepagina’s');
}
if (!defined('_CREATE_TAG_DESC')) {
    define('_CREATE_TAG_DESC', 'Om snel '.strtolower(_TAGS_DEFAULT).' op te slaan die niet in de database staan');
}
if (!defined('_ADD_TAG_TO_RES_DESC')) {
    define('_ADD_TAG_TO_RES_DESC', 'Om '.strtolower(_TAGS_DEFAULT).' te koppelen aan een document via het veld '.strtolower(_TAGS_DEFAULT).' vanuit de actiepagina’s en de gedetailleerde fiche');
}
if (!defined('_DELETE_TAG_TO_RES')) {
    define('_DELETE_TAG_TO_RES', strtolower(_TAGS_DEFAULT).' verwijderen bij een ressource');
}
if (!defined('_DELETE_TAG_TO_RES_DESC')) {
    define('_DELETE_TAG_TO_RES_DESC', 'Om '.strtolower(_TAGS_DEFAULT). ' te verwijderen bij een ressource');
}
if (!defined('_NEW_TAG_IN_LIBRARY_RIGHTS')) {
    define('_NEW_TAG_IN_LIBRARY_RIGHTS', 'Nieuwe '.strtolower(_TAGS_DEFAULT).' aanmaken in de Maarch-bibliotheek');
}
if (!defined('_NEW_TAG_IN_LIBRARY_RIGHTS_DESC')) {
    define('_NEW_TAG_IN_LIBRARY_RIGHTS_DESC', 'Door deze '.strtolower(_TAG_DEFAULT).' te activeren kan de gebruiker nieuwe '.strtolower(_TAGS_DEFAULT).' in de Maarch-bibliotheek toevoegen');
}
if (!defined('_TAG')) {
    define('_TAG', _TAG_DEFAULT);
}
if (!defined('_TAGS')) {
    define('_TAGS', _TAGS_DEFAULT);
}
if (!defined('_TAG_SEPARATOR_HELP')) {
    define('_TAG_SEPARATOR_HELP', 'Scheid de '.strtolower(_TAGS_DEFAULT).' door op Enter te drukken of met komma’s');
}
if (!defined('_NB_DOCS_FOR_THIS_TAG')) {
    define('_NB_DOCS_FOR_THIS_TAG', 'gekoppeld(e) document(en)');
}
if (!defined('_TAGOTHER_OPTIONS')) {
    define('_TAGOTHER_OPTIONS', 'Andere opties');
}
if (!defined('_TAG_FUSION_ACTIONLABEL')) {
    define('_TAG_FUSION_ACTIONLABEL', 'de '.strtolower(_TAG_DEFAULT).' samenvoegen met');
}
if (!defined('_TAGFUSION')) {
    define('_TAGFUSION', 'Samenvoeging');
}
if (!defined('_TAGFUSION_GOODRESULT')) {
    define('_TAGFUSION_GOODRESULT', 'Deze '.strtolower(_TAGS_DEFAULT).' zijn nu samengevoegd');
}
if (!defined('_TAG_ALREADY_EXISTS')) {
    define('_TAG_ALREADY_EXISTS', 'Deze '.strtolower(_TAG_DEFAULT).' bestaat reeds');
}
if (!defined('_CHOOSE_TAG')) {
    define('_CHOOSE_TAG', 'Keuze van de '.strtolower(_TAGS_DEFAULT));
}
if (!defined('_TAG_SEARCH')) {
    define('_TAG_SEARCH', 'Sleutelwoorden');
}
if (!defined('_TAGNONE')) {
    define('_TAGNONE', 'Geen');
}
if (!defined('_ALL_TAG_DELETED_FOR_RES_ID')) {
    define('_ALL_TAG_DELETED_FOR_RES_ID', 'Alle '.strtolower(_TAGS_DEFAULT).' zijn verwijderd voor de resource');
}
if (!defined('_TAGCLICKTODEL')) {
    define('_TAGCLICKTODEL', 'verwijderen');
}
if (!defined('_NAME_TAGS')) {
    define('_NAME_TAGS', 'Naam van de '.strtolower(_TAG_DEFAULT));
}
if (!defined('_PRIVATE_TAGS')) {
    define('_PRIVATE_TAGS', 'De '.strtolower(_TAGS_DEFAULT).' verbinden met de eenheid van de gebruiker (Directieniveau)');
}
if (!defined('_PRIVATE_TAGS_DESC')) {
    define('_PRIVATE_TAGS_DESC', 'De gebruiker zal enkel de '.strtolower(_TAGS_DEFAULT).' zien die beperkt werden tot zijn directie (de toevoeging / wijziging zal het sleutelwoord automatisch aan zijn directie koppelen).');
}
if (!defined('_ADD_TAG_CONFIRM')) {
    define('_ADD_TAG_CONFIRM', 'Dit woord zal toegevoegd worden als '.strtolower(_TAG_DEFAULT).'.');
}
