<?php
/* kofaxToMC: capture dans MaarchCourrier des documents scannés par Kofax
 * @author Quentin RIBAC pour Maarch
 *
 * usage: php php/main.php dossier/
 */

require 'curlRequest_function.php';

echo "--- kofaxToMC ---\n";

// lecture du fichier de configuration
$configPath = (count($argv) > 1 && is_file($argv[1])) ? $argv[1] : './config.xml';
$configXml = simplexml_load_file($configPath);
$MAARCH_WS_USER = (string) $configXml->xpath('/root/config/rest/username')[0];
$MAARCH_WS_PASS = (string) $configXml->xpath('/root/config/rest/password')[0];
$MAARCH_WS_HTTPS = ((string) $configXml->xpath('/root/config/rest/https')[0]) === 'true';
$MAARCH_WS_PATH = (string) $configXml->xpath('/root/config/rest/path')[0];
$MAARCH_WS_URL = 'http'.($MAARCH_WS_HTTPS ? 's' : '').'://'.$MAARCH_WS_USER.':'.$MAARCH_WS_PASS.'@'.$MAARCH_WS_PATH;
$docnamefield = (string) $configXml->xpath('/root/config/docnamefield')[0];
$maindocconstfield = (string) $configXml->xpath('/root/config/maindocconstfield')[0];
$archiveboxidfield = (string) $configXml->xpath('/root/config/archiveboxidfield')[0];
$rootDir = (string) $configXml->xpath('/root/config/directories/source')[0];
$doneDir = (string) $configXml->xpath('/root/config/directories/done')[0];
$errorDir = (string) $configXml->xpath('/root/config/directories/errors')[0];
$doctype = (string) $configXml->xpath('/root/config/doctype')[0];

$rootDir = rtrim($rootDir, '/');
$doneDir = rtrim($doneDir, '/');
$errorDir = rtrim($errorDir, '/');
$MAARCH_WS_URL = rtrim($MAARCH_WS_URL, '/').'/';

if (!is_dir($rootDir)) {
	die($rootDir.' n’est pas un dossier accessible. Abandon.');
}

// récupération de l’attributaire par défaut d’une entité
function getDestUserByEntityId($entityId)
{
	global $MAARCH_WS_URL;
	$response = curlRequest(null, $MAARCH_WS_URL.'listTemplates/entities/'.$entityId, null, 'GET');
	foreach ($response->listTemplates as $lt) {
		if ($lt->type !== 'diffusionList') {
			continue;
		}
		foreach ($lt->items as $lti) {
			if ($lti->item_mode === 'dest' && $lti->item_type === 'user' && $lti->sequence === 0) {
				return $lti->item_id;
			}
		}
	}
	return null;
}

// stream file as base64
function fileToB64Stream($filename)
{
    $h = fopen($filename, 'r');
    stream_filter_append($h, 'convert.base64-encode');
    stream_set_chunk_size($h, 1024);
    return $h;
}

// fonction de déplacement d’un fichier de $rootDir vers $destDir avec création si nécessaire d’arborescence
function moveToDir($oldPath, $rootDir, $destDir)
{
	$newPath = str_replace($rootDir, $rootDir.'/'.$destDir, $oldPath);
	if (!is_dir(dirname($newPath)) && !mkdir(dirname($newPath), 0777, true)) {
		$errorCode = 'mkdir_failed';
		notify();
		return false;
	}
	if (!rename($oldPath, $newPath)) {
		$errorCode = 'mv_failed';
		notify();
		return false;
	}
	$oldDir = dirname($oldPath);
	while (is_dir($oldDir) && scandir($oldDir) === ['.', '..'] && $oldDir !== $rootDir) {
		rmdir($oldDir);
		$oldDir = dirname($oldDir);
	}
	return true;
}

// gestion d’erreur : déplace les fichiers d’un lot vers le dossier d’erreur correspondant
function moveToErrorDir($oldPaths, $rootDir, $errorCode)
{
	global $errorDir;
	foreach ($oldPaths as $oldPath) {
		if (!moveToDir($oldPath, $rootDir, $errorDir.'/error_'.$errorCode)) {
			return false;
		}
	}
	return true;
}

// fonction de lecture récursive des sous-dossiers pour trouver les fichiers correspondants à une condition fournie en callback
function findInDirectoryRecursive($dir, $filter)
{
	global $doneDir;
	global $errorDir;
	if (!is_dir($dir)) {
		return [];
	}
	$selectedFileNames = [];
	foreach (scandir($dir) as $sub) {
		if (in_array($sub, ['.', '..', $doneDir, $errorDir])) {
			continue;
		}
		if (is_file($dir.'/'.$sub) && !!call_user_func($filter, $dir.'/'.$sub)) {
			$selectedFileNames []= $dir.'/'.$sub;
		} elseif (is_dir($dir.'/'.$sub)) {
			array_push($selectedFileNames, ...findInDirectoryRecursive($dir.'/'.$sub, $filter));
		}
	}
	return $selectedFileNames;
}

// recherche des *.xml dans $rootDir
$xmlPaths = findInDirectoryRecursive($rootDir, function ($name) {
	return 1 === preg_match('/^.+\.xml$/', $name);
});

// fonction d’envoi de notifications d’erreur
function notify()
{
	global $errorCode;
	echo "\n\t/!\\ Erreur /!\\\n";
	$errorMessage = str_expand_vars(file_get_contents('error_messages/'.$errorCode.'.txt'));
	echo $errorMessage;
	return $errorMessage;
}

// fonction de remplacement des :nomsDeVariables: par leur valeur
function str_expand_vars($subject)
{
	return preg_replace_callback('/:((\w|\d|_)+):/', function ($matches) {
		$_var_name = $matches[1];
		global $$_var_name;
		if (isset($$_var_name)) {
			return $$_var_name;
		}
		return "-$_var_name-";
	}, $subject);
}

// récupération des entités
$entities = curlRequest(null, $MAARCH_WS_URL.'entities', null, 'GET');
if (!isset($entities->entities)) {
	$repr = var_export($entities, true);
	file_put_contents($rootDir.'/ERREUR.txt', 'Impossible de récupérer la liste des entités Maarch Courrier. Abandon.'.PHP_EOL.$repr);
	echo $repr.PHP_EOL;
	die('Impossible de récupérer la liste des entités Maarch Courrier. Abandon.');
}
$entities = $entities->entities;

// parcours des XML
foreach ($xmlPaths as $xmlI => $xmlPath) {
	$batchName = basename($xmlPath, '.xml');
	$batchPathInRootDir = str_replace($rootDir.'/', '', dirname($xmlPath));
	$xml = simplexml_load_file($xmlPath);
	echo "\n--- [".($xmlI+1).'/'.count($xmlPaths)."] $xmlPath ---\n";
	$destination = (string) $xml->xpath('/Root/DirectorateName')[0];
	$documentCount = (int) $xml->xpath('/Root/documentCount')[0];
	$documents = explode('#', $xml->xpath('/Root/documents')[0]);
	array_splice($documents, $documentCount);
	$comments = (string) $xml->xpath('/Root/Comments')[0];
	$arrivalDate = (string) $xml->xpath('/Root/ScanDateTime')[0];
	$hashAlgo = (string) $xml->xpath('/Root/TimeStampHashAlgorithm')[0];
	$archiveboxid = (string) $xml->xpath('/Root/ArchiveBoxID')[0];

	$resId = null;
	$errorCode = null;
	$errorMessage = null;

	foreach ($documents as $i => $document) {
		$parts = explode('/', $document);
		$docPath = dirname($xmlPath).'/'.$parts[0];
		$ersPath = dirname($xmlPath).'/'.$parts[1];
		if (!is_file($docPath) || !is_file($ersPath)) {
			if ($i === 0) {
				$errorCode = 'primary_file_not_found';
				$errorMessage = notify();
				break;
			} else {
				$errorCode = 'attachment_file_not_found';
				$errorMessage = notify();
				break;
			}
		}
		$hash = strtolower($parts[2]);
		$fh = strtolower(hash_file($hashAlgo, $docPath));
		if ($hash !== $fh) {
			echo "hash incorrect pour le fichier $docPath\n";
			if ($i === 0) {
				$errorCode = 'primary_file_hash_failed';
				$errorMessage = notify();
				break;
			} else {
				$errorCode = 'attachment_file_hash_failed';
				$errorMessage = notify();
				break;
			}
		}
		echo "Hash correct pour : $docPath\n";
	}

	if (!empty($errorCode)) {
		$paths = [$xmlPath];
		file_put_contents(dirname($xmlPath).'/LISEZMOI.txt', $errorMessage);
		$paths []= dirname($xmlPath).'/LISEZMOI.txt';
		foreach ($documents as $document) {
			$paths []= dirname($xmlPath).'/'.(explode('/', $document)[0]);
			$paths []= dirname($xmlPath).'/'.(explode('/', $document)[1]);
		}
		moveToErrorDir($paths, $rootDir, $errorCode);
		continue;
	}

	foreach ($documents as $i => $document) {
		$parts = explode('/', $document);
		$docPath = dirname($xmlPath).'/'.$parts[0];
		$ersPath = dirname($xmlPath).'/'.$parts[1];
		if ($i === 0) {
			echo 'injection du document principal ... (PDF) ';
			$destinationId = null;
			foreach ($entities as $entity) {
				if ($entity->entity_id === $destination) {
					$destinationId = $entity->serialId;
					break;
				}
			}
			if ($destinationId === null) {
				$errorCode = 'entity_not_found';
				$errorMessage = notify();
				break;
			}
			$arrivalDate = preg_replace('|^(\d{2})/(\d{2})/(\d{4}) (\d{2}:\d{2}:\d{2})$|', '$3-$2-$1T$4', $arrivalDate);
            $docFileHandle = fileToB64Stream($docPath);
			$resource = [
				'modelId' => 1,
				'chrono' => true,
				'doctype' => $doctype,
				'subject' => $batchName.' / '.$arrivalDate,
				'status' => 'INIT',
				'destination' => $destinationId,
				'initiator' => $destinationId,
				'arrivalDate' => $arrivalDate,
				'encodedFile' => $docFileHandle,
				'format' => 'pdf',
				'customFields' => [
					$docnamefield => $parts[0],
					$maindocconstfield => 'Courrier entrant principal',
					$archiveboxidfield => $archiveboxid,
				],
			];
			$destUser = getDestUserByEntityId($resource['destination']);
			if (isset($destUser)) {
				$resource['diffusionList'] = [
					['id' => $destUser, 'mode' => 'dest', 'type' => 'user'],
				];
			}
			$res = curlRequest($resource, $MAARCH_WS_URL.'resources', null, 'POST');
			fclose($docFileHandle);
			if (!empty($res->errors)) {
				$wserrors = $res->errors;
				$errorCode = 'primary_file_request_failed';
				$errorMessage = notify();
				break;
			}
			$resId = $res->resId;
			echo '(ERS) ';
            $ersFileHandle = fileToB64Stream($ersPath);
			$attachment = [
				'resIdMaster' => $resId,
				'type' => 'kofax_ers_attachment',
				'title' => basename($ersPath),
				'encodedFile' => $ersFileHandle,
				'format' => 'ers',
			];
			$res = curlRequest($attachment, $MAARCH_WS_URL.'attachments', null, 'POST');
            fclose($ersFileHandle);
			if (!empty($res->errors)) {
				$wserrors = $res->errors;
				$errorCode = 'ers_attachment_request_failed';
				$errorMessage = notify();
				break;
			}
			if (!empty($comments)) {
				echo '(note)';
				$note = ['value' => $comments, 'resId' => $resId];
				$res = curlRequest($note, $MAARCH_WS_URL.'notes', null, 'POST');
				if (!empty($res->errors)) {
					$wserrors = $res->errors;
					$errorCode = 'note_request_failed';
					$errorMessage = notify();
					break;
				}
			}
			echo "\n";
		} else {
			echo "injection de la pièce-jointe no $i ... ";
			echo '(PDF) ';
            $docFileHandle = fileToB64Stream($docPath);
			$attachment = [
				'resIdMaster' => $resId,
				'type' => 'kofax_pdf_attachment',
				'title' => basename($docPath),
				'encodedFile' => $docFileHandle,
				'format' => 'pdf',
			];
			$res = curlRequest($attachment, $MAARCH_WS_URL.'attachments', null, 'POST');
            fclose($docFileHandle);
			if (!empty($res->errors)) {
				$wserrors = $res->errors;
				$errorCode = 'pdf_attachment_request_failed';
				$errorMessage = notify();
				break;
			}
			echo '(ERS) ';
            $ersFileHandle = fileToB64Stream($ersPath);
			$attachment = [
				'resIdMaster' => $resId,
				'type' => 'kofax_ers_attachment',
				'title' => basename($ersPath),
				'encodedFile' => $ersFileHandle,
				'format' => 'ers',
			];
			$res = curlRequest($attachment, $MAARCH_WS_URL.'attachments', null, 'POST');
            fclose($ersFileHandle);
			if (!empty($res->errors)) {
				$wserrors = $res->errors;
				$errorCode = 'ers_attachment_request_failed';
				$errorMessage = notify();
				break;
			}
			echo "\n";
		}
	}
	if (!empty($errorCode)) {
		$paths = [$xmlPath];
		file_put_contents(dirname($xmlPath).'/LISEZMOI.txt', $errorMessage);
		$paths []= dirname($xmlPath).'/LISEZMOI.txt';
		foreach ($documents as $document) {
			$paths []= dirname($xmlPath).'/'.(explode('/', $document)[0]);
			$paths []= dirname($xmlPath).'/'.(explode('/', $document)[1]);
		}
		moveToErrorDir($paths, $rootDir, $errorCode);
		continue;
	}

	echo 'injection du fichier xml ... ';
    $xmlFileHandle = fileToB64Stream($xmlPath);
	$attachment = [
		'resIdMaster' => $resId,
		'type' => 'kofax_xml_attachment',
		'title' => basename($xmlPath),
		'encodedFile' => $xmlFileHandle,
		'format' => 'xml',
	];
	$res = curlRequest($attachment, $MAARCH_WS_URL.'attachments', null, 'POST');
    fclose($xmlFileHandle);
	$error = false;
	if (!empty($res->errors)) {
		$wserrors = $res->errors;
		$errorCode = 'xml_attachment_request_failed';
		$errorMessage = notify();
	}

	if (!empty($errorCode)) {
		$paths = [$xmlPath];
		file_put_contents(dirname($xmlPath).'/LISEZMOI.txt', $errorMessage);
		$paths []= dirname($xmlPath).'/LISEZMOI.txt';
		foreach ($documents as $document) {
			$paths []= dirname($xmlPath).'/'.(explode('/', $document)[0]);
			$paths []= dirname($xmlPath).'/'.(explode('/', $document)[1]);
		}
		moveToErrorDir($paths, $rootDir, $errorCode);
		continue;
	} else {
		echo "\ndéplacement des fichiers ... ";
		if (!moveToDir($xmlPath, $rootDir, $doneDir)) {
			continue;
		}
		foreach ($documents as $document) {
			$parts = explode('/', $document);
			$docPath = dirname($xmlPath).'/'.$parts[0];
			$ersPath = dirname($xmlPath).'/'.$parts[1];
			if (!moveToDir($docPath, $rootDir, $doneDir)) {
				break;
			}
			if (!moveToDir($ersPath, $rootDir, $doneDir)) {
				break;
			}
		}
		echo "\n";
	}
}
