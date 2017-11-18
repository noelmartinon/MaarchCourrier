<?php

if (!defined("_SIGN_DOCS"))
    define("_SIGN_DOCS", "Signer les documents");

if (!defined("_PRINTFOLDER"))
    define("_PRINTFOLDER", "Dossier d'impression");

if (!defined("_PRINT_FOLDER_DOC"))
    define("_PRINT_FOLDER_DOC", "Imprimer le dossier complet");

if (!defined("_NO_PDF_FILE"))
    define("_NO_PDF_FILE", "Aucun PDF présent pour ce fichier");

// CIRCUIT DE VISA
if (!defined("_VISA_WORKFLOW"))
    define("_VISA_WORKFLOW", "Circuit de visa");
if (!defined("_INTERRUPT_WORKFLOW"))
    define("_INTERRUPT_WORKFLOW", "Interrompre le circuit de visa");
if (!defined("_VISA_WORKFLOW_COMMENT"))   
    define("_VISA_WORKFLOW_COMMENT", "Gestion du circuit de visa");
if (!defined("_VIEW_VISA_WORKFLOW"))
    define("_VIEW_VISA_WORKFLOW", "Visualisation du circuit de visa");
if (!defined("_VIEW_VISA_WORKFLOW_DESC"))
    define("_VIEW_VISA_WORKFLOW_DESC", "Permet de visualiser le circuit de visa dans les parties de liste de diffusion et dans celles d'avancement.");
if (!defined("_CONFIG_VISA_WORKFLOW"))
    define("_CONFIG_VISA_WORKFLOW", "Configuration du circuit de visa");
if (!defined("_CONFIG_VISA_WORKFLOW_DESC"))
    define("_CONFIG_VISA_WORKFLOW_DESC", "Permet de configurer le circuit de visa que devra prendre le courrier");
if (!defined("_EMPTY_USER_LIST"))
    define("_EMPTY_USER_LIST", "La liste des utilisateurs est vide");
if (!defined("_EMPTY_VISA_WORKFLOW"))
    define("_EMPTY_VISA_WORKFLOW", "Aucun circuit de visa paramétré");
if (!defined("_VISA_ANSWERS"))
    define("_VISA_ANSWERS", "Viser les projets de réponse");

if (!defined("_VISA_ANSWERS_DESC"))
    define("_VISA_ANSWERS_DESC", "Permet de viser les projets de réponse");

if (!defined("_NO_VISA"))
    define("_NO_VISA", "Aucune personne désignée en visa");
if (!defined("_NO_RESPONSE_PROJECT_VISA"))
    define("_NO_RESPONSE_PROJECT_VISA", "Veuillez intégrer au moins une pièce jointe au parapheur.");
if (!defined("_NO_CONVERTED_PDF_VISA"))
    define("_NO_CONVERTED_PDF_VISA", "Veuillez éditer votre/vos pièce(s) jointe(s)");
if (!defined("_PLEASE_CONVERT_PDF_VISA"))
    define("_PLEASE_CONVERT_PDF_VISA", "Veuillez éditer les pièces jointes suivantes : ");

// CIRCUIT D"AVIS
if (!defined("_AVIS_WORKFLOW"))
    define("_AVIS_WORKFLOW", "Circuit d'avis");
if (!defined("_CONFIG_AVIS_WORKFLOW"))
    define("_CONFIG_AVIS_WORKFLOW", "Configuration du circuit d'avis");
if (!defined("_CONFIG_AVIS_WORKFLOW_DESC"))
    define("_CONFIG_AVIS_WORKFLOW_DESC", "Permet de configurer le circuit d'avis du courrier");

if (!defined("_THUMBPRINT"))
    define("_THUMBPRINT", "Empreinte numérique");

if (!defined("_DISSMARTCARD_SIGNER_APPLET"))
    define("_DISSMARTCARD_SIGNER_APPLET", "Signature électronique en cours...");


if (!defined("_IMG_SIGN_MISSING"))
    define("_IMG_SIGN_MISSING", "Image de signature manquante");

if (!defined("_THUMBPRINT_MISSING"))
    define("_THUMBPRINT_MISSING", "Empreinte numérique manquante");

if (!defined("_SEND_TO_SIGNATURE"))
    define("_SEND_TO_SIGNATURE", "Soumettre");

if (!defined("_SUBMIT_COMMENT"))
    define("_SUBMIT_COMMENT", "Commentaire de visa (optionnel) ");

if (!defined("_NO_FILE_PRINT"))
    define("_NO_FILE_PRINT", "Aucun fichier à imprimer");

if (!defined("_BAD_PIN"))
    define("_BAD_PIN", "Code PIN incorrect. Attention, 3 essais maximum !");

if (!defined("_PRINT_DOCUMENT"))
    define("_PRINT_DOCUMENT", "Afficher et imprimer le document");

if (!defined("_VISA_BY"))
    define("_VISA_BY", "Visa par");

if (!defined("_INSTEAD_OF"))
    define("_INSTEAD_OF", "à la place de");

if (!defined("_CONFIG_VISA_WORKFLOW_IN_DETAIL"))
    define("_CONFIG_VISA_WORKFLOW_IN_DETAIL", "Configuration du circuit de visa depuis la fiche détaillée");

if (!defined("_CONFIG_VISA_WORKFLOW_DESC"))
    define("_CONFIG_VISA_WORKFLOW_DESC", "Permet de configurer le circuit de visa depuis la fiche détaillée");

if (!defined("_WAITING_FOR_SIGN"))
    define("_WAITING_FOR_SIGN", "En attente de la signature");

if (!defined("_SIGNED"))
    define("_SIGNED", "Signé");

if (!defined("_WAITING_FOR_VISA"))
    define("_WAITING_FOR_VISA", "En attente du visa");

if (!defined("_VISED"))
    define("_VISED", "Visé");

if (!defined("DOWN_USER_WORKFLOW"))
    define("DOWN_USER_WORKFLOW", "Déplacer l'utilisateur vers le bas");

if (!defined("UP_USER_WORKFLOW"))
    define("UP_USER_WORKFLOW", "Déplacer l'utilisateur vers le haut");

if (!defined("ADD_USER_WORKFLOW"))
    define("ADD_USER_WORKFLOW", "Ajouter un utilisateur dans le circuit");

if (!defined("DEL_USER_WORKFLOW"))
    define("DEL_USER_WORKFLOW", "Retirer l'utilisateur du circuit");

if (!defined("_NO_NEXT_STEP_VISA"))
    define("_NO_NEXT_STEP_VISA", "Impossible d'effectuer cette action. Le circuit ne contient pas d'étape supplémentaire.");

if (!defined("_VISA_USERS"))
    define("_VISA_USERS", "Personne(s) pour visa / signature");

if (!defined("_TMP_SIGNED_FILE_FAILED"))
    define("_TMP_SIGNED_FILE_FAILED", "Echec de la génération document avec signature");

if (!defined("NO_PLACE_SIGNATURE"))
    define("NO_PLACE_SIGNATURE", "Aucun emplacement de signature");

if (!defined("_ENCRYPTED"))
    define("_ENCRYPTED", "crypté");

if (!defined("_VISA_USER_COU"))
    define("_VISA_USER_COU", "Vous êtes l'actuel viseur");

if (!defined("_VISA_USER_COU_DESC"))
    define("_VISA_USER_COU_DESC", "Vous visez à la place de");

if (!defined("_SIGN_USER_COU"))
    define("_SIGN_USER_COU", "Vous êtes l'actuel signataire");

if (!defined("_SIGN_USER_COU_DESC"))
    define("_SIGN_USER_COU_DESC", "Vous signez à la place de");

if (!defined("_SIGN_USER"))
    define("_SIGN_USER", "Personne signataire");

if (!defined("_ADD_VISA_ROLE"))
    define("_ADD_VISA_ROLE", "Ajouter un viseur / signataire");

if (!defined("_ADD_VISA_MODEL"))
    define("_ADD_VISA_MODEL", "Utiliser un modèle de circuit de visa");

if (!defined("_MODIFY_VISA_IN_SIGNATUREBOOK"))
    define("_MODIFY_VISA_IN_SIGNATUREBOOK", "Modifier le viseur en cours depuis le parapheur");

if (!defined("_MODIFY_VISA_IN_SIGNATUREBOOK_DESC"))
    define("_MODIFY_VISA_IN_SIGNATUREBOOK_DESC", "Utile si le parahpeur sert en tant que parapheur de supervision");

if (!defined("_NO_SIGNATORY"))
    define("_NO_SIGNATORY", "Aucun signataire");

if (!defined("_SIGNATORY"))
    define("_SIGNATORY", "Signataire");

if (!defined("_SIGNED_TO"))
    define("_SIGNED_TO", "Signé le");

if (!defined("_SIGN_IN_PROGRESS"))
    define("_SIGN_IN_PROGRESS", "En cours de signature");

if (!defined("_DOCUMENTS_LIST_WITH_SIGNATORY"))
    define("_DOCUMENTS_LIST_WITH_SIGNATORY", "Liste des documents avec signataire");

/***** Signature Book *****/
if (!defined('_DEFINE_MAIL'))
    define('_DEFINE_MAIL', 'Courrier');
if (!defined('_PROGRESSION'))
    define('_PROGRESSION', 'Avancement');
if (!defined('_ACCESS_TO_DETAILS'))
    define('_ACCESS_TO_DETAILS', 'Accédez à la fiche détaillée');
if (!defined('_SB_INCOMING_MAIL_ATTACHMENTS'))
    define('_SB_INCOMING_MAIL_ATTACHMENTS', 'pièce(s) complémentaire(s)');
if (!defined('_DOWNLOAD_ATTACHMENT'))
    define('_DOWNLOAD_ATTACHMENT', 'Télécharger la pièce jointe');
if (!defined('_DEFINE_FOR'))
    define('_DEFINE_FOR', 'Pour');
if (!defined('_CHRONO'))
    define('_CHRONO', 'Chrono');
if (!defined('_DRAFT'))
    define('_DRAFT', 'Brouillon');
if (!defined('_UPDATE_ATTACHMENT'))
    define('_UPDATE_ATTACHMENT', 'Modifier la pièce jointe');
if (!defined('_DELETE_ATTACHMENT'))
    define('_DELETE_ATTACHMENT', 'Supprimer la pièce jointe');
if (!defined('_DISPLAY_ATTACHMENTS'))
    define('_DISPLAY_ATTACHMENTS', 'Afficher la liste des pièces jointes');
/***** Signature Book *****/

if (!defined('_DRAG_N_DROP_CHANGE_ORDER'))
    define('_DRAG_N_DROP_CHANGE_ORDER', 'Glisser/déposer pour modifier l\'ordre du circuit');

if (!defined('_NO_USER_SIGNED_DOC'))
    define('_NO_USER_SIGNED_DOC', "vous n'avez PAS signé de pièce jointe !");

if (!defined('_IS_ALL_ATTACHMENT_SIGNED_INFO'))
    define('_IS_ALL_ATTACHMENT_SIGNED_INFO', "Vous ne pourrez pas demander de signature aux utilisateurs, aucune pièce jointe présente dans le parapheur");

if (!defined('_IS_ALL_ATTACHMENT_SIGNED_INFO2'))
    define('_IS_ALL_ATTACHMENT_SIGNED_INFO2', "Vous ne pourrez pas demander de signature aux utilisateurs, toutes les pièces jointes présentes dans le parapheur ont été signées.");

