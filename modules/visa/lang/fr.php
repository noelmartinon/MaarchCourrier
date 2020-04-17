<?php

// CIRCUIT DE VISA
if (!defined('_VISA_WORKFLOW')) {
    define('_VISA_WORKFLOW', 'Circuit de visa');
}
if (!defined('_INTERRUPT_WORKFLOW')) {
    define('_INTERRUPT_WORKFLOW', 'Interrompre le circuit de visa');
}
if (!defined('_VISA_WORKFLOW_COMMENT')) {
    define('_VISA_WORKFLOW_COMMENT', 'Gestion du circuit de visa');
}

if (!defined('_NO_VISA')) {
    define('_NO_VISA', 'Aucune personne désignée en visa');
}
if (!defined('_NO_RESPONSE_PROJECT_VISA')) {
    define('_NO_RESPONSE_PROJECT_VISA', 'Veuillez intégrer au moins une pièce jointe au parapheur.');
}

// CIRCUIT D"AVIS
if (!defined('_AVIS_WORKFLOW')) {
    define('_AVIS_WORKFLOW', "Circuit d'avis");
}

if (!defined('_DISSMARTCARD_SIGNER_APPLET')) {
    define('_DISSMARTCARD_SIGNER_APPLET', 'Signature électronique en cours...');
}

if (!defined('_IMG_SIGN_MISSING')) {
    define('_IMG_SIGN_MISSING', 'Image de signature manquante');
}

if (!defined('_SEND_TO_SIGNATURE')) {
    define('_SEND_TO_SIGNATURE', 'Soumettre');
}

if (!defined('_SUBMIT_COMMENT')) {
    define('_SUBMIT_COMMENT', 'Commentaire de visa (optionnel) ');
}

if (!defined('_NO_FILE_PRINT')) {
    define('_NO_FILE_PRINT', 'Aucun fichier à imprimer');
}

if (!defined('_BAD_PIN')) {
    define('_BAD_PIN', 'Code PIN incorrect. Attention, 3 essais maximum !');
}

if (!defined('_PRINT_DOCUMENT')) {
    define('_PRINT_DOCUMENT', 'Afficher et imprimer le document');
}

if (!defined('_VISA_BY')) {
    define('_VISA_BY', 'Visa par');
}

if (!defined('_INSTEAD_OF')) {
    define('_INSTEAD_OF', 'à la place de');
}

if (!defined('_WAITING_FOR_SIGN')) {
    define('_WAITING_FOR_SIGN', 'En attente de la signature');
}

if (!defined('_SIGNED')) {
    define('_SIGNED', 'Signé');
}

if (!defined('_WAITING_FOR_VISA')) {
    define('_WAITING_FOR_VISA', 'En attente du visa');
}

if (!defined('_VISED')) {
    define('_VISED', 'Visé');
}

if (!defined('DOWN_USER_WORKFLOW')) {
    define('DOWN_USER_WORKFLOW', "Déplacer l'utilisateur vers le bas");
}

if (!defined('UP_USER_WORKFLOW')) {
    define('UP_USER_WORKFLOW', "Déplacer l'utilisateur vers le haut");
}

if (!defined('ADD_USER_WORKFLOW')) {
    define('ADD_USER_WORKFLOW', 'Ajouter un utilisateur dans le circuit');
}

if (!defined('DEL_USER_WORKFLOW')) {
    define('DEL_USER_WORKFLOW', "Retirer l'utilisateur du circuit");
}

if (!defined('_NO_NEXT_STEP_VISA')) {
    define('_NO_NEXT_STEP_VISA', "Impossible d'effectuer cette action. Le circuit ne contient pas d'étape supplémentaire.");
}

if (!defined('_VISA_USERS')) {
    define('_VISA_USERS', 'Personne(s) pour visa / signature');
}

if (!defined('_TMP_SIGNED_FILE_FAILED')) {
    define('_TMP_SIGNED_FILE_FAILED', 'Echec de la génération du document avec signature');
}

if (!defined('NO_PLACE_SIGNATURE')) {
    define('NO_PLACE_SIGNATURE', 'Aucun emplacement de signature');
}

if (!defined('_ENCRYPTED')) {
    define('_ENCRYPTED', 'crypté');
}

if (!defined('_VISA_USER_COU')) {
    define('_VISA_USER_COU', "Vous êtes l'actuel viseur");
}

if (!defined('_VISA_USER_COU_DESC')) {
    define('_VISA_USER_COU_DESC', 'Vous visez à la place de');
}

if (!defined('_SIGN_USER_COU')) {
    define('_SIGN_USER_COU', "Vous êtes l'actuel signataire");
}

if (!defined('_SIGN_USER_COU_DESC')) {
    define('_SIGN_USER_COU_DESC', 'Vous signez à la place de');
}

if (!defined('_SIGN_USER')) {
    define('_SIGN_USER', 'Personne signataire');
}

if (!defined('_ADD_VISA_ROLE')) {
    define('_ADD_VISA_ROLE', 'Ajouter un viseur');
}

if (!defined('_ADD_VISA_MODEL')) {
    define('_ADD_VISA_MODEL', 'Utiliser un modèle de circuit de visa');
}

if (!defined('_NO_SIGNATORY')) {
    define('_NO_SIGNATORY', 'Aucun signataire');
}

if (!defined('_SIGNATORY')) {
    define('_SIGNATORY', 'SIGNATAIRE');
}

if (!defined('_SIGNED_TO')) {
    define('_SIGNED_TO', 'Signé le');
}

if (!defined('_SIGN_IN_PROGRESS')) {
    define('_SIGN_IN_PROGRESS', 'En cours de signature');
}

if (!defined('_DOCUMENTS_LIST_WITH_SIGNATORY')) {
    define('_DOCUMENTS_LIST_WITH_SIGNATORY', 'Liste des documents avec signataire');
}

/***** Signature Book *****/
if (!defined('_DEFINE_MAIL')) {
    define('_DEFINE_MAIL', 'Courrier');
}
if (!defined('_PROGRESSION')) {
    define('_PROGRESSION', 'Avancement');
}
if (!defined('_ACCESS_TO_DETAILS')) {
    define('_ACCESS_TO_DETAILS', 'Accédez à la fiche détaillée');
}
if (!defined('_SB_INCOMING_MAIL_ATTACHMENTS')) {
    define('_SB_INCOMING_MAIL_ATTACHMENTS', 'pièce(s) complémentaire(s)');
}
if (!defined('_DOWNLOAD_ATTACHMENT')) {
    define('_DOWNLOAD_ATTACHMENT', 'Télécharger la pièce jointe');
}
if (!defined('_DEFINE_FOR')) {
    define('_DEFINE_FOR', 'Pour');
}
if (!defined('_CHRONO')) {
    define('_CHRONO', 'Chrono');
}
if (!defined('_DRAFT')) {
    define('_DRAFT', 'Brouillon');
}
if (!defined('_UPDATE_ATTACHMENT')) {
    define('_UPDATE_ATTACHMENT', 'Modifier la pièce jointe');
}
if (!defined('_DELETE_ATTACHMENT')) {
    define('_DELETE_ATTACHMENT', 'Supprimer la pièce jointe');
}
if (!defined('_DISPLAY_ATTACHMENTS')) {
    define('_DISPLAY_ATTACHMENTS', 'Afficher la liste des pièces jointes');
}
/***** Signature Book *****/

//Parameters
if (!defined('_PARAMETER_IDENTIFIER')) {
    define('_PARAMETER_IDENTIFIER', 'Identifiant');
}

if (!defined('_DESCRIPTION')) {
    define('_DESCRIPTION', 'Description');
}

if (!defined('_VALUE')) {
    define('_VALUE', 'Valeur');
}

if (!defined('_TYPE')) {
    define('_TYPE', 'Type');
}

if (!defined('_STRING')) {
    define('_STRING', 'Chaîne de caractères');
}

if (!defined('_INTEGER')) {
    define('_INTEGER', 'Nombre entier');
}

if (!defined('_VALIDATE')) {
    define('_VALIDATE', 'Valider');
}

if (!defined('_CANCEL')) {
    define('_CANCEL', 'Annuler');
}

if (!defined('_MODIFY_PARAMETER')) {
    define('_MODIFY_PARAMETER', 'Modifier paramètre');
}

if (!defined('_DELETE_PARAMETER')) {
    define('_DELETE_PARAMETER', 'Supprimer paramètre');
}

if (!defined('_PAGE')) {
    define('_PAGE', 'Page');
}

if (!defined('_OUT_OF')) {
    define('_OUT_OF', 'sur');
}

if (!defined('_SEARCH')) {
    define('_SEARCH', 'Chercher');
}

if (!defined('_RECORDS_PER_PAGE')) {
    define('_RECORDS_PER_PAGE', 'résultats par page');
}

if (!defined('_DISPLAY')) {
    define('_DISPLAY', 'Afficher');
}

if (!defined('_NO_RECORDS')) {
    define('_NO_RECORDS', 'Aucun résultat');
}

if (!defined('_AVAILABLE')) {
    define('_AVAILABLE', 'disponible');
}

if (!defined('_FILTERED_FROM')) {
    define('_FILTERED_FROM', 'filtré sur un ensemble de ');
}

if (!defined('_RECORDS')) {
    define('_RECORDS', 'résultats');
}

if (!defined('_FIRST')) {
    define('_FIRST', 'premier');
}

if (!defined('_LAST')) {
    define('_LAST', 'dernier');
}

if (!defined('_NEXT')) {
    define('_NEXT', 'suivant');
}

if (!defined('_PREVIOUS')) {
    define('_PREVIOUS', 'précédent');
}

if (!defined('_PARAMETER')) {
    define('_PARAMETER', 'paramètre');
}

if (!defined('_DELETE_CONFIRM')) {
    define('_DELETE_CONFIRM', 'Voulez-vous vraiment supprimer le paramètre');
}

if (!defined('_NO_USER_SIGNED_DOC')) {
    define('_NO_USER_SIGNED_DOC', "vous n'avez PAS signé de pièce jointe !");
}

if (!defined('_IS_ALL_ATTACHMENT_SIGNED_INFO')) {
    define('_IS_ALL_ATTACHMENT_SIGNED_INFO', 'Vous ne pourrez pas demander de signature aux utilisateurs, aucune pièce jointe présente dans le parapheur');
}

if (!defined('_IS_ALL_ATTACHMENT_SIGNED_INFO2')) {
    define('_IS_ALL_ATTACHMENT_SIGNED_INFO2', 'Toutes les pièces jointes présentes dans le parapheur ont été signées.');
}

if (!defined('_REQUESTED_SIGNATURE')) {
    define('_REQUESTED_SIGNATURE', 'Signature demandée');
}

if (!defined('_HANDWRITTEN_SIGN')) {
    define('_HANDWRITTEN_SIGN', "Signature manuscrite");
}

if (!defined('_ESIGN')) {
    define('_ESIGN', "Signature électronique");
}

/* NCH01 */

if (!defined('_PJ_NUMBER')) {
    define('_PJ_NUMBER', "PJ n°");
}

if (!defined('_AND_DOC_ORIG')) {
    define('_AND_DOC_ORIG', "et document original n°");
}

if (!defined('_EXTERNAL_ID_EMPTY')) {
    define('_EXTERNAL_ID_EMPTY', "La référence externe est vide");
}

if (!defined('_SEND_TO_IPARAPHEUR')) {
    define('_SEND_TO_IPARAPHEUR', "Envoyer à l'IParapheur ?");
}

if (!defined('_SEND_TO_FAST')) {
    define('_SEND_TO_FAST', "Envoyer au parapheur FAST ?");
}

if (!defined('_PROJECT_NUMBER')) {
    define('_PROJECT_NUMBER', "Projet courrier numéro ");
}

if (!defined('_MAIL_NOTE')) {
    define('_MAIL_NOTE', "Annotation du document principal");
}
if (!defined('_ATTACHMENT_SIGNATURE')) {
    define('_ATTACHMENT_SIGNATURE', "Signature des documents intégrés au parapheur");
}
if (!defined('_NOTE_USER')) {
    define('_NOTE_USER', "Annotateur");
}
if (!defined('_WF_SEND_TO')) {
    define('_WF_SEND_TO', "Envoyé à :");
}
if (!defined('_VISA_USER_MIN')) {
    define('_VISA_USER_MIN', "Viseur");
}
