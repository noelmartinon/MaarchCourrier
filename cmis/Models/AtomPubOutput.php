<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Models;

use CMIS\Controllers\CMIS;
use CMIS\Utils\Utils;
use Folder\Models\FoldersModel;

class AtomPubOutput implements OutputStrategyInterface
{
    private $_collections = [
        [
            'type' => 'types',
            'title' => 'Types Collection',
            'accept' => ['']
        ],
        [
            'type' => 'query',
            'title' => 'Query Collection',
            'accept' => ['application/cmisquery+xml']
        ]
    ];

    private $_templates = [
        [
            'template' => '/path?path={path}',
            'type' => 'objectbypath',
            'mediatype' => 'application/atom+xml;type=entry'

        ],
        [
            'template' => '/id?objectId={id}',
            'type' => 'objectbyid',
            'mediatype' => 'application/atom+xml;type=entry'

        ],  [
            'template' => '/type?id={id}',
            'type' => 'typebyid',
            'mediatype' => ''

        ]
    ];

    private $_xml,
        $_webroot,
        $_conf,
        $_app_workspace_node,
        $_repository_info_node;

    public function __construct()
    {
        libxml_use_internal_errors(true);
        $this->_xml = new \DOMDocument("1.0", "UTF-8");
    }


    /**
     * @param $id
     * @param $object CMISObject
     * @param $succinct
     * @param $selector
     * @param null $node
     * @return $this
     */
    public function id($id, $object, $succinct, $selector, $node = null)
    {
        $atom_entry = $this->_xml->createElement("atom:entry");
        $atom_entry_node = ($node) ? $node->appendChild($atom_entry) : $this->_xml->appendChild($atom_entry);
        $atom_entry_node->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
        $atom_entry_node->setAttribute("xmlns:cmis", "http://docs.oasis-open.org/ns/cmis/core/200908/");
        $atom_entry_node->setAttribute("xmlns:cmisra", "http://docs.oasis-open.org/ns/cmis/restatom/200908/");
        $atom_entry_node->setAttribute("xmlns:app", "http://www.w3.org/2007/app");

        $atom_author = $this->_xml->createElement("atom:author");
        $atom_entry_node->appendChild($atom_author);

        $atom_author->appendChild($this->_xml->createElement("atom:name", 'System'));

        $atom_entry_node->appendChild($this->_xml->createElement("atom:id", "MTMz"));
        $atom_entry_node->appendChild($this->_xml->createElement("atom:published", date(DATE_ATOM)));
        $atom_entry_node->appendChild($this->_xml->createElement("atom:edited", date(DATE_ATOM)));
        $atom_entry_node->appendChild($this->_xml->createElement("atom:updated", date(DATE_ATOM)));


        $atom_link = $this->_xml->createElement("atom:link");
        $atom_link_node = $atom_entry->appendChild($atom_link);
        $atom_link_node->setAttribute("rel", "down");
        $atom_link_node->setAttribute("href", str_replace('/id', '', $this->_webroot) . "/descendants?id=" . $id);
        $atom_link_node->setAttribute("type", "application/cmistree+xml");

        $atom_entry_node->appendChild($this->_xml->createElement("atom:updated", date(DATE_ATOM)));

        $obj_node = $this->_xml->createElement("cmisra:object");
        $obj_node->setAttribute("xmlns:ns3", "http://docs.oasis-open.org/ns/cmis/messaging/200908/");
        $atom_object_node = $atom_entry_node->appendChild($obj_node);
        $atom_properties_node = $atom_object_node->appendChild($this->_xml->createElement("cmis:properties"));
        foreach ($object->toArray() as $property) {
            $atom_property = $this->_xml->createElement('cmis:property' . ucfirst($property['type']));
            $atom_property->setAttribute("propertyDefinitionId", $property['id']);
            $atom_property->setAttribute("displayName", $property['displayName']);
            $atom_property->setAttribute("localName", $property['localName']);
            $atom_property->setAttribute("queryName", $property['queryName']);
            $atom_property_node = $atom_properties_node->appendChild($atom_property);
            $atom_property_node->appendChild($this->_xml->createElement("cmis:value", $property['value']));


        }

        return $this;
    }


    private function collections()
    {
        foreach ($this->_collections as $collection) {
            $element = $this->_xml->createElement('app:collection');

            if (!isset($collection['href'])) {
                $element->setAttribute('href', $this->_webroot . '/' . $collection['type']);
            } else {
                $element->setAttribute('href', $this->_webroot . str_replace('[rootFolderId]', $this->_conf['CMIS']['rootFolderId'], $collection['href']));
            }

            $node = $this->_app_workspace_node->appendChild($element);
            $node->appendChild($this->_xml->createElement('cmisra:collectionType', $collection['type']));
            $title = $this->_xml->createElement('atom:title', $collection['title']);
            $title->setAttribute('type', 'text');
            $node->appendChild($title);
            foreach ($collection['accept'] as $accept) {
                $node->appendChild($this->_xml->createElement('app:accept', $accept));
            }
        }

        return $this;
    }

    public function templates()
    {
        foreach ($this->_templates as $template) {
            $element = $this->_xml->createElement('cmisra:uritemplate');
            $node = $this->_app_workspace_node->appendChild($element);
            $node->appendChild($this->_xml->createElement('cmisra:template', $this->_webroot . $template['template']));
            $node->appendChild($this->_xml->createElement('cmisra:type', $template['type']));
            $node->appendChild($this->_xml->createElement('cmisra:mediatype', $template['mediatype']));
        }
        return $this;
    }

    public function repository()
    {
        $app_service = $this->_xml->createElement("app:service");
        $_app_service_node = $this->_xml->appendChild($app_service);
        $_app_service_node->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
        $_app_service_node->setAttribute("xmlns:cmis", "http://docs.oasis-open.org/ns/cmis/core/200908/");
        $_app_service_node->setAttribute("xmlns:cmisra", "http://docs.oasis-open.org/ns/cmis/restatom/200908/");
        $_app_service_node->setAttribute("xmlns:app", "http://www.w3.org/2007/app");

        $this->_app_workspace_node = $_app_service_node->appendChild($this->_xml->createElement('app:workspace'));

        $this->_repository_info_node = $this->_app_workspace_node->appendChild($this->_xml->createElement('cmisra:repositoryInfo'));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:repositoryId', $this->_conf['CMIS']['repositoryId']));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:repositoryName', 'Main Repository'));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:repositoryDescription', 'Main Repository'));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:vendorName', $this->_conf['maarch']['vendorName']));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:productName', $this->_conf['maarch']['productName']));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:productVersion', $this->_conf['maarch']['productVersion']));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:rootFolderId', $this->_conf['CMIS']['rootFolderId']));
        $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:cmisVersionSupported', $this->_conf['CMIS']['cmisVersionSupported']));

        $this->collections();
        $this->templates();

        return $this;
    }


    public function capabilities()
    {
        $capabilities = $this->_repository_info_node->appendChild($this->_xml->createElement('cmis:capabilities'));

        foreach ($this->_conf['capabilities'] as $capability => $value) {
            $capabilities->appendChild($this->_xml->createElement('cmis:capability' . $capability, $value));
        }

        return $this;
    }


    public function descendants($id)
    {
        //set_time_limit(0);
        $objects = CMISObject::getAllObjects($id);

        $this->createAtomEntry(null, $objects);

        return $this;
    }


    public function getObjects()
    {
        // TODO: Implement getObjects() method.
    }

    public function generate()
    {
        return $this->_xml->saveXML();
    }

    public function render()
    {
        header("Content-type: text/xml");
        echo $this->_xml->saveXML();
    }

    public function validate()
    {
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return empty($errors);
    }

    public function webroot($webroot)
    {
        $this->_webroot = $webroot;
        return $this;
    }

    public function renderType($type)
    {
        switch ($type) {
            case 'cmis:document':
                Utils::renderXML('assets/atom/cmis-document.xml');
                break;
            case 'cmis:folder':
                Utils::renderXML('assets/atom/cmis-folder.xml');
                break;
            case 'cmis:policy':
                Utils::renderXML('assets/atom/cmis-policy.xml');
                break;
            case 'cmis:relationship':
                Utils::renderXML('assets/atom/cmis-relationship.xml');
                break;
            case 'cmis:item':
                Utils::renderXML('assets/atom/cmis-relationship.xml');
                break;
            case 'P:cm:titled':
                Utils::renderXML('assets/atom/p-cm-titled.xml');
                break;
            case 'P:sys:localized':
                Utils::renderXML('assets/atom/p-sys-localized.xml');
                break;
            case 'cmis:secondary':
                Utils::renderXML('assets/atom/cmis-secondary.xml');
                break;
            case 'P:app:uifacets':
                Utils::renderXML('assets/atom/p-app-uifacets.xml');
                break;
            case 'F:st:sites':
                Utils::renderXML('assets/atom/f-st-sites.xml');
                break;
            default:
                Utils::renderXML('assets/atom/types.xml');
        }
    }


    public function loadConfiguration($conf)
    {
        $this->_conf = $conf;
        return $this;
    }


    public function query($objects)
    {
        $atom_feed = $this->_xml->createElement("atom:feed");

        $atom_feed_node = (!empty($node)) ? $node->appendChild($atom_feed) : $this->_xml->appendChild($atom_feed);
        $atom_feed_node->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
        $atom_feed_node->setAttribute("xmlns:cmis", "http://docs.oasis-open.org/ns/cmis/core/200908/");
        $atom_feed_node->setAttribute("xmlns:cmisra", "http://docs.oasis-open.org/ns/cmis/restatom/200908/");
        $atom_feed_node->setAttribute("xmlns:app", "http://www.w3.org/2007/app");

        $atom_author = $this->_xml->createElement("atom:author");
        $atom_feed_node->appendChild($atom_author);

        $atom_author->appendChild($this->_xml->createElement("atom:name", 'System'));

        /** @var $obj CMISObject */
        $atom_feed_node->appendChild($this->_xml->createElement("atom:id", base64_encode('query')));
        $atom_feed_node->appendChild($this->_xml->createElement("atom:published", date(DATE_ATOM)));
        $atom_feed_node->appendChild($this->_xml->createElement("atom:edited", date(DATE_ATOM)));
        $atom_feed_node->appendChild($this->_xml->createElement("atom:updated", date(DATE_ATOM)));

        /** @var $object CMISObject */
        foreach ($objects as $object) {
            $this->id($object->getObjectId(), $object, null, null, $atom_feed_node);
        }

        return $this;
    }


    private function createAtomEntry($node, $obj)
    {
        $atom_feed = $this->_xml->createElement("atom:feed");

        $atom_feed_node = (!empty($node)) ? $node->appendChild($atom_feed) : $this->_xml->appendChild($atom_feed);
        $atom_feed_node->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
        $atom_feed_node->setAttribute("xmlns:cmis", "http://docs.oasis-open.org/ns/cmis/core/200908/");
        $atom_feed_node->setAttribute("xmlns:cmisra", "http://docs.oasis-open.org/ns/cmis/restatom/200908/");
        $atom_feed_node->setAttribute("xmlns:app", "http://www.w3.org/2007/app");

        $atom_author = $this->_xml->createElement("atom:author");
        $atom_feed_node->appendChild($atom_author);

        $atom_author->appendChild($this->_xml->createElement("atom:name", 'System'));


        /** @var $obj CMISObject */
        $atom_feed_node->appendChild($this->_xml->createElement("atom:id", $obj->getObjectId()['value']));
        $atom_feed_node->appendChild($this->_xml->createElement("atom:published", date(DATE_ATOM)));
        $atom_feed_node->appendChild($this->_xml->createElement("atom:edited", date(DATE_ATOM)));
        $atom_feed_node->appendChild($this->_xml->createElement("atom:updated", date(DATE_ATOM)));


        /**
         * @var $child CMISObject
         */
        foreach ($obj as $child) {
            $atom_entry = $this->_xml->createElement("atom:entry");
            $atom_entry_node = $atom_feed_node->appendChild($atom_entry);
            $atom_entry_node->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
            $atom_entry_node->setAttribute("xmlns:cmis", "http://docs.oasis-open.org/ns/cmis/core/200908/");
            $atom_entry_node->setAttribute("xmlns:cmisra", "http://docs.oasis-open.org/ns/cmis/restatom/200908/");
            $atom_entry_node->setAttribute("xmlns:app", "http://www.w3.org/2007/app");

            $atom_author = $this->_xml->createElement("atom:author");
            $atom_entry_node->appendChild($atom_author);
            $atom_author->appendChild($this->_xml->createElement("atom:name", 'System'));

            $atom_entry_node->appendChild($this->_xml->createElement("atom:id", $child->getObjectId()['value']));
            $atom_entry_node->appendChild($this->_xml->createElement("atom:published", date(DATE_ATOM)));
            $atom_entry_node->appendChild($this->_xml->createElement("atom:edited", date(DATE_ATOM)));
            $atom_entry_node->appendChild($this->_xml->createElement("atom:updated", date(DATE_ATOM)));

            $atom_link = $this->_xml->createElement("atom:link");
            $atom_link_node = $atom_entry->appendChild($atom_link);
            $atom_link_node->setAttribute("rel", "down");
            $atom_link_node->setAttribute("href", str_replace('/id', '', $this->_webroot) . "?id=" . $child->getObjectId()['value']);
            $atom_link_node->setAttribute("type", "application/cmistree+xml");

            $atom_entry_node->appendChild($this->_xml->createElement("atom:updated", date(DATE_ATOM)));

            $atom_object_node = $atom_entry_node->appendChild($this->_xml->createElement("cmisra:object"));
            $atom_properties_node = $atom_object_node->appendChild($this->_xml->createElement("cmis:properties"));

            foreach ($child->toArray() as $property) {
                $atom_property = $this->_xml->createElement('cmis:property' . ucfirst($property['type']));
                $atom_property->setAttribute('propertyDefinitionId', $property['id']);
                $atom_property->setAttribute('displayName', $property['displayName']);
                $atom_property->setAttribute('localName', $property['localName']);
                $atom_property->setAttribute('queryName', $property['queryName']);
                $atom_property_node = $atom_properties_node->appendChild($atom_property);
                $atom_property_node->appendChild($this->_xml->createElement('cmis:value', $property['value']));
            }

            $atom_children_node = $atom_entry_node->appendChild($this->_xml->createElement("cmisra:children"));

            foreach ($child as $value) {
                $this->createAtomEntry($atom_children_node, $child);
            }
        }
    }

}