<?php

require_once 'apps/maarch_entreprise/services/Table.php';

class thumbnails
{
	/*function __construct()
	{
		parent::__construct();
	}*/

	public function build_modules_tables()
	{
		if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . "modules"
            . DIRECTORY_SEPARATOR . "thumbnails" . DIRECTORY_SEPARATOR . "xml"
            . DIRECTORY_SEPARATOR . "config.xml"
        )
        ) {
            $configPath = $_SESSION['config']['corepath'] . 'custom'
                        . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                        . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR
                        . "thumbnails" . DIRECTORY_SEPARATOR . "xml"
                        . DIRECTORY_SEPARATOR . "config.xml";
        } else {
            $configPath = "modules" . DIRECTORY_SEPARATOR . "thumbnails"
                        . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR
                        . "config.xml";
        }
		
		$xmlconfig = simplexml_load_file($configPath);
		$conf = $xmlconfig->CONFIG;
		
	}

	private function r_mkdir_tnl($path, $mode = 0777, $recursive = true) {
	if(empty($path))
		return false;
	 
	if($recursive) {
		$toDo = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));
		if($toDo !== '.' && $toDo !== '..')
			$this->r_mkdir_tnl($toDo, $mode);
	}
	 
	if(!is_dir($path))
		mkdir($path, $mode);
	 
		return true;
}
	public function generateTnl($res_id, $coll_id, $table_name = false){
		$db = new Database();
		$query = "select priority_number, docserver_id from docservers where is_readonly = 'N' and "
	   . " enabled = 'Y' and coll_id = ? and docserver_type_id = 'TNL' order by priority_number";
		$stmt1 = $db->query($query, array($coll_id));

		if($res = $stmt1->fetchObject()){
			$docserverId = $res->docserver_id;
		}else{
			$docserverId='';
		}

		$docServers = "select docserver_id, path_template, device_label from docservers";
		
		$stmt1 = $db->query($docServers,array());
		while ($queryResult = $stmt1->fetchObject()) {
		  $pathToDocServer[$queryResult->docserver_id] = $queryResult->path_template;
		}
		$pathOutput = $pathToDocServer[(string)$docserverId];
		if (!$table_name) $table_name = 'res_letterbox';
		$queryMakeThumbnails = "select docserver_id, path, filename, format from ". $table_name . " where res_id = ? ";
		$stmt1 = $db->query($queryMakeThumbnails, array($res_id));

		while ($queryResult=$stmt1->fetchObject()) {
			$fileFormat = $queryResult->format;
			$path= $queryResult->path;
			$filename= $queryResult->filename;

			$pathToFile = $pathToDocServer[$queryResult->docserver_id] 
			. str_replace("#", DIRECTORY_SEPARATOR, $path)
        	. $queryResult->filename;
			$outputPathFile  = $pathOutput . str_replace("#", DIRECTORY_SEPARATOR, $path) 
			. str_replace(pathinfo($pathToFile, PATHINFO_EXTENSION), "png",$filename);
		}

		if (
       		strtoupper($fileFormat) <> 'PDF' 
       		&& strtoupper($fileFormat) <> 'HTML'
       		&& strtoupper($fileFormat) <> 'MAARCH'
       	) {
			$stmt2 = $db->query("UPDATE ".$table_name." SET tnl_path = 'ERR', tnl_filename = 'ERR' WHERE res_id = ?", array($res_id));

       	} else {
			$racineOut = $pathOutput . str_replace("#", DIRECTORY_SEPARATOR, $path);
			if (!is_dir($racineOut)){
				$this->r_mkdir_tnl($racineOut,0777);
			}
			
			$command = '';

			if (strtoupper($fileFormat) == 'PDF') {
				$command = "convert -density 100x100 -quality 65 -alpha remove " . escapeshellarg($pathToFile) . " ". escapeshellarg($outputPathFile);
			} else {
				$posPoint = strpos($pathToFile, '.');
				$extension = substr($pathToFile, $posPoint);
				$chemin = substr($pathToFile, 0, $posPoint);
				if($extension == '.maarch'){
					if (!copy($pathToFile, $chemin.'.html')) {
					    echo "La copie $pathToFile du fichier a échoué...\n";
					}else{
						$cheminComplet = $chemin.".html";
						$command = "wkhtmltoimage --width 400 --height 600 --quality 100 --zoom 0.2 " . escapeshellarg($cheminComplet) . " "
						. escapeshellarg($outputPathFile);

					}
				}else{
					$command = "wkhtmltoimage --width 400 --height 600 --quality 100 --zoom 0.2 " . escapeshellarg($pathToFile) . " "
					. escapeshellarg($outputPathFile);
				}
			}
			echo $command;
			exec($command.' 2>&1', $output, $result);
			if($result > 0)
			{
			   echo 'document not converted ! ('.$output[0].')';
			}else{
				if (is_file($outputPathFile)){
					$stmt2 = $db->query("UPDATE ".$table_name." SET tnl_path = ?, tnl_filename = ? WHERE res_id = ?", array($path, str_replace(pathinfo($pathToFile, PATHINFO_EXTENSION), "png",$filename), $res_id));	
				}
				else if (is_file(pathinfo($outputPathFile,PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($outputPathFile,PATHINFO_FILENAME).'-0.png')){
					$newFilename =  pathinfo($outputPathFile,PATHINFO_FILENAME).'-0.png';
					$stmt2 = $db->query("UPDATE ".$table_name." SET tnl_path = ?, tnl_filename = ? WHERE res_id = ?", array($path, $newFilename, $res_id));
				}
				return $this->getPathTnl($res_id, $coll_id, $table_name);
			}
		}
	}
	
	public function getPathTnl($res_id, $coll_id, $table_name = false){
		if (empty($res_id)) return '';
		require_once("core" . DIRECTORY_SEPARATOR . "class" . DIRECTORY_SEPARATOR 
		. "class_security.php");
		require_once 'core/class/docservers_controler.php';
		$docserversControler = new docservers_controler();
		$sec = new security();
	
		$table = "";
		if (isset($coll_id) 
			&& !empty($coll_id)
		) {
		   $table = $sec->retrieve_table_from_coll(
				$coll_id
			);
		} else {
			$table = $_SESSION['collections'][0]['table'];
		}
		if ($table_name) $table = $table_name;
		$db = new Database();
		
		$query = "select priority_number, docserver_id from "
			   . _DOCSERVERS_TABLE_NAME . " where is_readonly = 'N' and "
			   . " enabled = 'Y' and coll_id = ? and docserver_type_id = 'TNL' order by priority_number";
			   
		$stmt = $db->query($query, array($coll_id));
		$docserverId = $stmt->fetchObject()->docserver_id;
				
		$docserver = $docserversControler->get($docserverId);
		
		
		$query = "select category_id from mlb_coll_ext"
			   . " where res_id = ?";
			   
		$stmt = $db->query($query, array($res_id));

		$catId = $stmt->fetchObject()->category_id;

		$query = "SELECT res_id, filename FROM res_view_attachments WHERE status <> 'DEL' and status <> 'OBS' "
                    . "and res_id_master = ? and attachment_type = 'outgoing_mail' order by res_id desc";
			   
		$stmt = $db->query($query, array($res_id));

		$isOutgoingPj = $stmt->fetchObject();

		if($catId == 'outgoing' && !empty($isOutgoingPj)){
			// $stmt = $db->query("SELECT tnl_path, tnl_filename FROM res_attachments WHERE res_id_master = ? AND status NOT IN ('DEL','OBS','TMP') AND type_id = '1'", array($res_id));
				$stmtPdf = $db->query(
                    "SELECT tnl_path, tnl_filename
                     FROM res_attachments
                     WHERE filename=? AND (status = 'TRA' or status = 'A_TRA')", array(substr($isOutgoingPj->filename, 0, strrpos($isOutgoingPj->filename, ".")).'.pdf')
                );

                $linePdf = $stmtPdf->fetchObject();
                if(!empty($linePdf)){
                    $data = $linePdf;
                }
		}else{
			$stmt = $db->query("SELECT tnl_path, tnl_filename FROM $table WHERE res_id = ?", array($res_id));
			$data = $stmt->fetchObject();
		}
		
		$tnlPath = str_replace("#", DIRECTORY_SEPARATOR , $data->tnl_path);
		$tnlFilename = $data->tnl_filename;
		if ($tnlFilename == '-1') $tnlFilename = '';
		$path=$docserver->path_template . DIRECTORY_SEPARATOR . $tnlPath . $tnlFilename;
		$path = str_replace("//","/",$path);
		
		return $path;
	}

        public function testMultiPage($path_tnl){
		if (strpos(pathinfo($path_tnl,PATHINFO_FILENAME),"-0") === false){
			return array($path_tnl);
		}
		else{
			$rep = pathinfo($path_tnl,PATHINFO_DIRNAME);
			$basefile = explode("-",pathinfo($path_tnl,PATHINFO_FILENAME));
			$basefile = $basefile[0];
			$directory  = opendir($rep);
			while (false !== ($filename = readdir($directory))) {
				if (strpos($filename,$basefile) !== false)
					$tmpfiles[] = $rep.DIRECTORY_SEPARATOR.$filename;
			}

			$files = array();
			foreach($tmpfiles as $tmp){
				$tmp_basefile = explode("-",pathinfo($tmp,PATHINFO_FILENAME));
				$cpt = $tmp_basefile[1];
				$files[$cpt] = $tmp;
			}
			return $files;
		}
	}

	/**
	 * Retrieve the path of source file to process
	 * @param array $aArgs
	 * @return string
	 */
	public function getTnlPathWithColl(array $aArgs = []) {
		if (empty($aArgs['resId'])) {
			throw new \Exception('resId empty');
		}
		if (empty($aArgs['collId'])) {
			throw new \Exception('collId empty');
		}

		$resId = $aArgs['resId'];
		$collId = $aArgs['collId'];

		for ($i=0;$i < count($_SESSION['collections']);$i++) {
			if ($_SESSION['collections'][$i]['id'] == $collId) {
				$resTable = $_SESSION['collections'][$i]['table'];
			}
		}
		if (empty($resTable)) {
			return false;
		}

		$oRowSet = Apps_Table_Service::select([
			'select'    => ['path_template'],
			'table'     => ['docservers'],
			'where'     => ['docserver_type_id = ?'],
			'data'      => ['TNL']
		]);

		if (empty($oRowSet[0]['path_template'])) {
			throw new \Exception('TNL docserver path empty');
		}

		$docserverPath = $oRowSet[0]['path_template'];

		$oRowSet = Apps_Table_Service::select([
			'select'    => ['tnl_path', 'tnl_filename'],
			'table'     => [$resTable],
			'where'     => ['res_id = ?'],
			'data'      => [$resId]
		]);

		if (empty($oRowSet)) {
			return false;
		}

		$path          = '';
		$filename      = '';
		if (!empty($oRowSet[0]['tnl_path'])) {
			$path = $oRowSet[0]['tnl_path'];
		}
		if (!empty($oRowSet[0]['tnl_filename'])) {
			$filename = $oRowSet[0]['tnl_filename'];
		}
		$sourceFilePath = $docserverPath . $path . $filename;
		$sourceFilePath = str_replace('#', DIRECTORY_SEPARATOR, $sourceFilePath);

		return $sourceFilePath;
	}

}

