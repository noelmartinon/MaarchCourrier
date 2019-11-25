<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Annuary Controller
* @author dev@maarch.org
*/

namespace MessageExchange\controllers;

use Entity\models\EntityModel;
use Group\models\ServiceModel;
use Parameter\models\ParameterModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;

class AnnuaryController
{
    public static function updateEntityToOrganization(Request $request, Response $response, array $args)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getById(['id' => $args['id'], 'select' => ['entity_label']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity does not exist']);
        }

        $siret = ParameterModel::getById(['id' => 'siret', 'select' => ['param_value_string']]);
        if (empty($siret['param_value_string'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Parameter siret does not exist']);
        }

        $entitySiret = "{$siret['param_value_string']}/{$args['id']}";

        EntityModel::update(['set' => ['business_id' => $entitySiret], 'where' => ['id = ?'], 'data' => [$args['id']]]);

        $control = AnnuaryController::getAnnuaries();
        if (!isset($control['annuaries'])) {
            if (isset($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            } else {
                return $response->withJson(['entitySiret' => $entitySiret]);
            }
        }
        $organization = $control['organization'];
        $communicationMeans = $control['communicationMeans'];
        $annuaries = $control['annuaries'];

        foreach ($annuaries as $annuary) {
            $ldap = @ldap_connect($annuary['uri']);
            if ($ldap === false) {
                continue;
            }
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 10);

            $search = @ldap_search($ldap, "{$annuary['baseDN']}", "(ou={$organization})", ['dn']);
            if ($search === false) {
                continue;
            }

            $authenticated = @ldap_bind($ldap, $annuary['login'], $annuary['password']);
            if (!$authenticated) {
                return $response->withStatus(400)->withJson(['errors' => 'Ldap authentication failed : ' . ldap_error($ldap)]);
            }

            $entries = ldap_get_entries($ldap, $search);
            if ($entries['count'] == 0) {
                $info = [];
                $info['ou'] = $organization;
                $info['destinationIndicator'] = $siret['param_value_string'];
                $info['labeledURI'] = $communicationMeans['url'] ?? null;
                $info['postOfficeBox'] = $communicationMeans['email'] ?? null;
                $info['objectclass'] = ['organizationalUnit', 'top', 'labeledURIObject'];

                $added = @ldap_add($ldap, "ou={$organization},{$annuary['baseDN']}", $info);
                if (!$added) {
                    return $response->withStatus(400)->withJson(['errors' => 'Ldap add failed : ' . ldap_error($ldap)]);
                }
            }

            $search = @ldap_search($ldap, "ou={$organization},{$annuary['baseDN']}", "(destinationIndicator={$args['id']})", ['dn']);
            if ($search === false) {
                return $response->withStatus(400)->withJson(['errors' => 'Ldap search failed : ' . ldap_error($ldap)]);
            }
            $entries = ldap_get_entries($ldap, $search);

            if ($entries['count'] > 0) {
                $renamed = @ldap_rename($ldap, $entries[0]['dn'], "cn={$entity['entity_label']}", "ou={$organization},{$annuary['baseDN']}", true);
                if (!$renamed) {
                    return $response->withStatus(400)->withJson(['errors' => 'Ldap rename failed : ' . ldap_error($ldap)]);
                }

                $replaced = @ldap_mod_replace($ldap, "cn={$entity['entity_label']},ou={$organization},{$annuary['baseDN']}", ['sn' => $entity['entity_label']]);
                if (!$replaced) {
                    return $response->withStatus(400)->withJson(['errors' => 'Ldap replace failed : ' . ldap_error($ldap)]);
                }
            } else {
                $info = [];
                $info['cn'] = $entity['entity_label'];
                $info['sn'] = $entity['entity_label'];
                $info['destinationIndicator'] = $args['id'];
                $info['objectclass'] = ['top', 'inetOrgPerson'];

                $added = @ldap_add($ldap, "cn={$entity['entity_label']},ou={$organization},{$annuary['baseDN']}", $info);
                if (!$added) {
                    return $response->withStatus(400)->withJson(['errors' => 'Ldap add failed : ' . ldap_error($ldap)]);
                }
            }

            break;
        }

        return $response->withJson(['entitySiret' => $entitySiret]);
    }

    public static function deleteEntityToOrganization(array $args)
    {
        $control = AnnuaryController::getAnnuaries();
        if (!isset($control['annuaries'])) {
            return $control;
        }
        $organization = $control['organization'];
        $annuaries = $control['annuaries'];

        foreach ($annuaries as $annuary) {
            $ldap = @ldap_connect($annuary['uri']);
            if ($ldap === false) {
                continue;
            }
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 10);

            $search = @ldap_search($ldap, "ou={$organization},{$annuary['baseDN']}", "(destinationIndicator={$args['id']})", ['dn']);
            if ($search === false) {
                return ['errors' => 'Ldap search failed : baseDN is maybe wrong => ' . ldap_error($ldap)];
            }
            $entries = ldap_get_entries($ldap, $search);
            if ($entries['count'] == 0) {
                return ['success' => 'Entity does not exist in annuary'];
            }

            $authenticated = @ldap_bind($ldap, $annuary['login'], $annuary['password']);
            if (!$authenticated) {
                return ['errors' => 'Ldap authentication failed : ' . ldap_error($ldap)];
            }
            $deleted = @ldap_delete($ldap, $entries[0]['dn']);
            if (!$deleted) {
                return ['errors' => 'Ldap delete failed : ' . ldap_error($ldap)];
            }

            break;
        }

        return ['success' => 'success'];
    }

    public static function getAnnuaries()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/m2m_config.xml']);

        if (!$loadedXml) {
            return ['success' => 'M2M is disabled'];
        }
        if ($loadedXml->annuaries->enabled == 'false') {
            return ['success' => 'Annuary is disabled'];
        }
        $organization = (string)$loadedXml->annuaries->organization;
        if (empty($organization)) {
            return ['errors' => 'Tag organization is empty'];
        }
        $annuaries = [];
        foreach ($loadedXml->annuaries->annuary as $annuary) {
            $uri = ((string)$annuary->ssl === 'true' ? "LDAPS://{$annuary->uri}" : (string)$annuary->uri);

            $annuaries[] = [
                'uri'       => $uri,
                'baseDN'    => (string)$annuary->baseDN,
                'login'     => (string)$annuary->login,
                'password'  => (string)$annuary->password,
                'ssl'       => (string)$annuary->ssl,
            ];
        }

        $rawCommunicationMeans = (string)$loadedXml->m2m_communication;
        if (empty($rawCommunicationMeans)) {
            return ['errors' => 'Tag m2m_communication is empty'];
        }
        $communicationMeans = [];
        $rawCommunicationMeans = explode(',', $rawCommunicationMeans);
        foreach ($rawCommunicationMeans as $value) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $communicationMeans['email'] = $value;
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                $communicationMeans['url'] = $value;
            }
        }
        if (empty($communicationMeans)) {
            return ['errors' => 'No communication means found'];
        }

        return ['annuaries' => $annuaries, 'organization' => $organization, 'communicationMeans' => $communicationMeans];
    }
}
