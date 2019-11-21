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


use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

class AnnuaryController
{
    public static function addEntityToOrganization(array $args)
    {
        ValidatorModel::notEmpty($args, ['siret']);
        ValidatorModel::stringType($args, ['siret']);

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
        foreach ($loadedXml->annuary as $annuary) {
            $annuaries[] = [
                'uri'       => (string)$annuary->uri,
                'baseDN'    => (string)$annuary->baseDN,
                'login'     => (string)$annuary->login,
                'password'  => (string)$annuary->password,
                'ssl'       => (string)$annuary->ssl,
            ];
        }

        foreach ($annuaries as $annuary) {
            $ldap = @ldap_connect($annuary);
            if ($ldap === false) {
                $error = 'Ldap connect failed : uri is maybe wrong';
                continue;
            }
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 10);

            $search = @ldap_search($ldap, $annuary['baseDN'], "(destinationIndicator={$args['siret']})", ['dn', 'mail', 'sn', 'labeledURI', 'ou']);
            if ($search === false) {
                $error = 'Ldap search failed : baseDN is maybe wrong => ' . ldap_error($ldap);
            }
            $entries = ldap_get_entries($ldap, $search);

            if ($entries['count'] > 0) {
                return ['errors' => 'Entity siret already exists'];
            }

            $authenticated = @ldap_bind($ldap, $annuary['login'], $annuary['password']);
            if (!$authenticated) {
                return ['errors' => 'Ldap authentication failed : ' . ldap_error($ldap)];
            }

        }


    }

    public static function fouretout(array $args)
    {

        $t = '9753';
        if ($search === false) {
            $error = 'Ldap search failed : baseDN is maybe wrong => ' . ldap_error($ldap);
        }
        $entries = ldap_get_entries($ldap, $search);
        $ldapLogin = $entries[0]['dn'];
//        $search = @ldap_search($ldap, 'cn=Contacts M2M,dc=maarch,dc=com', "(destinationIndicator={$t}*)", ['dn', 'mail', 'sn', 'labeledURI']);
//        if ($search === false) {
//            $error = 'Ldap search failed : baseDN is maybe wrong => ' . ldap_error($ldap);
//        }
//        $entries = ldap_get_entries($ldap, $search);
//        $ldapLogin = $entries[0]['dn'];

        $authenticated = @ldap_bind($ldap, "cn=admin,dc=maarch,dc=com", 'maarch');


        //CREATE
        $entityId = 22;
        $entityName = 'Pole juridique';
        $entityEmail = 'pj@maarch.com';
        $entitySiret = '975319/22';
//        $info["cn"] = $entityName;
//        $info["sn"] = $entityName;
//        $info["destinationIndicator"] = $entitySiret;
//        $info["mail"] = $entityEmail;
//        $info["objectclass"] = ["top", "inetOrgPerson"];
//        $r = ldap_add($ldap, "cn={$entityName},ou=Services du Premier Ministre,cn=Contacts M2M,dc=maarch,dc=com", $info);
//        $j = ldap_error($ldap);



//        $entityId = 22;
//        $entityName = 'Pole culture';
//        $entityNameUpdated = 'Pole culture futur';
//        $r = ldap_rename($ldap, "cn={$entityName},ou=Services du Premier Ministre,cn=Contacts M2M,dc=maarch,dc=com", "cn={$entityNameUpdated}", "ou=Services du Premier Ministre,cn=Contacts M2M,dc=maarch,dc=com", true);
//        $j = ldap_error($ldap);
//
//        $entityEmailUpdated = 'pc2@maarch.com';
//        $r = ldap_mod_replace($ldap, "cn={$entityNameUpdated},ou=Services du Premier Ministre,cn=Contacts M2M,dc=maarch,dc=com", ["mail" => $entityEmailUpdated, "sn" => $entityNameUpdated]);
//        $j = ldap_error($ldap);

//        $r = ldap_delete($ldap, "cn={$entityName},ou=Services du Premier Ministre,cn=Contacts M2M,dc=maarch,dc=com");
//        $j = ldap_error($ldap);

//        if (!$authenticated) {
//            $error = ldap_error($ldap);
//        }
    }
}
