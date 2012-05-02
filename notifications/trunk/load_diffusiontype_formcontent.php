<?php
/**
* File : load_diffusiontype_formcontent
*
* Script called by an ajax object to process the diffusion type change during
*
* @package  Maarch Entreprise Notifiation Modules
* @version 1.3
* @since 10/2005
* @license GPL v3
* @author Loïc Vinet  <dev@maarch.org>
*/

require_once 'modules' . DIRECTORY_SEPARATOR . 'notifications' . DIRECTORY_SEPARATOR
    . 'class' . DIRECTORY_SEPARATOR . 'diffusion_type_controler.php';

if ((! isset($_REQUEST['id_type']) || empty($_REQUEST['id_type']))) {
        $_SESSION['error'] = _TYPE_EMPTY;
    
    echo "{status : 1, error_txt : '" . addslashes($_SESSION['error']) . "'}";
    exit();
}

if (empty($_REQUEST['origin'])) {
    $_SESSION['error'] = _ORIGIN . ' ' . _UNKNOWN;
    echo "{status : 2, error_txt : '" . addslashes($_SESSION['error']) . "'}";
    exit();
}
//--------------------------------------------------

$db = new dbquery();
$core = new core_tools();
$core->load_lang();
$dType = new diffusion_type_controler();
$diffType = array();
$diffType = $dType->getAllDiffusion();

foreach($diffType as $loadedType) {
	if ($loadedType->id == $_REQUEST['id_type']){
		if ($loadedType->script <> '') {
			include_once($loadedType->script);
			$content = getContent('diffusion_type', 'diffusion_values', 'diffusion_properties');
			echo "{status : 0, div_content : '" . addslashes($content) . "'}";
		} else {
			 echo "{status : 1, error_txt : '" . addslashes($_SESSION['error']) . "'}";
		}
	}
}	
exit();
