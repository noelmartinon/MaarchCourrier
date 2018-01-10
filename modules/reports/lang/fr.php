<?php
/*
 *
 *    Copyright 2008,2009 Maarch
 *
 *  This file is part of Maarch Framework.
 *
 *   Maarch Framework is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Maarch Framework is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

/*************************** Report management *****************/
if (!defined("_ADMIN_REPORTS"))
    define("_ADMIN_REPORTS","Etats et éditions");
if (!defined("_ADMIN_REPORTS_DESC"))
    define("_ADMIN_REPORTS_DESC","Administration des états et des éditions");
if (!defined("_REPORTS"))
    define("_REPORTS","Etats et Editions");
if (!defined("_REPORTS_COMMENT"))
    define("_REPORTS_COMMENT","Etats et Editions");
if (!defined("_OTHER_REPORTS"))
    define("_OTHER_REPORTS","Editions diverses");
if (!defined("_REPORTS_DESC"))
    define("_REPORTS_DESC","Editions des états");
if (!defined("_HAVE_TO_SELECT_GROUP"))
    define("_HAVE_TO_SELECT_GROUP", "Vous devez sélectionner un groupe");
if (!defined("_AVAILABLE_REPORTS"))
    define("_AVAILABLE_REPORTS", "Etats disponibles pour");
if (!defined("_GROUP_REPORTS_ADDED"))
    define("_GROUP_REPORTS_ADDED", "Etats affectés au groupe");
if (!defined("_CLICK_LINE_BELOW_TO_SEE_REPORT"))
    define("_CLICK_LINE_BELOW_TO_SEE_REPORT", "Veuillez cliquer sur une des lignes ci-dessous pour voir les éditions correspondantes");
if (!defined("_CLICK_LINE_BELOW_TO_RETURN_TO_REPORTS"))
    define("_CLICK_LINE_BELOW_TO_RETURN_TO_REPORTS", "Cliquez ici pour retourner à la liste des éditions disponibles");
if (!defined("_NO_REPORTS_FOR_USER"))
    define("_NO_REPORTS_FOR_USER", "Aucune statistique n'est disponible via le menu pour cet utilisateur");
if (!defined("_PRINT_NO_RESULT"))
    define("_PRINT_NO_RESULT", "Rien à afficher");

//Global
if (!defined("_DOCUMENTS_LIST"))
    define("_DOCUMENTS_LIST","Liste des documents");
if (!defined("_DOCUMENTS_LIST_DESC"))
    define("_DOCUMENTS_LIST_DESC","Liste des documents");

/*************************** Module label in report *****************/
if (!defined("_TITLE_STATS_DU"))
    define("_TITLE_STATS_DU","du");
if (!defined("_TITLE_STATS_AU"))
    define("_TITLE_STATS_AU","au");
if (!defined("_NB_TOTAL_DOC"))
    define("_NB_TOTAL_DOC", "Nombre total de pièces présentes");
if (!defined("_NB_TOTAL_FOLDER"))
    define("_NB_TOTAL_FOLDER", "Nombre total de dossiers présents");
if (!defined("_NO_DATA_MESSAGE"))
    define("_NO_DATA_MESSAGE", "Données insuffisantes pour produire le graphique ou le tableau");
if (!defined("_REPORT"))
    define("_REPORT", "Etat");
if (!defined("_ERROR_REPORT_TYPE"))
    define("_ERROR_REPORT_TYPE", "Erreur avec le type d'état");
if (!defined("_ERROR_PERIOD_TYPE"))
    define("_ERROR_PERIOD_TYPE", "Erreur avec le type de période");
if (!defined("_REPORTS_EVO_PROCESS"))
    define("_REPORTS_EVO_PROCESS", "Evolution du délai moyen de traitement");
if (!defined("_MONTH"))
    define("_MONTH", "Mois");
if (!defined("_PROCESS_DELAI_AVG"))
    define("_PROCESS_DELAI_AVG", "Délai moyen (en jours)");
if (!defined("_PROCESS_DELAY_GENERIC_EVALUATION_REPORT"))
    define("_PROCESS_DELAY_GENERIC_EVALUATION_REPORT", "Délai moyen de traitement par période");
if (!defined("_PROCESS_DELAY_GENERIC_EVALUATION_REPORT_DESC"))
    define("_PROCESS_DELAY_GENERIC_EVALUATION_REPORT_DESC", "Permet d'afficher le délai (en jour) de la création jusqu'à la clôture d'un courrier.");
if (!defined("_PROCESS_DELAY_GENERIC_EVALUATION_REPORT_BY_TYPE"))
    define("_PROCESS_DELAY_GENERIC_EVALUATION_REPORT_BY_TYPE", "Délai moyen de traitement par type de courrier");
if (!defined("_FILESTAT_LIST_DESC"))
    define("_FILESTAT_LIST_DESC", "Afficher la liste des fichiers statistiques disponibles.");
if (!defined("_FILESTAT_DESC"))
    define("_FILESTAT_DESC", "Ces fichiers sont générés via <b>tâche(s) plannifiée(s)</b> présentes dans le module <b>life_cycle</b>.<br/>Seul les <b>10 derniers fichiers</b> sont affichés.");