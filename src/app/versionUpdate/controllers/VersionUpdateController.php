<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Version Update Controller
 * @author dev@maarch.org
 */

namespace VersionUpdate\controllers;

use Docserver\models\DocserverModel;
use Gitlab\Client;
use Group\controllers\PrivilegeController;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class VersionUpdateController
{
    const BACKUP_TABLES = ['usergroups_services', 'groupbasket'];

    public function get(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_update_control', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $client = Client::create('https://labs.maarch.org/api/v4/');
        try {
            $tags = $client->api('tags')->all('12');
        } catch (\Exception $e) {
            return $response->withJson(['errors' => $e->getMessage()]);
        }

        $applicationVersion = CoreConfigModel::getApplicationVersion();
        if (empty($applicationVersion)) {
            return $response->withStatus(400)->withJson(['errors' => "Can't load xml applicationVersion"]);
        }

        $currentVersion = $applicationVersion;

        $versions = explode('.', $currentVersion);
        $currentVersionBranch = "{$versions[0]}.{$versions[1]}";
        $currentVersionTag = $versions[2];
        $currentVersionBranchYear = $versions[0];
        $currentVersionBranchMonth = $versions[1];

        $availableMinorVersions = [];
        $availableMajorVersions = [];

        foreach ($tags as $value) {
            if (!preg_match("/^\d{2}\.\d{2}\.\d+$/", $value['name'])) {
                continue;
            }
            $explodedValue = explode('.', $value['name']);
            $tag = $explodedValue[2];

            $pos = strpos($value['name'], $currentVersionBranch);
            if ($pos === false) {
                $year = substr($value['name'], 0, 2);
                $month = substr($value['name'], 3, 2);
                if (($year == $currentVersionBranchYear && $month > $currentVersionBranchMonth) || $year > $currentVersionBranchYear) {
                    $availableMajorVersions[] = $value['name'];
                }
            } else {
                if ($tag > $currentVersionTag) {
                    $availableMinorVersions[] = $value['name'];
                }
            }
        }

        natcasesort($availableMinorVersions);
        natcasesort($availableMajorVersions);

        if (empty($availableMinorVersions)) {
            $lastAvailableMinorVersion = null;
        } else {
            $lastAvailableMinorVersion = $availableMinorVersions[0];
        }

        if (empty($availableMajorVersions)) {
            $lastAvailableMajorVersion = null;
        } else {
            $lastAvailableMajorVersion = $availableMajorVersions[0];
        }

        $output = [];

        exec('git status --porcelain --untracked-files=no 2>&1', $output);
        
        return $response->withJson([
            'lastAvailableMinorVersion' => $lastAvailableMinorVersion,
            'lastAvailableMajorVersion' => $lastAvailableMajorVersion,
            'currentVersion'            => $currentVersion,
            'canUpdate'                 => empty($output),
            'diffOutput'                => $output,
        ]);
    }

    public function update(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_update_control', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $client = Client::create('https://labs.maarch.org/api/v4/');
        try {
            $tags = $client->api('tags')->all('12');
        } catch (\Exception $e) {
            return $response->withJson(['errors' => $e->getMessage()]);
        }

        $applicationVersion = CoreConfigModel::getApplicationVersion();

        if (empty($applicationVersion)) {
            return $response->withStatus(400)->withJson(['errors' => "Can't load xml applicationVersion"]);
        }

        $currentVersion = $applicationVersion;

        $versions = explode('.', $currentVersion);
        $currentVersionBranch = "{$versions[0]}.{$versions[1]}";
        $currentVersionTag = $versions[2];

        $availableMinorVersions = [];

        foreach ($tags as $value) {
            if (strpos($value['name'], $currentVersionBranch) === false) {
                continue;
            }
            $explodedValue = explode('.', $value['name']);
            $tag = $explodedValue[2];

            if ($tag > $currentVersionTag) {
                $availableMinorVersions[] = $value['name'];
            }
        }

        if (empty($availableMinorVersions)) {
            return $response->withStatus(400)->withJson(['errors' => 'No minor versions available']);
        }

        natcasesort($availableMinorVersions);

        $minorVersion = $availableMinorVersions[0];

        $output = [];
        exec('git status --porcelain --untracked-files=no 2>&1', $output);
        if (!empty($output)) {
            return $response->withStatus(400)->withJson(['errors' => 'Some files are modified. Can not update application', 'lang' => 'canNotUpdateApplication']);
        }

        $minorVersions = explode('.', $minorVersion);
        $currentVersionTag = (int)$currentVersionTag;
        $currentVersionTag++;
        $sqlFiles = [];
        while ($currentVersionTag <= (int)$minorVersions[2]) {
            if (is_file("migration/{$versions[0]}.{$versions[1]}/{$versions[0]}{$versions[1]}{$currentVersionTag}.sql")) {
                if (!is_readable("migration/{$versions[0]}.{$versions[1]}/{$versions[0]}{$versions[1]}{$currentVersionTag}.sql")) {
                    return $response->withStatus(400)->withJson(['errors' => "File migration/{$versions[0]}.{$versions[1]}/{$versions[0]}{$versions[1]}{$currentVersionTag}.sql is not readable"]);
                }
                $sqlFiles[] = "migration/{$versions[0]}.{$versions[1]}/{$versions[0]}{$versions[1]}{$currentVersionTag}.sql";
            }
            $currentVersionTag++;
        }

        $control = VersionUpdateController::executeSQLUpdate(['sqlFiles' => $sqlFiles]);
        if (!empty($control['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
        }

        $output = [];
        exec('git fetch');
        exec("git checkout {$minorVersion} 2>&1", $output, $returnCode);

        $log = "Application update from {$currentVersion} to {$minorVersion}\nCheckout response {$returnCode} => " . implode(' ', $output) . "\n";
        file_put_contents("{$control['directoryPath']}/updateVersion.log", $log, FILE_APPEND);

        if ($returnCode != 0) {
            return $response->withStatus(400)->withJson(['errors' => "Application update failed. Please check updateVersion.log at {$control['directoryPath']}"]);
        }

        return $response->withStatus(204);
    }

    private static function executeSQLUpdate(array $args)
    {
        ValidatorModel::arrayType($args, ['sqlFiles']);

        $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'DOC', 'collId' => 'letterbox_coll', 'select' => ['path_template']]);
        $directoryPath = explode('/', rtrim($docserver['path_template'], '/'));
        array_pop($directoryPath);
        $directoryPath = implode('/', $directoryPath);

        if (!is_dir($directoryPath . '/migration')) {
            if (!is_writable($directoryPath)) {
                return ['errors' => 'Directory path is not writable : ' . $directoryPath];
            }
            mkdir($directoryPath . '/migration', 0755, true);
        } elseif (!is_writable($directoryPath . '/migration')) {
            return ['errors' => 'Directory path is not writable : ' . $directoryPath . '/migration'];
        }

        if (!empty($args['sqlFiles'])) {
            $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);
            $databaseName = (string)$loadedXml->CONFIG->databasename[0];

            $actualTime = date("dmY-His");
            $tablesToSave = '';
            foreach (self::BACKUP_TABLES as $table) {
                $tablesToSave .= ' -t ' . $table;
            }

            $execReturn = exec("pg_dump -d \"{$databaseName}\" {$tablesToSave} -a > \"{$directoryPath}/migration/backupDB_maarchcourrier_{$actualTime}.sql\"", $output, $intReturn);
            if (!empty($execReturn)) {
                return ['errors' => 'Pg dump failed : ' . $execReturn];
            }

            foreach ($args['sqlFiles'] as $sqlFile) {
                $fileContent = file_get_contents($sqlFile);
                DatabaseModel::exec($fileContent);
            }
        }

        return ['directoryPath' => "{$directoryPath}/migration"];
    }
}
