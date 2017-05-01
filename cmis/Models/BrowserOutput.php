<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */
namespace CMIS\Models;

use CMIS\Utils\Utils;

class BrowserOutput implements OutputStrategyInterface
{
    private $_array = [],
        $_conf = [],
        $_webroot = '';

    public function loadConfiguration($conf)
    {
        $this->_conf = $conf;
    }

    public function repository()
    {
        $this->_array[$this->_conf['CMIS']['repositoryId']] = [
            'repositoryId' => $this->_conf['CMIS']['repositoryId'],
            'repositoryName' => $this->_conf['CMIS']['repositoryName'],
            'repositoryDescription' => $this->_conf['CMIS']['repositoryDescription'],
            'vendorName' => $this->_conf['maarch']['vendorName'],
            'productName' => $this->_conf['maarch']['productName'],
            'productVersion' => $this->_conf['maarch']['productVersion'],
            'rootFolderId' => $this->_conf['CMIS']['rootFolderId'],
            'capabilities' => [],
            'cmisVersionSupported' => $this->_conf['CMIS']['cmisVersionSupported'],
            'principalIdAnonymous' => $this->_conf['CMIS']['principalIdAnonymous'],
            'principalIdAnyone' => $this->_conf['CMIS']['principalIdAnyone'],
            'extendedFeatures' => [[
                "id" => "http://docs.oasis-open.org/ns/cmis/extension/datetimeformat",
                "url" => "https://www.oasis-open.org/committees/tc_home.php?wg_abbrev=cmis",
                "commonName" => "Browser Binding DateTime Format",
                "versionLabel" => "1.0",
                "description" => "Adds an additional DateTime format for the Browser Binding."
            ]],
            'repositoryUrl' => $this->_webroot,
            'rootFolderUrl' => $this->_webroot . '/id'

        ];

        return $this;
    }

    public function capabilities()
    {

        foreach ($this->_conf['capabilities'] as $capability => $value) {
            $this->_array[$this->_conf['CMIS']['repositoryId']]['capabilities']['capability' . $capability] = $value;
        }
        return $this;
    }

    public function generate()
    {
        return json_encode($this->_array);
    }

    public function render()
    {
        header('Content-Type: application/json');
        echo json_encode($this->_array);
    }

    public function validate()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ''; // JSON is valid // No error has occurred
                break;
            case JSON_ERROR_DEPTH:
                $error = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON.';
                break;
            // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                $error = 'One or more recursive references in the value to be encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                $error = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $error = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $error = 'Unknown JSON error occured.';
        }

        if ($error !== '') {
            $error = null;
        }

        return $error;
    }


    public function getObjects()
    {
        return $this;
    }

    public function webroot($webroot)
    {
        $this->_webroot = $webroot;
        return $this;
    }

    public function descendants($id)
    {
        // TODO: Implement descendants() method.
        return $this;
    }

    public function id($objects, $succint = true, $selector)
    {
        //$this->_array['objects'] = [];
        $this->_array['hasMoreItems'] = false;
        $this->_array['numItems'] = sizeof($objects);

        if ($succint) {
            /**
             * @var $object CMISObject
             */
            foreach ($objects as $object) {
                $array['cmis:objectId'] = $object->getObjectId()['value'];
                $array['cmis:path'] = $object->getPath()['value'];
                $array['cmis:lastModifiedBy'] = $object->getLastModifiedBy()['value'];
                $array['cmis:objectTypeId'] = $object->getObjectTypeId()['value'];
                $array['cmis:description'] = $object->getDescription()['value'];
                $array['cmis:createdBy'] = $object->getCreatedBy()['value'];
                $array['cmis:baseTypeId'] = $object->getBaseTypeId()['value'];
                $array['cmis:parentId'] = $object->getParentId()['value'];
                $array['cmis:creationDate'] = $object->getCreationDate()['value'];
                $array['cmis:changeToken'] = $object->getChangeToken()['value'];
                $array['cmis:name'] = $object->getName()['value'];
                $array['cmis:lastModificationDate'] = $object->getLastModificationDate()['value'];

                if($selector == 'object'){
                    $this->_array['succinctProperties'] = $array;
                } else {
                    $this->_array['objects'][]['object']['succinctProperties'] = $array;
                }
            }
        } else {

            /**
             * @var $object CMISObject
             */
            foreach ($objects as $object) {
                $array['cmis:objectId'] = $object->getObjectId();
                $array['cmis:path'] = $object->getPath();
                $array['cmis:lastModifiedBy'] = $object->getLastModifiedBy();
                $array['cmis:objectTypeId'] = $object->getObjectTypeId();
                $array['cmis:description'] = $object->getDescription();
                $array['cmis:createdBy'] = $object->getCreatedBy();
                $array['cmis:baseTypeId'] = $object->getBaseTypeId();
                $array['cmis:parentId'] = $object->getParentId();
                $array['cmis:creationDate'] = $object->getCreationDate();
                $array['cmis:changeToken'] = $object->getChangeToken();
                $array['cmis:name'] = $object->getName();
                $array['cmis:lastModificationDate'] = $object->getLastModificationDate();

                if($selector == 'object') {
                    $this->_array['properties'] = $array;
                } else {
                    $this->_array['objects'][]['object']['properties'] = $array;
                }
            }
        }

        return $this;
    }


    public function query()
    {
        // TODO: Implement query() method.
    }

    public function renderType($type)
    {
        switch ($type) {
            case 'cmis:document':
                Utils::renderJSON('assets/browser/cmis-document.json');
                break;
            case 'cmis:folder':
                Utils::renderJSON('assets/browser/cmis-folder.json');
                break;
            case 'cmis:policy':
                Utils::renderJSON('assets/browser/cmis-policy.json');
                break;
            case 'cmis:relationship':
                Utils::renderJSON('assets/browser/cmis-relationship.json');
                break;
            case 'cmis:item':
                Utils::renderJSON('assets/browser/cmis-item.json');
                break;
        }
    }
}