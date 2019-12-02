<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Merge Controller
 *
 * @author dev@maarch.org
 */

namespace ContentManagement\controllers;

use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Doctype\models\DoctypeExtModel;
use Entity\models\EntityModel;
use Note\models\NoteModel;
use Resource\models\ResModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

include_once('vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php');


class MergeController
{
    public static function mergeDocument(array $args)
    {
        ValidatorModel::notEmpty($args, ['data']);
        ValidatorModel::arrayType($args, ['data']);
        ValidatorModel::stringType($args, ['path', 'content']);
        ValidatorModel::notEmpty($args['data'], ['resId', 'contactAddressId', 'userId']);
        ValidatorModel::intVal($args['data'], ['resId', 'contactAddressId', 'userId']);

        setlocale(LC_TIME, _DATE_LOCALE);

        $tbs = new \clsTinyButStrong();
        $tbs->NoErr = true;
        $tbs->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN);

        if (!empty($args['path'])) {
            $pathInfo = pathinfo($args['path']);
            $extension = $pathInfo['extension'];
        } else {
            $tbs->Source = $args['content'];
            $extension = 'unknow';
            $args['path'] = null;
        }

        if (!empty($args['path'])) {
            if ($extension == 'odt') {
                $tbs->LoadTemplate($args['path'], OPENTBS_ALREADY_UTF8);
    //            $tbs->LoadTemplate("{$args['path']}#content.xml;styles.xml", OPENTBS_ALREADY_UTF8);
            } elseif ($extension == 'docx') {
                $tbs->LoadTemplate($args['path'], OPENTBS_ALREADY_UTF8);
    //            $tbs->LoadTemplate("{$args['path']}#word/header1.xml;word/footer1.xml", OPENTBS_ALREADY_UTF8);
            } else {
                $tbs->LoadTemplate($args['path'], OPENTBS_ALREADY_UTF8);
            }
        }

        $dataToBeMerge = MergeController::getDataForMerge($args['data']);

        foreach ($dataToBeMerge as $key => $value) {
            $tbs->MergeField($key, $value);
        }

        if (in_array($extension, ['odt', 'ods', 'odp', 'xlsx', 'pptx', 'docx', 'odf'])) {
            $tbs->Show(OPENTBS_STRING);
        } else {
            $tbs->Show(TBS_NOTHING);
        }

        return ['encodedDocument' => base64_encode($tbs->Source)];
    }

    private static function getDataForMerge(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'contactAddressId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'contactAddressId', 'userId']);

        //Resource
        $resource = ResModel::getOnView(['select' => ['*'], 'where' => ['res_id = ?'], 'data' => [$args['resId']]])[0];
        $allDates = ['doc_date', 'departure_date', 'admission_date', 'process_limit_date', 'opinion_limit_date', 'closing_date', 'creation_date'];
        foreach ($allDates as $date) {
            $resource[$date] = TextFormatModel::formatDate($resource[$date], 'd/m/Y');
        }
        if (!empty($resource['category_id'])) {
            $resource['category_id'] = ResModel::getCategoryLabel(['category_id' => $resource['category_id']]);
        }
        if (!empty($resource['nature_id'])) {
            $resource['nature_id'] = ResModel::getNatureLabel(['nature_id' => $resource['nature_id']]);
        }
        $doctype = DoctypeExtModel::getById(['id' => $resource['type_id'], 'select' => ['process_delay', 'process_mode']]);
        $resource['process_delay'] = $doctype['process_delay'];
        $resource['process_mode'] = $doctype['process_mode'];

        if (!empty($resource['initiator'])) {
            $initiator = EntityModel::getByEntityId(['entityId' => $resource['initiator'], 'select' => ['*']]);
            if (!empty($initiator)) {
                foreach ($initiator as $key => $value) {
                    $resource["initiator_{$key}"] = $value;
                }
            }

            if (!empty($initiator['parent_entity_id'])) {
                $parentInitiator = EntityModel::getByEntityId(['entityId' => $initiator['parent_entity_id'], 'select' => ['*']]);
            }
        }
        if (!empty($resource['destination'])) {
            $destination = EntityModel::getByEntityId(['entityId' => $resource['destination'], 'select' => ['*']]);
            if (!empty($destination['parent_entity_id'])) {
                $parentDestination = EntityModel::getByEntityId(['entityId' => $destination['parent_entity_id'], 'select' => ['*']]);
            }
        }

        //User
        $currentUser = UserModel::getById(['id' => $args['userId'], 'select' => ['firstname', 'lastname', 'phone', 'mail', 'initials']]);

        //Contact
        $contact = ContactModel::getOnView(['select' => ['*'], 'where' => ['ca_id = ?'], 'data' => [$args['contactAddressId']]])[0];
        $contact['postal_address'] = ContactController::formatContactAddressAfnor($contact);
        $contact['title'] = ContactModel::getCivilityLabel(['civilityId' => $contact['title']]);
        if (empty($contact['title'])) {
            $contact['title'] = ContactModel::getCivilityLabel(['civilityId' => $contact['contact_title']]);
        }
        if (empty($contact['firstname'])) {
            $contact['firstname'] = $contact['contact_firstname'];
        }
        if (empty($contact['lastname'])) {
            $contact['lastname'] = $contact['contact_lastname'];
        }
        if (empty($contact['function'])) {
            $contact['function'] = $contact['contact_function'];
        }
        if (empty($contact['other_data'])) {
            $contact['other_data'] = $contact['contact_other_data'];
        }

        //Notes
        $mergedNote = '';
        $notes = NoteModel::getByUserIdForResource(['select' => ['note_text', 'creation_date', 'user_id'], 'resId' => $args['resId'], 'userId' => $args['userId']]);
        foreach ($notes as $note) {
            $labelledUser = UserModel::getLabelledUserById(['login' => $note['user_id']]);
            $creationDate = TextFormatModel::formatDate($note['creation_date'], 'd/m/Y');
            $mergedNote .= "{$labelledUser} : {$creationDate} : {$note['note_text']}\n";
        }

        $dataToBeMerge['res_letterbox']     = $resource;
        $dataToBeMerge['initiator']         = empty($initiator) ? [] : $initiator;
        $dataToBeMerge['parentInitiator']   = empty($parentInitiator) ? [] : $parentInitiator;
        $dataToBeMerge['destination']       = empty($destination) ? [] : $destination;
        $dataToBeMerge['parentDestination'] = empty($parentDestination) ? [] : $parentDestination;
        $dataToBeMerge['user']              = $currentUser;
        $dataToBeMerge['contact']           = $contact;
        $dataToBeMerge['notes']             = $mergedNote;

        return $dataToBeMerge;
    }
}
