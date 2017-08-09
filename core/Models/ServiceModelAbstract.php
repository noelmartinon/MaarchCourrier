<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Service Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class ServiceModelAbstract extends \Apps_Table_Service
{

    public static function hasService(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['id', 'userId', 'location', 'type']);
        static::checkString($aArgs, ['id', 'userId', 'location', 'type']);

        if ($aArgs['userId'] == 'superadmin') {
            return true;
        }
        $rawServicesStoredInDB = UserModel::getServicesById(['userId' => $aArgs['userId']]);
        $servicesStoredInDB = [];
        foreach ($rawServicesStoredInDB as $value) {
            $servicesStoredInDB[] = $value['service_id'];
        }

        $xmlfile = ServiceModel::getLoadedXml(['location' => $aArgs['location']]);

        if ($xmlfile) {
            foreach ($xmlfile->SERVICE as $value) {
                if ((string)$value->servicetype == $aArgs['type'] && (string)$value->id == $aArgs['id'] && (string)$value->enabled === 'true'
                    && ((string)$value->system_service == 'true' || in_array((string)$value->id, $servicesStoredInDB))) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function getLoadedXml(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['location']);
        static::checkString($aArgs, ['location']);

        $customId = CoreConfigModel::getCustomId();

        if ($aArgs['location'] == 'apps') {
            if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/services.xml")) {
                $path = "custom/{$customId}/apps/maarch_entreprise/xml/services.xml";
            } else {
                $path = 'apps/maarch_entreprise/xml/services.xml';
            }
        } else {
            if (file_exists("custom/{$customId}/modules/{$aArgs['location']}/xml/services.xml")) {
                $path = "custom/{$customId}/modules/{$aArgs['location']}/xml/services.xml";
            } else {
                $path = "modules/{$aArgs['location']}/xml/services.xml";
            }
        }

        if (!file_exists($path)) {
            return false;
        }

        $loadedXml = simplexml_load_file($path);

        return $loadedXml;
    }
}
