<?php

//LANGUAGE
if (!defined('_LANGUAGE')) {
    define('_LANGUAGE', 'Language');
}
if (!defined('_CHOOSE_LANGUAGE')) {
    define('_CHOOSE_LANGUAGE', 'Choose your language');
}

//WELCOME
if (!defined('_WELCOME_INSTALL')) {
    define('_WELCOME_INSTALL', 'Welcome');
}

if (!defined('_DESC_INSTALL')) {
    define('_DESC_INSTALL', "Welcome to Maarch install tool !<br />
                    <br />
                    Maarch is a full free software for Document Management, Content Management, Mail Management, Colaborative and legal archiving/record management capabilities. It allows to follow multichannel administrative information (postals mails, mails, forms, phones calls) and classify the documents to find effectively in time. The software offers supports tools for writing and to validate outgoing mails. Maarch Courrier is distributed under GPL v3 licence : there is no licence cost, but professional services offered by Maarch and its partner global network.<br />
                    <br />
                    Services cover audit, installation, parametrization, customizing, IT integration, training, support and maintenance. Go to <A style='color: #800000; font-family:verdana;' href='http://www.maarch.com' target\"_blank\"> www.maarch.com</A> for more information on Maarch Professional Services.
					<br>
					<br><b>Important : we are looking for english speaking partners to promote Maarch around the world. Don't hesitate to contact us directly at info@maarch.org for a win-win partnership !");
}

//LICENCE

if (!defined('_SMTP_DOMAINS')) {
    define('_SMTP_DOMAINS', 'Domains');
}

if (!defined('_SET_CONFIG')) {
    define('_SET_CONFIG', 'set');
}
if (!defined('_INFO_SMTP_OK')) {
    define('_INFO_SMTP_OK', 'Informations add in stmp files config');
}

if (!defined('_ADD_INFO_SMTP')) {
    define('_ADD_INFO_SMTP', 'Add');
}

if (!defined('_LICENCE')) {
    define('_LICENCE', 'Licence');
}

if (!defined('_SMTP_OK')) {
    define('_SMTP_OK', 'Information: Look your email');
}

if (!defined('_SMTP_ERROR')) {
    define('_SMTP_ERROR', 'Information : incorrect SMTP authentication');
}

//PREREQUISITES
if (!defined('_PREREQUISITES')) {
    define('_PREREQUISITES', 'Prerequisites');
}
if (!defined('_PREREQUISITES_HEAD')) {
    define('_PREREQUISITES_HEAD', 'Pr�-requis');
}

if (!defined('_PREREQUISITES_EXP')) {
    define('_PREREQUISITES_EXP', "Maarch installation needs some prerequisites on PHP. Please report to <A style='color: #800000; font-family:verdana;' href='http://wiki.maarch.org/Maarch_Courrier/latest/fr/Install/Prerequis' target=\"_blank\"> Architecture and requirements</A> for details");
}
if (!defined('_LANG')) {
    define('_LANG', 'Lang');
}
if (!defined('_CONFIG_INSTALL')) {
    define('_CONFIG_INSTALL', 'Configuration');
}
if (!defined('_ACTIVATED')) {
    define('_ACTIVATED', 'Ok');
} if (!defined('_OPTIONNAL')) {
    define('_OPTIONNAL', 'Not Ok but optional');
} if (!defined('_NOT_ACTIVATED')) {
    define('_NOT_ACTIVATED', 'Not Ok');
}
if (!defined('_SMTP_USER_CONNECT')) {
    define('_SMTP_USER_CONNECT', 'User who is connect on the server');
}

if (!defined('_SET_CONFIG_OK')) {
    define('_SET_CONFIG_OK', 'Done');
}

if (!defined('_SET_CONFIG_KO')) {
    define('_SET_CONFIG_KO', 'no set done : there is a bug');
}

if (!defined('_SENDER_TYPE')) {
    define('_SENDER_TYPE', 'smtp: Transfert message; sendmail: mail server service');
}

if (!defined('_SMTP_MAILTO_BOX')) {
    define('_SMTP_MAILTO_BOX', 'MailBox where the test mail will send');
}

if (!defined('_SMTP_INFORMATION')) {
    define('_SMTP_INFORMATION', 'Information: Check your email');
}

if (!defined('_CONFIG_EXP')) {
    define('_CONFIG_EXP', 'Here, you can see the software configuration');
}
if (!defined('_CONFIG_SMTP_EXP')) {
    define('_CONFIG_SMTP_EXP', 'Here, you can see the software configuration for smtp');
}
if (!defined('_CONFIG_INFO')) {
    define('_CONFIG_INFO', 'Configuration File');
}
if (!defined('_APPLICATIONNAME')) {
    define('_APPLICATIONNAME', 'Application Name');
}
if (!defined('_PATH_TO_DOCSERVER')) {
    define('_PATH_TO_DOCSERVER', 'Path to docserver');
}
if (!defined('_USER_BDD')) {
    define('_USER_BDD', 'User of database');
}
if (!defined('_GENERAL')) {
    define('_GENERAL', 'General');
}
if (!defined('_PHP_VERSION')) {
    define('_PHP_VERSION', 'Version of PHP (7.2, 7.3, or 7.4)');
}
if (!defined('_MAARCH_PATH_RIGHTS')) {
    define('_MAARCH_PATH_RIGHTS', 'Rights to Maarch root directory (read/write)');
}
if (!defined('_THE_MAARCH_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS')) {
    define('_THE_MAARCH_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS', 'The Maarch root path does not have the adequate rights');
}
if (!defined('_UNOCONV_INSTALLED')) {
    define('_UNOCONV_INSTALLED', "Unoconv installed");
}
if (!defined('_UNOCONV_NOT_INSTALLED')) {
    define('_UNOCONV_NOT_INSTALLED', "Unoconv not installed");
}
if (!defined('_NETCAT_OR_NMAP_INSTALLED')) {
    define('_NETCAT_OR_NMAP_INSTALLED', "netcat or nmap installed");
}
if (!defined('_NETCAT_OR_NMAP_NOT_INSTALLED')) {
    define('_NETCAT_OR_NMAP_NOT_INSTALLED', "netcat or nmap not installed");
}
if (!defined('_PGSQL')) {
    define('_PGSQL', 'pgsql library');
}
if (!defined('_PDO_PGSQL')) {
    define('_PDO_PGSQL', 'pdo_pgsql library');
}
if (!defined('_GD')) {
    define('_GD', 'gd library');
}
if (!defined('_IMAP')) {
    define('_IMAP', 'imap library');
}
if (!defined('_MBSTRING')) {
    define('_MBSTRING', 'mbstring library');
}
if (!defined('_XSL')) {
    define('_XSL', 'xsl library');
}
if (!defined('_XMLRPC')) {
    define('_XMLRPC', 'XML-RPC library');
}
if (!defined('_FILEINFO')) {
    define('_FILEINFO', 'fileinfo library');
}
if (!defined('_GETTEXT')) {
    define('_GETTEXT', 'gettext library');
}
if (!defined('_IMAGICK')) {
    define('_IMAGICK', 'IMagick library');
}
if (!defined('_PEAR')) {
    define('_PEAR', 'PEAR');
}
if (!defined('_MIMETYPE')) {
    define('_MIMETYPE', 'MIME-TYPE');
}
if (!defined('_ERROR_REPORTING')) {
    define('_ERROR_REPORTING', 'error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT');
}
if (!defined('_DISPLAY_ERRORS')) {
    define('_DISPLAY_ERRORS', 'display_errors=On');
}
if (!defined('_SHORT_OPEN_TAGS')) {
    define('_SHORT_OPEN_TAGS', 'short_open_tags=On');
}

if (!defined('_MUST_FIX')) {
    define('_MUST_FIX', 'You must fix errors before continuing');
}

if (!defined('_CHOOSE_DATASET_TO_IMPORT')) {
    define('_CHOOSE_DATASET_TO_IMPORT', 'Choose dataset to import');
}

//DOCSERVERS
if (!defined('_DOCSERVERS')) {
    define('_DOCSERVERS', 'Docservers');
}
if (!defined('_DOCSERVERS_EXP')) {
    define('_DOCSERVERS_EXP', "Electronic resources are stored on an HD, a disc bay, or a logical storage system like EMC Centera. Type in an existing folder name, on which Apache gets write access. The install will create the sub-folders declared in the dataset. More information on <A style='color: #800000; font-family:verdana;' href='http://wiki.maarch.org/Maarch_Entreprise/fr/Man/Admin/Stockage' target=\"_blank\"> Gestion du stockage(FR)</A>");
}
if (!defined('_DOCSERVER_ROOT')) {
    define('_DOCSERVER_ROOT', 'Root directory');
}
if (!defined('_CREATE_DOCSERVERS')) {
    define('_CREATE_DOCSERVERS', 'Validate');
}
if (!defined('_MUST_CHOOSE_PATH_ROOT')) {
    define('_MUST_CHOOSE_PATH_ROOT', 'You must choose the path root directory');
}
if (!defined('_PATH_UNAPPROACHABLE')) {
    define('_PATH_UNAPPROACHABLE', 'Path is unapproachable');
}
if (!defined('_THE_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS')) {
    define('_THE_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS', 'The path does not have the adequate rights');
}
if (!defined('_CREATES_OR_UPDATES_RIGHT_ON_PATH')) {
    define('_CREATES_OR_UPDATES_RIGHT_ON_PATH', 'Creates or updates right path');
}


//DATABASE
if (!defined('_DATABASE')) {
    define('_DATABASE', 'Database');
}
if (!defined('_DATABASE_DESC')) {
    define('_DATABASE_ADD_INF', 'Maarch installer is going to create a new database. Type in the database name (ex:maarch_db)');
}
if (!defined('_DATABASE_EXP')) {
    define('_DATABASE_EXP', 'Maarch uses as a standard PostgreSQL engine (Version 9 or more). Type in server name, port #, user and password to the database.');
}

if (!defined('_DATABASE_INFOS')) {
    define('_DATABASE_INFOS', 'Connection to database');
}
if (!defined('_DATABASE_CREATE')) {
    define('_DATABASE_CREATE', 'Create database');
}
if (!defined('_DATABASESERVER')) {
    define('_DATABASESERVER', 'Server');
}
if (!defined('_DATABASESERVERPORT')) {
    define('_DATABASESERVERPORT', 'Port');
}
if (!defined('_DATABASEUSER')) {
    define('_DATABASEUSER', 'User');
}
if (!defined('_DATABASEPASSWORD')) {
    define('_DATABASEPASSWORD', 'Password');
}
if (!defined('_DATABASENAME')) {
    define('_DATABASENAME', 'Name');
}
if (!defined('_DATABASETYPE')) {
    define('_DATABASETYPE', 'Type');
}
if (!defined('_DATASET_CHOICE')) {
    define('_DATASET_CHOICE', 'Selection of the sample dataset');
}
if (!defined('_DATASET_EXP')) {
    define('_DATASET_EXP', 'Maarch comes with two sample test datasets : one is quite simple and illustrates basic archiving functionalities(data.sql), and the other one is showing mail management functionalities for public offices (data_mlb.sql). Both include users, document types, and everything to get a global understanding of Maarch.');
}
if (!defined('_DATASET')) {
    define('_DATASET', 'Dataset');
}
if (!defined('_CHOOSE')) {
    define('_CHOOSE', 'Choose one...');
}
if (!defined('_INSTALL_SUCCESS')) {
    define('_INSTALL_SUCCESS', 'Installation ended successfully');
}
if (!defined('_SUBMIT')) {
    define('_SUBMIT', 'Test connection');
}
if (!defined('_BAD_INFORMATIONS_FOR_CONNECTION')) {
    define('_BAD_INFORMATIONS_FOR_CONNECTION', 'Bad informations');
}
if (!defined('_UNABLE_TO_CREATE_DATABASE')) {
    define('_UNABLE_TO_CREATE_DATABASE', 'Unable to create database, try another name or check the script structure.sql');
}
if (!defined('_UNABLE_TO_CREATE_CUSTOM')) {
    define('_UNABLE_TO_CREATE_CUSTOM', 'Unable to create custom, try another name for database or check custom folder (permission problem ?)');
}
if (!defined('_UNABLE_TO_LOAD_DATAS')) {
    define('_UNABLE_TO_LOAD_DATAS', 'Unable to load dataset');
}
if (!defined('_CHOOSE_A_NAME_FOR_DB')) {
    define('_CHOOSE_A_NAME_FOR_DB', 'You must choose a name for the database');
}
if (!defined('_LOAD_DATA')) {
    define('_LOAD_DATA', 'Load dataset');
}
if (!defined('_CREATE_DATABASE')) {
    define('_CREATE_DATABASE', 'Create database');
}
//PASSWORD
if (!defined('_PASSWORD')) {
    define('_PASSWORD', 'Password');
}
if (!defined('_CHOOSE_ADMIN_PASSWORD')) {
    define('_CHOOSE_ADMIN_PASSWORD', 'Choose the admin password');
}
if (!defined('_NEW_ADMIN_PASS')) {
    define('_NEW_ADMIN_PASS', 'Admin password');
}
if (!defined('_NEW_ADMIN_PASS_AGAIN')) {
    define('_NEW_ADMIN_PASS_AGAIN', 'Again');
}
if (!defined('_PASSWORD_EXP')) {
    define('_PASSWORD_EXP', 'Choose the password for "superadmin" ');
}
if (!defined('_PASSWORDS_ARE_DIFFERENTS')) {
    define('_PASSWORDS_ARE_DIFFERENTS', 'The two passwords must match');
}
if (!defined('_FILL_ALL_PASSWORD_FIELDS')) {
    define('_FILL_ALL_PASSWORD_FIELDS', 'You must type the password twice');
}

//RESUME
if (!defined('_RESUME')) {
    define('_RESUME', 'Resume');
}

if (!defined('_START_MEP_1_3')) {
    define('_START_MEP_1_3', 'Start with Maarch');
}

//ERROR
if (!defined('_ERROR')) {
    define('_ERROR', 'Error');
}
if (!defined('_NO_STEP')) {
    define('_NO_STEP', 'No step choosen');
}
if (!defined('_BAD_STEP')) {
    define('_BAD_STEP', "The step doesn't exist");
}
if (!defined('_INSTALL_ISSUE')) {
    define('_INSTALL_ISSUE', 'Installation issue');
}
if (!defined('_TRY_AGAIN')) {
    define('_TRY_AGAIN', 'Please try again');
}

//BUTTON
if (!defined('_PREVIOUS_INSTALL')) {
    define('_PREVIOUS_INSTALL', 'Previous');
}
if (!defined('_NEXT_INSTALL')) {
    define('_NEXT_INSTALL', 'Next');
}

if (!defined('_ONE_FIELD_EMPTY')) {
    define('_ONE_FIELD_EMPTY', 'You must fill all the fields');
}

//SMTP

if (!defined('_SMTP_HOST')) {
    define('_SMTP_HOST', 'Host');
}

if (!defined('_SMTP_TYPE')) {
    define('_SMTP_TYPE', 'Type');
}

if (!defined('_SMTP_PORT')) {
    define('_SMTP_PORT', 'Port');
}

if (!defined('_SMTP_USER')) {
    define('_SMTP_USER', 'Username');
}

if (!defined('_SMTP_PASSWORD')) {
    define('_SMTP_PASSWORD', 'password');
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

if (!defined('_SMTP_MAILFROM')) {
    define('_SMTP_MAILFROM', 'Mail from');
}

if (!defined('_SMTP_MAILTO')) {
    define('_SMTP_MAILTO', 'your mail');
}

if (!defined('_SMTP_EXP')) {
    define('_SMTP_EXP', "Here, you can configure notifications and sendmails's modules.");
}

if (!defined('_VERIF_SMTP')) {
    define('_VERIF_SMTP', 'V&eacute;rification');
}

if (!defined('_SMTP')) {
    define('_SMTP', 'SMTP');
}

if (!defined('_SMTP_INFO')) {
    define('_SMTP_INFO', "SMTP's Configuration");
}

if (!defined('_SMTP_DOMAINS')) {
    define('_SMTP_DOMAINS', 'Domains');
}

if (!defined('_DEPENDENCIES_NOT_DOWNLOADED')) {
    define('_DEPENDENCIES_NOT_DOWNLOADED', 'dependencies not downloaded, retrieve it via wiki documentation');
}

if (!defined('_DEPENDENCIES_NOT_EXTRACTED')) {
    define('_DEPENDENCIES_NOT_EXTRACTED', 'dependencies not extracted, retrieve it via wiki documentation');
}

if (!defined('_CURL')) {
    define('_CURL', 'curl library');
}

if (!defined('_ZIP_LIB')) {
    define('_ZIP_LIB', 'zip library');
}

if (!defined('_NO_AVAILABLE_TAG_TO_UPDATE')) {
    define('_NO_AVAILABLE_TAG_TO_UPDATE', 'No available tag to update');
}

if (!defined('_CAN_NOT_COPY_TO')) {
    define('_CAN_NOT_COPY_TO', 'Can not copy to');
}

if (!defined('_CHECK_RIGHT_SOURCE_FOLDER')) {
    define('_CHECK_RIGHT_SOURCE_FOLDER', 'One folder or file does not have enought rights.');
}

if (!defined('_MISSING_PREREQUISITE_UPDATE')) {
    define('_MISSING_PREREQUISITE_UPDATE', 'Prerequisite is missing to update :');
}

// CONFIG LOGIN IMAGE
if (!defined('_CONFIG_IMAGE')) {
    define('_CONFIG_IMAGE', 'Picture configuration');
}
if (!defined('_CONFIG_IMG_EXP')) {
    define('_CONFIG_IMG_EXP', "Allows you to update the picture login page, Choose a resolution equal or bigger than 1920*1080 and a JPG format");
}
if (!defined('_LOGIN_PICTURE')) {
    define('_LOGIN_PICTURE', "Choose an picture from your disk");
}
if (!defined('_UPLOAD_PICTURE')) {
    define('_UPLOAD_PICTURE', "Upload picture");
}
if (!defined('_LOGIN_PICTURE_FROM_DATA')) {
    define('_LOGIN_PICTURE_FROM_DATA', "Or from our storage (Pixabay)");
}

if (!defined('_CUSTOM_LIST')) {
    define('_CUSTOM_LIST', "Custom list already installed on this server");
}
