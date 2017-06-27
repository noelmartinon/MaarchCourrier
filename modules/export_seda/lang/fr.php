<?php

if (!defined("_EXPORT_SEDA_COMMENT"))
    define("_EXPORT_SEDA_COMMENT", "Export SEDA");

if (!defined("_EXPORT_SEDA"))
    define("_EXPORT_SEDA","Transferer vos courriers");
if (!defined("_CHECK_ACKNOWLEDGEMENT"))
    define("_CHECK_ACKNOWLEDGEMENT","Vérification de l'accusé de reception");
if (!defined("_CHECK_REPLY"))
    define("_CHECK_REPLY","Vérification de la réponse au transfert");
if (!defined("_PURGE_LETTER"))
    define("_PURGE_LETTER","Purger le courrier apres l'archivage");
if (!defined("_RESET_LETTER"))
    define("_RESET_LETTER","Remise à zéro du circuit de traitement");


if (!defined("_EXPORT_SEDA_VIEW"))
    define("_EXPORT_SEDA_VIEW", "Voir le bordereau SEDA");

if (!defined("_INFORMATION_MESSAGE"))
    define("_INFORMATION_MESSAGE", "Information bordereau");

if (!defined("_MESSAGE_IDENTIFIER"))
    define("_MESSAGE_IDENTIFIER", "Identifiant bordereau");
if (!defined("_ARCHIVAL_AGENCY_SIREN"))
    define("_ARCHIVAL_AGENCY_SIREN", "Numéro SIREN service d'archive");
if (!defined("_TRANSFERRING_AGENCY_SIREN"))
    define("_TRANSFERRING_AGENCY_SIREN", "Numéro SIREN service de transfert");

if (!defined("_INFORMATION_ARCHIVE"))
    define("_INFORMATION_ARCHIVE", "Information archive");
if (!defined("_ARCHIVE_IDENTIFIER"))
    define("_ARCHIVE_IDENTIFIER", "Identifiant archive");

if (!defined("_DESCRIPTION_LEVEL"))
    define("_DESCRIPTION_LEVEL", "Service de description");
if (!defined("_ITEM"))
    define("_ITEM", "Objet");
if (!defined("_RECEIVED_DATE"))
    define("_RECEIVED_DATE", "Date de reception");
if (!defined("_YEARS"))
    define("_YEARS", "an(s)");
if (!defined("_MONTHS"))
    define("_MONTHS", "mois");
if (!defined("_DAYS"))
    define("_DAYS", "jour(s)");
if (!defined("_APPRAISAL_RULE"))
    define("_APPRAISAL_RULE", "Règle de conservation");
if (!defined("_APPRAISAL_FINAL_DISPOSITION"))
    define("_APPRAISAL_FINAL_DISPOSITION", "Sort final");
if (!defined("_DESTROY"))
    define("_DESTROY", "Destruction");
if (!defined("_KEEP"))
    define("_KEEP", "Conservation");
if (!defined("_DOCUMENT_TYPE"))
    define("_DOCUMENT_TYPE", "Type de document");
if (!defined("_REPLY"))
    define("_REPLY", "Réponse");
if (!defined("_ATTACHMENT"))
    define("_ATTACHMENT", "Pièce jointe");
if (!defined("_SENT_DATE"))
    define("_SENT_DATE", "Date d'envoi");

if (!defined("_INFORMATION_ARCHIVE_CHILDREN"))
    define("_INFORMATION_ARCHIVE_CHILDREN", "Information archive enfant");

if (!defined("_ZIP"))
    define("_ZIP", "Télécharger Zip");
if (!defined("_SEND_MESSAGE"))
    define("_SEND_MESSAGE", "Transferer bordereau");
if (!defined("_VALIDATE"))
    define("_VALIDATE", "Valider");
if (!defined("_URLSAE"))
    define("_URLSAE", "> Système d'archivage <");

if (!defined("_RECEIVED_MESSAGE"))
    define("_RECEIVED_MESSAGE", "Conformité du bordereau confirmée par accusé de réception : ");

if (!defined("_ERROR_MESSAGE"))
    define("_ERROR_MESSAGE", "Bordereau non-reçu");

if (!defined("_TRANSFERRING_AGENCY_SIREN_COMPULSORY"))
    define("_TRANSFERRING_AGENCY_SIREN_COMPULSORY", "Numéro SIREN service versant obligatoire");

if (!defined("_ARCHIVAL_AGENCY_SIREN_COMPULSORY"))
    define("_ARCHIVAL_AGENCY_SIREN_COMPULSORY", "Numéro SIREN service d'archive obligatoire");

if (!defined("_VALIDATE_MANUAL_DELIVERY"))
    define("_VALIDATE_MANUAL_DELIVERY", "Valider l'envoi manuel du bordereau");

if (!defined("_ERROR_MESSAGE_ALREADY_SENT"))
    define("_ERROR_MESSAGE_ALREADY_SENT", "L'archivage d'un courrier sélectionné est déjà en cours, vous ne pouvez pas archiver deux fois le même courrier. Veuillez le désélectionner pour continuer. Numéro de courrier en cours d'archivage : ");

if (!defined("_ERROR_STATUS_SEDA"))
    define("_ERROR_STATUS_SEDA", "Le courrier selectionné n'est pas en cours d'archivage. Veuillez le désélectionner ou le transferer au système d'archivage. Numéro du courrier : ");

if (!defined("_ERROR_NO_ACKNOWLEDGEMENT"))
    define("_ERROR_NO_ACKNOWLEDGEMENT", "Aucun accusé de reception n'est referencé pour le courrier suivant : ");

if (!defined("_ERROR_NO_XML_ACKNOWLEDGEMENT"))
    define("_ERROR_NO_XML_ACKNOWLEDGEMENT", "L'accusé de reception n'est pas bien structuré. Numéro du courrier : ");

if (!defined("_ERROR_NO_REFERENCE_MESSAGE_ACKNOWLEDGEMENT"))
    define("_ERROR_NO_REFERENCE_MESSAGE_ACKNOWLEDGEMENT", "Aucun bordereau correspond à l'accusé de reception. Numéro du courrier : ");

if (!defined("_ERROR_WRONG_ACKNOWLEDGEMENT"))
    define("_ERROR_WRONG_ACKNOWLEDGEMENT", "L'accusé de reception n'est pas lié au bon courrier. Numéro du courrier : ");

if (!defined("_ERROR_NO_REPLY"))
    define("_ERROR_NO_REPLY", "Aucune réponse de transfert n'est referencé pour le courrier suivant : ");

if (!defined("_ERROR_NO_XML_REPLY"))
    define("_ERROR_NO_XML_REPLY", "La réponse de transfert n'est pas bien structuré. Numéro du courrier : ");

if (!defined("_ERROR_NO_REFERENCE_MESSAGE_REPLY"))
    define("_ERROR_NO_REFERENCE_MESSAGE_REPLY", "Aucun bordereau correspond à la réponse de transfert. Numéro du courrier : ");

if (!defined("_ERROR_WRONG_REPLY"))
    define("_ERROR_WRONG_REPLY", "La réponse de transfert n'est pas lié au bon courrier. Numéro du courrier : ");

if (!defined("_LETTER_NO_ARCHIVED"))
    define("_LETTER_NO_ARCHIVED", "Le courrier n'a pas été archivé. Veuillez regarder la réponse au transfert pour en connaitre la cause. Numéro du courrier : ");

if (!defined("_ERROR_LETTER_ARCHIVED"))
    define("_ERROR_LETTER_ARCHIVED", "Vous ne pouvez pas remettre à zéro un courrier archivé. Numéro du courrier : ");
