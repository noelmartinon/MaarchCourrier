<?php
/**
 * Created by PhpStorm.
 * User: Jules DIEDHIOU
 * Date: 28/11/2018
 * Time: 10:11
 */
$GLOBALS['FILE_SIZE_MAX'] = 5242880;
$GLOBALS['STORAGE_PATH'] = "";
$GLOBALS['ALONE'] = FALSE;

$GLOBALS['MODE'] = "";

$GLOBALS['DOC_SERV_VIEWD'] = array(".ppt",".pps",".odp",".pdf",".djvu",".epub",".xps");
$GLOBALS['DOC_SERV_EDITED'] = array(".docx",".doc",".odt",".xlsx",".xls",".ods",".csv",".pptx",".ppsx",".rtf",".txt",".mht",".html",".htm");
$GLOBALS['DOC_SERV_CONVERT'] = array(".doc",".odt",".xls",".ods",".ppt",".pps",".odp",".rtf",".mht",".html",".htm",".epub");

$GLOBALS['DOC_SERV_TIMEOUT'] = "120000";

/*remplacer localhost et le port par l'adresse ou est installé OnlyOffice*/

$GLOBALS['DOC_SERV_STORAGE_URL'] = "http://localhost:8080/FileUploader.ashx";
$GLOBALS['DOC_SERV_CONVERTER_URL'] = "http://localhost:8080/ConvertService.ashx";
$GLOBALS['DOC_SERV_API_URL'] = "http://localhost:8080/web-apps/apps/api/documents/api.js";
$GLOBALS['DOC_SERV_PRELOADER_URL'] = "http://localhost:8080/web-apps/apps/api/documents/cache-scripts.html";


$GLOBALS['EXAMPLE_URL'] = "";

$GLOBALS['MOBILE_REGEX'] = "android|avantgo|playbook|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino";

$GLOBALS['ExtsSpreadsheet'] = array(".xls", ".xlsx",
                                    ".ods", ".csv");

$GLOBALS['ExtsPresentation'] = array(".pps", ".ppsx",
                                    ".ppt", ".pptx",
                                    ".odp");

$GLOBALS['ExtsDocument'] = array(".docx", ".doc", ".odt", ".rtf", ".txt",
                                ".html", ".htm", ".mht", ".pdf", ".djvu",
                                ".fb2", ".epub", ".xps");

if ( !defined('ServiceConverterMaxTry') )
    define( 'ServiceConverterMaxTry', 3);

?>