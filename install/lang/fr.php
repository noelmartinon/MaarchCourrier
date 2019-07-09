<?php

//LANGUAGE
if (!defined('_LANGUAGE')) {
    define('_LANGUAGE', 'Langue');
}
if (!defined('_CHOOSE_LANGUAGE')) {
    define('_CHOOSE_LANGUAGE', 'Choisissez votre langue');
}

//WELCOME
if (!defined('_WELCOME_INSTALL')) {
    define('_WELCOME_INSTALL', 'Bienvenue');
}

if (!defined('_DESC_INSTALL')) {
    define('_DESC_INSTALL', "Bienvenue dans l'assistant d'installation de Maarch Courrier ! <br />
                    <br />
                    Cet assistant va vous guider pas à pas dans l'installation de Maarch Courrier notamment :<br />
                    - Contrôle des pré-requis<br />
                    - Création de la base de données<br />
                    - Import d'un jeu de données<br />
                    - Création des docservers<br />
                    - Paramétrage des batchs
                    <br />
                    <br />Pour tout renseignements, rendez vous sur <A style='color: #800000; font-family:verdana;' href='https://community.maarch.org/' target=\"_blank\"> community.maarch.org</A> ou sur <A style='color: #800000; font-family:verdana;' href='http://www.maarch.com' target=\"_blank\"> www.maarch.com</A>  ");
}

//LICENCE
if (!defined('_SET_CONFIG')) {
    define('_SET_CONFIG', 'modifier');
}

if (!defined('_INFO_SMTP_OK')) {
    define('_INFO_SMTP_OK', 'Informations renseignées dans les fichiers de configuration');
}

if (!defined('_ADD_INFO_SMTP')) {
    define('_ADD_INFO_SMTP', 'Ajouter');
}

if (!defined('_LICENCE')) {
    define('_LICENCE', 'Licence');
}

if (!defined('_SMTP_OK')) {
    define('_SMTP_OK', 'Information: Consultez votre messagerie');
}

if (!defined('_SMTP_ERROR')) {
    define('_SMTP_ERROR', 'Information : Authentication SMTP incorrect');
}

if (!defined('_SET_CONFIG_OK')) {
    define('_SET_CONFIG_OK', 'Modification faite');
}

if (!defined('_SET_CONFIG_KO')) {
    define('_SET_CONFIG_KO', 'Aucune modification réalisée : un problème est survenu');
}

//PREREQUISITES
if (!defined('_PREREQUISITES')) {
    define('_PREREQUISITES', 'Pré-requis');
}
if (!defined('_PREREQUISITES_HEAD')) {
    define('_PREREQUISITES_HEAD', 'Pré-requis');
}

if (!defined('_PREREQUISITES_EXP')) {
    define('_PREREQUISITES_EXP', "L'installation de Maarch Courrier nécessite un certain nombre de pré-requis au niveau de l'installation de PHP. Reportez-vous à la page <A style='color: #800000; font-family:verdana;' href='http://wiki.maarch.org/Maarch_Courrier/latest/fr/Install/Prerequis' target=\"_blank\"> pre-requis</A> pour les détails");
}
if (!defined('_ACTIVATED')) {
    define('_ACTIVATED', 'Conforme');
} if (!defined('_OPTIONNAL')) {
    define('_OPTIONNAL', 'Non conforme mais optionnel');
} if (!defined('_NOT_ACTIVATED')) {
    define('_NOT_ACTIVATED', 'Non conforme');
}

if (!defined('_GENERAL')) {
    define('_GENERAL', 'Général');
}
if (!defined('_PHP_VERSION')) {
    define('_PHP_VERSION', 'Version de PHP (7.0 et 7.1)');
}
if (!defined('_MAARCH_PATH_RIGHTS')) {
    define('_MAARCH_PATH_RIGHTS', "Droits de lecture et d'écriture du répertoire racine de Maarch Courrier");
}
if (!defined('_THE_MAARCH_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS')) {
    define('_THE_MAARCH_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS', 'Les droits du répertoire racine de Maarch Courrier ne sont pas corrects');
}
if (!defined('_UNOCONV_INSTALLED')) {
    define('_UNOCONV_INSTALLED', "Outils de conversion de documents bureautiques soffice/unoconv installés");
}
if (!defined('_UNOCONV_NOT_INSTALLED')) {
    define('_UNOCONV_NOT_INSTALLED', "Outils de conversion de documents bureautiques soffice/unoconv non installés");
}
if (!defined('_PGSQL')) {
    define('_PGSQL', 'Librairie pgsql');
}
if (!defined('_GD')) {
    define('_GD', 'Librairie gd');
}
if (!defined('_IMAP')) {
    define('_IMAP', 'librairie imap');
}
if (!defined('_MBSTRING')) {
    define('_MBSTRING', 'librairie mbstring');
}
if (!defined('_XSL')) {
    define('_XSL', 'librairie xsl');
}
if (!defined('_XMLRPC')) {
    define('_XMLRPC', 'librairie XML-RPC');
}
if (!defined('_FILEINFO')) {
    define('_FILEINFO', 'librairie fileinfo');
}
if (!defined('_GETTEXT')) {
    define('_GETTEXT', 'librairie gettext');
}
if (!defined('_IMAGICK')) {
    define('_IMAGICK', 'librairie IMagick');
}
if (!defined('_PEAR')) {
    define('_PEAR', 'PEAR');
}
if (!defined('_MIMETYPE')) {
    define('_MIMETYPE', 'MIME-TYPE');
}
if (!defined('_CLITOOLS')) {
    define('_CLITOOLS', 'CLITools');
}
if (!defined('_ERROR_REPORTING')) {
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        define('_ERROR_REPORTING', 'error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT');
    } else {
        define('_ERROR_REPORTING', 'error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED');
    }
}
if (!defined('_DISPLAY_ERRORS')) {
    define('_DISPLAY_ERRORS', 'display_errors = On');
}
if (!defined('_SHORT_OPEN_TAGS')) {
    define('_SHORT_OPEN_TAGS', 'short_open_tags = On');
}

if (!defined('_MUST_FIX')) {
    define('_MUST_FIX', 'Vous devez corriger les erreurs avant de continuer');
}

if (!defined('_CHOOSE_DATASET_TO_IMPORT')) {
    define('_CHOOSE_DATASET_TO_IMPORT', 'Choisissez un jeu de données à importer');
}

//DOCSERVERS
if (!defined('_DOCSERVERS')) {
    define('_DOCSERVERS', 'Zones de stockage');
}
if (!defined('_DOCSERVERS_EXP')) {
    define('_DOCSERVERS_EXP', "Les documents sont stockés à part dans des zones de stockage sur un disque, une baie de disque, ou un système de stockage logique tel que le Centera d'EMC. Entrez un nom de répertoire existant, sur lequel le serveur Apache doit avoir des droits d'écriture. L'installeur va créer les sous-répertoires déclarés dans le jeu de test. Plus d'information sur <A style='color: #800000; font-family:verdana;' href='http://wiki.maarch.org/Maarch_Entreprise/fr/Man/Admin/Stockage' target=\"_blank\"> Gestion du stockage</A>.");
}
if (!defined('_DOCSERVERS_EXP_2')) {
    define('_DOCSERVERS_EXP_2', 'Le dossier racine sera automatiquement suivi du nom de la base de données sélectionnée préalablement.');
}
if (!defined('_DOCSERVER_ROOT')) {
    define('_DOCSERVER_ROOT', 'Dossier racine');
}
if (!defined('_CREATE_DOCSERVERS')) {
    define('_CREATE_DOCSERVERS', 'Enregistrer');
}
if (!defined('_MUST_CHOOSE_PATH_ROOT')) {
    define('_MUST_CHOOSE_PATH_ROOT', 'Vous devez choisir le dossier racine');
}
if (!defined('_PATH_UNAPPROACHABLE')) {
    define('_PATH_UNAPPROACHABLE', "Le dossier n'est pas accessible");
}
if (!defined('_THE_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS')) {
    define('_THE_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS', "Le dossier n'a pas les droits appropriés");
}
if (!defined('_CREATES_OR_UPDATES_RIGHT_ON_PATH')) {
    define('_CREATES_OR_UPDATES_RIGHT_ON_PATH', 'Créer le répertoire ou y positionner les bons droits');
}

//DATABASE
if (!defined('_DATABASE')) {
    define('_DATABASE', 'Base de données');
}
if (!defined('_DATABASE_DESC')) {
    define('_DATABASE_ADD_INF', "L'installeur Maarch Courrier va créer une nouvelle base de données pour vous. Entrez le nom de la base (ex:maarch_db)");
}
if (!defined('_DATABASE_EXP')) {
    define('_DATABASE_EXP', "Maarch Courrier utilise en standard le moteur de base de données libre PostgreSQL (Version 9 ou supérieure). Précisez le nom du serveur, le n° de port, l'utilisateur et le mot de passe de connexion.");
}

if (!defined('_DATABASE_INFOS')) {
    define('_DATABASE_INFOS', 'Informations de connexion à la base de données');
}
if (!defined('_DATABASE_CREATE')) {
    define('_DATABASE_CREATE', 'Création de la base de données');
}
if (!defined('_DATABASESERVER')) {
    define('_DATABASESERVER', 'Serveur');
}
if (!defined('_DATABASESERVERPORT')) {
    define('_DATABASESERVERPORT', 'Port');
}
if (!defined('_DATABASEUSER')) {
    define('_DATABASEUSER', 'Utilisateur');
}
if (!defined('_DATABASEPASSWORD')) {
    define('_DATABASEPASSWORD', 'Mot de passe');
}
if (!defined('_DATABASENAME')) {
    define('_DATABASENAME', 'Nom');
}
if (!defined('_DATABASETYPE')) {
    define('_DATABASETYPE', 'Type');
}
if (!defined('_LANG')) {
    define('_LANG', 'Langue');
}
if (!defined('_CONFIG_INSTALL')) {
    define('_CONFIG_INSTALL', 'Configuration');
}
if (!defined('_APPLICATIONNAME')) {
    define('_APPLICATIONNAME', "Nom de l'application");
}
if (!defined('_CONFIG_EXP')) {
    define('_CONFIG_EXP', "Ici, vous pouvez visualiser les informations qui ont été renseignées dans le fichier de configuration de l'application");
}
if (!defined('_CONFIG_SMTP_EXP')) {
    define('_CONFIG_SMTP_EXP', 'Ici, vous pouvez visualiser les informations qui ont été renseignées dans le fichier de configuration pour le smtp');
}
if (!defined('_CONFIG_INFO')) {
    define('_CONFIG_INFO', 'Fichier de configuration');
}
if (!defined('_PATH_TO_DOCSERVER')) {
    define('_PATH_TO_DOCSERVER', 'Chemin du docservers');
}
if (!defined('_USER_BDD')) {
    define('_USER_BDD', 'Utilisateur de la base de données');
}
if (!defined('_DATASET_CHOICE')) {
    define('_DATASET_CHOICE', "Choix du jeu de données d'exemple à importer");
}
if (!defined('_DATASET_EXP')) {
    define('_DATASET_EXP', "Le jeu de données illustre un circuit courrier en collectivité. Il comprend des utilisateurs, des types de documents, et l'ensemble du référentiel pour une compréhension globale de l'outil.");
}
if (!defined('_DATASET')) {
    define('_DATASET', 'Jeu de données');
}
if (!defined('_CHOOSE')) {
    define('_CHOOSE', 'Choisissez...');
}
if (!defined('_INSTALL_SUCCESS')) {
    define('_INSTALL_SUCCESS', 'Installation terminée avec succès');
}
if (!defined('_SUBMIT')) {
    define('_SUBMIT', 'Tester la connexion');
}
if (!defined('_BAD_INFORMATIONS_FOR_CONNECTION')) {
    define('_BAD_INFORMATIONS_FOR_CONNECTION', 'Les informations de connexion sont invalides');
}
if (!defined('_UNABLE_TO_CREATE_DATABASE')) {
    define('_UNABLE_TO_CREATE_DATABASE', 'Impossible de créer la base de données, essayer un autre nom ou vérifier le script structure.sql');
}
if (!defined('_UNABLE_TO_CREATE_CUSTOM')) {
    define('_UNABLE_TO_CREATE_CUSTOM', 'Impossible de créer le custom, essayer un autre nom de base de données ou vérifier le répertoire custom (problème de permission ?)');
}

if (!defined('_UNABLE_TO_LOAD_DATAS')) {
    define('_UNABLE_TO_LOAD_DATAS', "Impossible d'importer les datas");
}
if (!defined('_CHOOSE_A_NAME_FOR_DB')) {
    define('_CHOOSE_A_NAME_FOR_DB', 'Vous devez choisir un nom pour la base de données');
}
if (!defined('_LOAD_DATA')) {
    define('_LOAD_DATA', 'Charger les données');
}
if (!defined('_CREATE_DATABASE')) {
    define('_CREATE_DATABASE', 'Créer la base');
}
//PASSWORD
if (!defined('_PASSWORD')) {
    define('_PASSWORD', 'Mot de passe');
}
if (!defined('_CHOOSE_ADMIN_PASSWORD')) {
    define('_CHOOSE_ADMIN_PASSWORD', 'Choisissez le mot de passe administrateur');
}
if (!defined('_NEW_ADMIN_PASS')) {
    define('_NEW_ADMIN_PASS', 'Mot de passe administrateur');
}
if (!defined('_NEW_ADMIN_PASS_AGAIN')) {
    define('_NEW_ADMIN_PASS_AGAIN', 'Retapez le mot de passe');
}
if (!defined('_PASSWORD_EXP')) {
    define('_PASSWORD_EXP', 'Le compte "superadmin" est le compte administrateur. Choisissez ici le mot de passe pour ce compte');
}
if (!defined('_PASSWORDS_ARE_DIFFERENTS')) {
    define('_PASSWORDS_ARE_DIFFERENTS', 'Les deux mots de passe doivent être identiques');
}
if (!defined('_FILL_ALL_PASSWORD_FIELDS')) {
    define('_FILL_ALL_PASSWORD_FIELDS', 'Vous devez taper deux fois le mot de passe');
}

//RESUME
if (!defined('_RESUME')) {
    define('_RESUME', 'Résumé');
}

if (!defined('_START_MEP_1_3')) {
    define('_START_MEP_1_3', 'Démarrer avec Maarch Courrier');
}

//ERROR
if (!defined('_ERROR')) {
    define('_ERROR', 'Erreur');
}
if (!defined('_NO_STEP')) {
    define('_NO_STEP', 'Aucune étape choisie');
}
if (!defined('_BAD_STEP')) {
    define('_BAD_STEP', "L'étape choisie n'existe pas");
}
if (!defined('_INSTALL_ISSUE')) {
    define('_INSTALL_ISSUE', "Problème lors de l'installation");
}
if (!defined('_TRY_AGAIN')) {
    define('_TRY_AGAIN', 'Veuillez réessayer');
}

//BUTTON
if (!defined('_PREVIOUS_INSTALL')) {
    define('_PREVIOUS_INSTALL', 'Précédent');
}
if (!defined('_NEXT_INSTALL')) {
    define('_NEXT_INSTALL', 'Suivant');
}

if (!defined('_ONE_FIELD_EMPTY')) {
    define('_ONE_FIELD_EMPTY', 'Au moins un champ est vide');
}

//SMTP

if (!defined('_SMTP_HOST')) {
    define('_SMTP_HOST', 'Hôte');
}

if (!defined('_SMTP_TYPE')) {
    define('_SMTP_TYPE', 'Type');
}

if (!defined('_SMTP_PORT')) {
    define('_SMTP_PORT', 'Port');
}

if (!defined('_SMTP_USER')) {
    define('_SMTP_USER', "nom d'utilisateur");
}

if (!defined('_SMTP_PASSWORD')) {
    define('_SMTP_PASSWORD', 'mot de passe');
}

if (!defined('_SMTP_AUTH')) {
    define('_SMTP_AUTH', 'Authentification SMTP');
}

if (!defined('_SMTP_CHARSET')) {
    define('_SMTP_CHARSET', 'Charset');
}

if (!defined('_SMTP_SECURE')) {
    define('_SMTP_SECURE', 'Secure');
}

if (!defined('_SMTP_SECURE')) {
    define('_SMTP_SECURE', 'Secure');
}

if (!defined('_SMTP_MAILFROM')) {
    define('_SMTP_MAILFROM', 'Mail de');
}

if (!defined('_SMTP_MAILTO')) {
    define('_SMTP_MAILTO', 'Votre adresse courriel');
}

if (!defined('_SMTP_USER_CONNECT')) {
    define('_SMTP_USER_CONNECT', 'Utilisateur se connectant au serveur');
}
if (!defined('_SENDER_TYPE')) {
    define('_SENDER_TYPE', 'smtp: Transfert de message; sendmail: serveur de messagerie électronique');
}

if (!defined('_SMTP_MAILTO_BOX')) {
    define('_SMTP_MAILTO_BOX', 'Courriel où sera envoyé le mail de test');
}

if (!defined('_SMTP_INFORMATION')) {
    define('_SMTP_INFORMATION', 'Information: Consultez votre messagerie');
}

if (!defined('_SMTP_EXP')) {
    define('_SMTP_EXP', 'Ici, vous pouvez remplir ce formulaire afin de configurer les modules de notifications et de sendmails.');
}

if (!defined('_VERIF_SMTP')) {
    define('_VERIF_SMTP', 'Vérification');
}

if (!defined('_SMTP')) {
    define('_SMTP', 'SMTP');
}

if (!defined('_SMTP_INFO')) {
    define('_SMTP_INFO', 'Configuration du SMTP');
}

if (!defined('_SMTP_DOMAINS')) {
    define('_SMTP_DOMAINS', 'Domaines');
}

if (!defined('_DEPENDENCIES_NOT_DOWNLOADED')) {
    define('_DEPENDENCIES_NOT_DOWNLOADED', 'dépendances non téléchargées, récupérez les via la procédure wiki');
}

if (!defined('_DEPENDENCIES_NOT_EXTRACTED')) {
    define('_DEPENDENCIES_NOT_EXTRACTED', 'dépendances non extraites, récupérez les via la procédure wiki');
}

if (!defined('_CURL')) {
    define('_CURL', 'librairie curl');
}

if (!defined('_ZIP_LIB')) {
    define('_ZIP_LIB', 'librairie zip');
}

if (!defined('_NO_AVAILABLE_TAG_TO_UPDATE')) {
    define('_NO_AVAILABLE_TAG_TO_UPDATE', 'Pas de tag disponible pour une mise à jour');
}

if (!defined('_CAN_NOT_COPY_TO')) {
    define('_CAN_NOT_COPY_TO', 'Impossible de copier le dossier vers');
}

if (!defined('_CHECK_RIGHT_SOURCE_FOLDER')) {
    define('_CHECK_RIGHT_SOURCE_FOLDER', "Un dossier ou fichier n'a pas les droits suffisants.");
}

if (!defined('_MISSING_PREREQUISITE_UPDATE')) {
    define('_MISSING_PREREQUISITE_UPDATE', 'Il manque un pré-requis pour réaliser la mise à jour :');
}

// CONFIG LOGIN IMAGE
if (!defined('_CONFIG_IMAGE')) {
    define('_CONFIG_IMAGE', 'Configuration image');
}
if (!defined('_CONFIG_IMG_EXP')) {
    define('_CONFIG_IMG_EXP', "Permet de modifier l'image de fond de la page de connexion, Choisissez une image de taille minimum de 1920*1080 et au format JPG");
}
if (!defined('_LOGIN_PICTURE')) {
    define('_LOGIN_PICTURE', "Choisir une image depuis votre disque");
}
if (!defined('_UPLOAD_PICTURE')) {
    define('_UPLOAD_PICTURE', "Charger l'image");
}
if (!defined('_LOGIN_PICTURE_FROM_DATA')) {
    define('_LOGIN_PICTURE_FROM_DATA', "Ou depuis notre banque d'images (Pixabay)");
}

if (!defined('_CUSTOM_LIST')) {
    define('_CUSTOM_LIST', "Liste des instances MaarchCourrier déjà installées sur ce serveur");
}

if (!defined('_MUST_CHOOSE_VERSION')) {
    define('_MUST_CHOOSE_VERSION', "Vous devez choisir une version");
}
