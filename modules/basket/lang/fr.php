<?php
/*
 *
 *    Copyright 2008-2015 Maarch
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

/*********************** SERVICES ***********************************/
if (!defined("_DIFFUSION_LIST")) {
    define("_DIFFUSION_LIST", "Liste de diffusion");
}

//class basket
if (!defined("_BASKET")) {
    define("_BASKET", "Bannette");
}
if (!defined("_BASKETS_COMMENT")) {
    define("_BASKETS_COMMENT", "Bannettes");
}
if (!defined("_THE_ID")) {
    define("_THE_ID", "L'identifiant ");
}

/************** Bannette : Liste + Formulaire**************/
if (!defined("_ALL_BASKETS")) {
    define("_ALL_BASKETS", "Tout mon périmètre");
}
if (!defined("_BASKET_LIST")) {
    define("_BASKET_LIST", "Liste des bannettes");
}
if (!defined("_ADD_BASKET")) {
    define("_ADD_BASKET", "Ajouter une bannette");
}
if (!defined("_BASKET_ADDITION")) {
    define("_BASKET_ADDITION", "Ajout d'une bannette");
}
if (!defined("_BASKET_MODIFICATION")) {
    define("_BASKET_MODIFICATION", "Modification d'une bannette");
}
if (!defined("_BASKET_VIEW")) {
    define("_BASKET_VIEW", "Vue sur la table");
}
if (!defined("_MODIFY_BASKET")) {
    define("_MODIFY_BASKET", "Modifier la bannette");
}
if (!defined("_ADD_A_NEW_BASKET")) {
    define("_ADD_A_NEW_BASKET", "Créer une nouvelle bannette");
}
if (!defined("_ADD_A_GROUP_TO_BASKET")) {
    define("_ADD_A_GROUP_TO_BASKET", "Associer un nouveau groupe à la bannette");
}
if (!defined("_DEL_GROUPS")) {
    define("_DEL_GROUPS", "Supprimer groupe(s)");
}
if (!defined("_ASSOCIATED_GROUP")) {
    define("_ASSOCIATED_GROUP", "Liste des groupes associés à la bannette");
}
if (!defined("_TITLE_GROUP_BASKET")) {
    define("_TITLE_GROUP_BASKET", "Associer la bannette à un groupe");
}
if (!defined("_ADD_TO_BASKET")) {
    define("_ADD_TO_BASKET", "Associer la bannette");
}
if (!defined("_TO_THE_GROUP")) {
    define("_TO_THE_GROUP", "à un groupe");
}
if (!defined("_ALLOWED_ACTIONS")) {
    define("_ALLOWED_ACTIONS", "Actions autorisées");
}
if (!defined("_SERVICES_BASKETS")) {
    define("_SERVICES_BASKETS", "Bannettes de services");
}
if (!defined("_USERGROUPS_BASKETS")) {
    define("_USERGROUPS_BASKETS", "Bannettes des groupes d'utilisateurs");
}
if (!defined("_BASKET_RESULT_PAGE")) {
    define("_BASKET_RESULT_PAGE", "Liste de résultats");
}
if (!defined("_ADD_THIS_GROUP")) {
    define("_ADD_THIS_GROUP", "Ajouter le groupe");
}
if (!defined("_MODIFY_THIS_GROUP")) {
    define("_MODIFY_THIS_GROUP", "Modifier le groupe");
}
if (!defined("_DEFAULT_ACTION_LIST")) {
    define("_DEFAULT_ACTION_LIST", "Action par défaut sur la liste<br/><i>(Cliquez sur la ligne)");
}
if (!defined("_NO_ACTION_DEFINED")) {
    define("_NO_ACTION_DEFINED", "Aucune action définie");
}

//LIST
if (!defined("_COPY_LIST")) {
    define("_COPY_LIST", "Liste des courriers en copie");
}
if (!defined("_PROCESS_LIST")) {
    define("_PROCESS_LIST", "Liste des courriers à traiter");
}
if (!defined("_CLICK_LINE_TO_VIEW")) {
    define("_CLICK_LINE_TO_VIEW", "Cliquez sur une ligne pour visualiser");
}
if (!defined("_CLICK_LINE_TO_PROCESS")) {
    define("_CLICK_LINE_TO_PROCESS", "Cliquez sur une ligne pour traiter");
}
if (!defined("_REDIRECT_TO_SENDER_ENTITY")) {
    define("_REDIRECT_TO_SENDER_ENTITY", "Redirection vers l'entité émetteur");
}
if (!defined("_CHOOSE_DEPARTMENT")) {
    define("_CHOOSE_DEPARTMENT", "Choisissez une entité");
}
if (!defined("_ENTITY_UPDATE")) {
    define("_ENTITY_UPDATE", "Entité mise à jour");
}

// USER ABS
if (!defined("_MY_ABS")) {
    define("_MY_ABS", "Gérer mes absences");
}
if (!defined("_MY_ABS_TXT")) {
    define("_MY_ABS_TXT", "Permet de rediriger vos bannettes en cas de départ en congé.");
}
if (!defined("_MY_ABS_REDIRECT")) {
    define("_MY_ABS_REDIRECT", "Vos courriers sont actuellement redirigés vers");
}
if (!defined("_MY_ABS_DEL")) {
    define("_MY_ABS_DEL", "Pour supprimer la redirection, cliquez ici pour stopper");
}
if (!defined("_ADMIN_ABS")) {
    define("_ADMIN_ABS", "Gérer les absences.");
}
if (!defined("_ADMIN_ABS_TXT")) {
    define("_ADMIN_ABS_TXT", "Permet de rediriger le courrier de l'utilisateur en attente en cas de départ en congé.");
}
if (!defined("_ADMIN_ABS_REDIRECT")) {
    define("_ADMIN_ABS_REDIRECT", "Redirection d'absence en cours.");
}
if (!defined("_ADMIN_ABS_FIRST_PART")) {
    define("_ADMIN_ABS_FIRST_PART", "Les courrier de");
}
if (!defined("_ADMIN_ABS_SECOND_PART")) {
    define("_ADMIN_ABS_SECOND_PART", "sont actuellement redirigés vers ");
}
if (!defined("_ADMIN_ABS_THIRD_PART")) {
    define("_ADMIN_ABS_THIRD_PART", ". Cliquez ici pour supprimer la redirection.");
}
if (!defined("_ACTIONS_DONE")) {
    define("_ACTIONS_DONE", "Actions effectuées le");
}
if (!defined("_PROCESSED_MAIL")) {
    define("_PROCESSED_MAIL", "Courriers traités");
}
if (!defined("_INDEXED_MAIL")) {
    define("_INDEXED_MAIL", "Courriers indexés");
}
if (!defined("_REDIRECTED_MAIL")) {
    define("_REDIRECTED_MAIL", "Courriers redirigés");
}
if (!defined("_PROCESS_MAIL_OF")) {
    define("_PROCESS_MAIL_OF", "Courrier à traiter de");
}
if (!defined("_MISSING")) {
    define("_MISSING", "Absent");
}
if (!defined("_BACK_FROM_VACATION")) {
    define("_BACK_FROM_VACATION", "de retour de son absence");
}
if (!defined("_MISSING_CHOOSE")) {
    define("_MISSING_CHOOSE", "Souhaitez-vous continuer?");
}
if (!defined("_CONFIG")) {
    define("_CONFIG", "(paramètrer)");
}
if (!defined("_IN_ACTION")) {
    define("_IN_ACTION", " dans l'action");
}
if (!defined("_TO_ENTITIES")) {
    define("_TO_ENTITIES", "Vers des entités");
}
if (!defined("_TO_USERGROUPS")) {
    define("_TO_USERGROUPS", "Vers des groupes d'utilisateur");
}
if (!defined("_USE_IN_MASS")) {
    define("_USE_IN_MASS", "Action disponible dans la liste");
}
if (!defined("_USE_ONE")) {
    define("_USE_ONE", "Action disponible dans la page d'action");
}
if (!defined("_SAVE_CHANGES")) {
    define("_SAVE_CHANGES", "Enregistrer les modifications");
}
if (!defined("_VIEW_BASKETS")) {
    define("_VIEW_BASKETS", "Mes bannettes");
}
if (!defined("_MY_BASKETS")) {
    define("_MY_BASKETS", "Mes bannettes");
}
if (!defined("_NAME")) {
    define("_NAME", "Nom");
}
if (!defined("_FORM_ERROR")) {
    define("_FORM_ERROR", "Erreur dans la transmission du formulaire...");
}
if (!defined("_ABS_LOG_OUT")) {
    define("_ABS_LOG_OUT", "si vous vous reconnectez, le mode absent sera annulé.");
}
if (!defined("_ABS_USER")) {
    define("_ABS_USER", "Utilisateur absent");
}
if (!defined("_ABSENCE")) {
    define("_ABSENCE", "Absence");
}
if (!defined("_BASK_BACK")) {
    define("_BASK_BACK", "Retour");
}
if (!defined("_FILTER_BY")) {
    define("_FILTER_BY", "Filtrer par");
}
if (!defined("_VIEWED")) {
    define("_VIEWED", "Vu?");
}

//NEW WF
if (!defined("_WF")) {
    define("_WF", "Workflow");
}
if (!defined("_POSITION")) {
    define("_POSITION", "Position");
}
if (!defined("_SPREAD_SEARCH_TO_BASKETS")) {
    define("_SPREAD_SEARCH_TO_BASKETS", "Etendre la recherche aux bannettes");
}
