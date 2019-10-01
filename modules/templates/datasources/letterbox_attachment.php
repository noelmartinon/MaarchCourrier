<?php
/*********************************************************************************
** Get aditionnal data to merge template
**
*********************************************************************************/
$dbDatasource = new Database();

require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
.'class_contacts_v2.php';
$contacts = new contacts_v2();

// Main document resource from view
$datasources['res_letterbox'] = array();
if (empty($res_view)) {
    $res_view = 'res_view_letterbox';
}

if (!empty($res_id)) {
    $stmt = $dbDatasource->query('SELECT * FROM '.$res_view.' WHERE res_id = ? ', array($res_id));
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    $date = new DateTime($doc['doc_date']);
    $doc['doc_date'] = $date->format('d/m/Y');

    $admission_date = new DateTime($doc['admission_date']);
    $doc['admission_date'] = $admission_date->format('d/m/Y');

    $creation_date = new DateTime($doc['creation_date']);
    $doc['creation_date'] = $creation_date->format('d/m/Y');

    $process_limit_date = new DateTime($doc['process_limit_date']);
    $doc['process_limit_date'] = $process_limit_date->format('d/m/Y');

    $doc['category_id'] = html_entity_decode($_SESSION['coll_categories']['letterbox_coll'][$doc['category_id']]);

    $doc['nature_id'] = $_SESSION['mail_natures'][$doc['nature_id']];

    //INITIATOR INFO OF DOCUMENT
    $stmt2 = $dbDatasource->query('SELECT a.*, b.entity_label as parent_entity_label
    FROM entities as a, entities as b
    WHERE a.entity_id = ?
    AND a.parent_entity_id = b.entity_id', array($doc['initiator']));
    $initiator = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!empty($initiator)) {
        foreach ($initiator as $column => $value) {
            $doc['initiator_'.$column] = $value;
        }
        $doc['initiator_entity_path'] = \Entity\models\EntityModel::getEntityPathByEntityId(['entityId' => $doc['initiator'], 'path' => '']);
    }

    $datasources['res_letterbox'][] = $doc;

    //COMPLEMENTARY CURRENT USER INFO
    $stmt2 = $dbDatasource->query('SELECT * FROM entities WHERE entity_id = ? ', array($_SESSION['user']['primaryentity']['id']));
    $dest = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!empty($dest)) {
        foreach ($dest as $column => $value) {
            $datasources['user'][0][$column] = $value;
        }
        $datasources['user'][0]['entity_path'] = \Entity\models\EntityModel::getEntityPathByEntityId(['entityId' => $_SESSION['user']['primaryentity']['id'], 'path' => '']);
    }

    //COMPLEMENTARY CURRENT USER PARENT ENTITY INFO
    $stmt2 = $dbDatasource->query('SELECT * FROM entities WHERE entity_id = ? ', array($datasources['user'][0]['parent_entity_id']));
    $dest = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (!empty($dest)) {
        foreach ($dest as $column => $value) {
            $datasources['user'][0]['parent_'.$column] = $value;
        }
        $datasources['user'][0]['parent_entity_path'] = \Entity\models\EntityModel::getEntityPathByEntityId(['entityId' => $datasources['user'][0]['parent_entity_id'], 'path' => '']);
    }

    //multicontact
    $stmt = $dbDatasource->query('SELECT * FROM contacts_res WHERE res_id = ? AND contact_id = ? ', array($doc['res_id'], $res_contact_id));
    $datasources['res_letterbox_contact'][] = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($params['mailing'])) {
        if ($datasources['res_letterbox_contact'][0]['contact_id'] != '' && $_SESSION['attachmentInfo'][$_SESSION['attachmentInfo']['attachNum']]['contactId'] != '' && $_SESSION['attachmentInfo'][$_SESSION['attachmentInfo']['attachNum']]['contactId'] != 'mailing') {
            // $datasources['contact'] = array();
            $attachNum = $_SESSION['attachmentInfo']['attachNum'];
            $stmt = $dbDatasource->query('SELECT * FROM view_contacts WHERE contact_id = ? and ca_id = ? ', array($_SESSION['attachmentInfo'][$attachNum]['contactId'], $_SESSION['attachmentInfo'][$attachNum]['addressId']));
            $myContact = $stmt->fetch(PDO::FETCH_ASSOC);
            $myContact['postal_address'] = \Contact\controllers\ContactController::formatContactAddressAfnor($myContact);
            $myContact['contact_title'] = $contacts->get_civility_contact($myContact['contact_title']);
            $myContact['title'] = $contacts->get_civility_contact($myContact['title']);
            $datasources['contact'][] = $myContact;
    
        // single Contact
        } elseif (isset($res_contact_id) && isset($res_address_id) && is_numeric($res_contact_id)) {
            $stmt = $dbDatasource->query('SELECT * FROM view_contacts WHERE contact_id = ? and ca_id = ? ', array($res_contact_id, $res_address_id));
            $myContact = $stmt->fetch(PDO::FETCH_ASSOC);
            $myContact['postal_address'] = \Contact\controllers\ContactController::formatContactAddressAfnor($myContact);
            $myContact['contact_title'] = $contacts->get_civility_contact($myContact['contact_title']);
            $myContact['title'] = $contacts->get_civility_contact($myContact['title']);
            $datasources['contact'][] = $myContact;
        } elseif (!empty($res_contact_id) && !is_numeric($res_contact_id)) {
            $stmt = $dbDatasource->query('SELECT firstname, lastname, user_id, mail, phone, initials FROM users WHERE user_id = ?', [$res_contact_id]);
            $myContact = $stmt->fetch(PDO::FETCH_ASSOC);
            $datasources['contact'][] = $myContact;
        } elseif (!empty($context) && !empty($datasources['res_letterbox'][0]['address_id']) && !empty($datasources['res_letterbox'][0]['contact_id'])) {
            $stmt = $dbDatasource->query('SELECT * FROM view_contacts WHERE contact_id = ? and ca_id = ?', array($datasources['res_letterbox'][0]['contact_id'], $datasources['res_letterbox'][0]['address_id']));
            $myContact = $stmt->fetch(PDO::FETCH_ASSOC);
            $myContact['postal_address'] = \Contact\controllers\ContactController::formatContactAddressAfnor($myContact);
            $myContact['contact_title'] = $contacts->get_civility_contact($myContact['contact_title']);
            $myContact['title'] = $contacts->get_civility_contact($myContact['title']);
            $datasources['contact'][] = $myContact;
        }
    
        if (isset($datasources['contact'][0]['title']) && $datasources['contact'][0]['title'] == '') {
            $datasources['contact'][0]['title'] = $datasources['contact'][0]['contact_title'];
        } else {
            $datasources['contact'][0]['contact_title'] = $datasources['contact'][0]['title'];
        }
        if (isset($datasources['contact'][0]['firstname']) && $datasources['contact'][0]['firstname'] == '') {
            $datasources['contact'][0]['firstname'] = $datasources['contact'][0]['contact_firstname'];
        } else {
            $datasources['contact'][0]['contact_firstname'] = $datasources['contact'][0]['firstname'];
        }
        if (isset($datasources['contact'][0]['lastname']) && $datasources['contact'][0]['lastname'] == '') {
            $datasources['contact'][0]['lastname'] = $datasources['contact'][0]['contact_lastname'];
        } else {
            $datasources['contact'][0]['contact_lastname'] = $datasources['contact'][0]['lastname'];
        }
        if (isset($datasources['contact'][0]['function']) && $datasources['contact'][0]['function'] == '') {
            $datasources['contact'][0]['function'] = $datasources['contact'][0]['contact_function'];
        } else {
            $datasources['contact'][0]['contact_function'] = $datasources['contact'][0]['function'];
        }
        if (isset($datasources['contact'][0]['other_data']) && $datasources['contact'][0]['other_data'] == '') {
            $datasources['contact'][0]['other_data'] = $datasources['contact'][0]['contact_other_data'];
        } else {
            $datasources['contact'][0]['contact_other_data'] = $datasources['contact'][0]['other_data'];
        }
    }

    // Notes
    $datasources['notes'] = array();
    $stmt = $dbDatasource->query('SELECT notes.*, users.firstname, users.lastname FROM notes left join users on notes.user_id = users.user_id WHERE coll_id = ? AND identifier = ?', array($coll_id, $res_id));

    $countNote = 1;
    while ($notes = $stmt->fetchObject()) {
        $datasources['notes'][0]['note_text'.$countNote] = $notes->note_text;
        $datasources['notes'][0]['date_note'.$countNote] = $notes->date_note;
        ++$countNote;
    }

    // Attachments

    if (empty($params['mailing'])) {
        $datasources['attachments'] = array();
        $myAttachment['chrono'] = $chronoAttachment;
    }
    
    //thirds
    $stmt = $dbDatasource->query('SELECT * FROM contacts_res WHERE res_id = ? AND mode = ? ', [$doc['res_id'], 'third']);
    $datasources['thirds'] = [];
    $countThird = 1;
    while ($third = $stmt->fetchObject()) {
        if (is_numeric($third->contact_id)) {
            $stmt2 = $dbDatasource->query('SELECT * FROM view_contacts WHERE contact_id = ? ', [$third->contact_id]);
            $thirdContact = $stmt2->fetchObject();
            if ($thirdContact) {
                $datasources['thirds'][0]['firstname'.$countThird] = ($thirdContact->contact_firstname ?: $thirdContact->firstname);
                $datasources['thirds'][0]['lastname'.$countThird] = ($thirdContact->contact_lastname ?: $thirdContact->lastname);
            }
        } else {
            $stmt2 = $dbDatasource->query('SELECT * FROM users WHERE user_id = ? ', [$third->contact_id]);
            $thirdContact = $stmt2->fetchObject();
            if ($thirdContact) {
                $datasources['thirds'][0]['firstname'.$countThird] = $thirdContact->firstname;
                $datasources['thirds'][0]['lastname'.$countThird] = $thirdContact->lastname;
            }
        }
        ++$countThird;
    }

    //visa
    $stmt = $dbDatasource->query('SELECT * FROM listinstance WHERE res_id = ? AND difflist_type = ?  ORDER BY sequence ASC', [$doc['res_id'], 'VISA_CIRCUIT']);
    $datasources['visa'] = [];
    $countVisa = 1;
    while ($visa = $stmt->fetchObject()) {
        $process_date = new DateTime($visa->process_date);
        $stmt2 = $dbDatasource->query('SELECT u.*, ue.user_role as role FROM users u, users_entities ue WHERE u.user_id = ? AND ue.user_id = u.user_id AND ue.primary_entity = ?', [$visa->item_id, 'Y']);
        $visaContact = $stmt2->fetchObject();
        $stmt3 = $dbDatasource->query('SELECT en.entity_id, en.entity_label FROM entities en, users_entities ue WHERE ue.user_id = ? AND primary_entity = ? AND ue.entity_id = en.entity_id', [$visa->item_id, 'Y']);
        $visaEntity = $stmt3->fetchObject();
        if ($visaContact) {
            if ($visa->item_mode == 'sign') {
                $datasources['visa'][0]['firstnameSign'] = $visaContact->firstname;
                $datasources['visa'][0]['lastnameSign'] = $visaContact->lastname;
                $datasources['visa'][0]['roleSign'] = $visaContact->role;
                $datasources['visa'][0]['entitySign'] = str_replace($visaEntity->entity_id.': ', '', $visaEntity->entity_label);
                $datasources['visa'][0]['dateSign'] = $process_date->format('d/m/Y');
            } else {
                $datasources['visa'][0]['firstname'.$countVisa] = $visaContact->firstname;
                $datasources['visa'][0]['lastname'.$countVisa] = $visaContact->lastname;
                $datasources['visa'][0]['role'.$countVisa] = $visaContact->role;
                $datasources['visa'][0]['entity'.$countVisa] = str_replace($visaEntity->entity_id.': ', '', $visaEntity->entity_label);
                $datasources['visa'][0]['date'.$countVisa] = $process_date->format('d/m/Y');
                ++$countVisa;
            }
        }
    }

    //AVIS CICUIT
    $stmt = $dbDatasource->query('SELECT * FROM listinstance WHERE res_id = ? AND difflist_type = ?  ORDER BY sequence ASC', [$doc['res_id'], 'AVIS_CIRCUIT']);
    $datasources['avis'] = [];
    $countVisa = 1;
    $i = 1;
    while ($avis = $stmt->fetchObject()) {
        $stmt2 = $dbDatasource->query('SELECT u.*, ue.user_role as role FROM users u, users_entities ue WHERE u.user_id = ? AND ue.user_id = u.user_id AND ue.primary_entity = ?', [$avis->item_id, 'Y']);
        $avisContact = $stmt2->fetchObject();
        $stmt3 = $dbDatasource->query('SELECT en.entity_id, en.entity_label FROM entities en, users_entities ue WHERE ue.user_id = ? AND primary_entity = ? AND ue.entity_id = en.entity_id', [$avis->item_id, 'Y']);
        $stmt4 = $dbDatasource->query('SELECT note_text, date_note FROM notes WHERE user_id = ? AND identifier = ? AND note_text LIKE ? ORDER BY date_note ASC', [$avis->item_id, $doc['res_id'], '[Avis n°%']);

        $avisEntity = $stmt3->fetchObject();
        $avisContent = $stmt4->fetchObject();
        if ($avisContact) {
            if ($avis->item_mode == 'avis') {
                $datasources['avis'][0]['firstname'.$i] = $avisContact->firstname;
                $datasources['avis'][0]['lastname'.$i] = $avisContact->lastname;
                $datasources['avis'][0]['role'.$i] = $avisContact->role;
                $datasources['avis'][0]['entity'.$i] = str_replace($avisEntity->entity_id.': ', '', $avisEntity->entity_label);
                if ($avisContent) {
                    $datasources['avis'][0]['note'.$i] = $avisContent->note_text;
                    $datasources['avis'][0]['date_note'.$i] = $avisContent->note_text;
                }
            }
        }
        ++$i;
    }

    // COPIES LIST
    // usage in template :
    // [copies.firstname1] [copies.lastname1] [copies.entity1]
    // [copies.firstname2] [copies.lastname2] [copies.entity2]
    // ...
    // [copies.firstnameN] [copies.lastnameN] [copies.entityN]
    $stmt = $dbDatasource->query('SELECT * FROM listinstance WHERE res_id = ? AND difflist_type = ? AND item_mode = ? '
        . 'ORDER BY sequence ASC', [$doc['res_id'], 'entity_id', 'cc']);
    $datasources['copies'] = [];
    $i = 1;
    while ($copies = $stmt->fetchObject()) {
        $copiesContact = false;
        $copiesEntity = false;
        if ($copies->item_type == 'user_id') {
            $stmt2 = $dbDatasource->query('SELECT * FROM users WHERE user_id = ?', [$copies->item_id]);
            $copiesContact = $stmt2->fetchObject();
            $stmt3 = $dbDatasource->query('SELECT en.entity_id, en.entity_label FROM entities en, users_entities ue '
                . 'WHERE ue.user_id = ? AND primary_entity = ? AND ue.entity_id = en.entity_id', [$copies->item_id, 'Y']);
            $entity = $stmt3->fetchObject();
        } elseif ($copies->item_type == 'entity_id') {
            $stmt3 = $dbDatasource->query('SELECT entity_label FROM entities '
                . 'WHERE entity_id = ?', [$copies->item_id]);
            $copiesEntity = $stmt3->fetchObject();
        }
        if ($copiesContact) {
            $datasources['copies'][0]['firstname'.$i] = $copiesContact->firstname;
            $datasources['copies'][0]['lastname'.$i] = $copiesContact->lastname;
            $datasources['copies'][0]['entity'.$i] = str_replace($entity->entity_id.': ', '', $entity->entity_label);
        }
        if ($copiesEntity) {
            $datasources['copies'][0]['entity'.$i] = $copiesEntity->entity_label;
        }
        ++$i;
    }

    // Transmissions
    $datasources['transmissions'] = [];
    if (isset($_SESSION['transmissionContacts']) && count($_SESSION['transmissionContacts']) > 0) {
        if (isset($_SESSION['attachmentInfo']['attachNum']) && $_SESSION['transmissionContacts'][$_SESSION['attachmentInfo']['attachNum']]) {
            $curNb = $_SESSION['attachmentInfo']['attachNum'];
            foreach ($_SESSION['transmissionContacts'][$curNb] as $key => $value) {
                if ($key == 'title' || $key == 'contact_title') {
                    $datasources['transmissions'][0]['currentContact_'.$key] = $contacts->get_civility_contact($value);
                } else {
                    $datasources['transmissions'][0]['currentContact_'.$key] = $value;
                }
            }

            if (isset($datasources['transmissions'][0]['currentContact_title']) && $datasources['transmissions'][0]['currentContact_title'] == '') {
                $datasources['transmissions'][0]['currentContact_title'] = $datasources['transmissions'][0]['currentContact_contact_title'];
            } else {
                $datasources['transmissions'][0]['currentContact_contact_title'] = $datasources['transmissions'][0]['currentContact_title'];
            }
            if (isset($datasources['transmissions'][0]['currentContact_firstname']) && $datasources['transmissions'][0]['currentContact_firstname'] == '') {
                $datasources['transmissions'][0]['currentContact_firstname'] = $datasources['transmissions'][0]['currentContact_contact_firstname'];
            } else {
                $datasources['transmissions'][0]['currentContact_contact_firstname'] = $datasources['transmissions'][0]['currentContact_firstname'];
            }
            if (isset($datasources['transmissions'][0]['currentContact_lastname']) && $datasources['transmissions'][0]['currentContact_lastname'] == '') {
                $datasources['transmissions'][0]['currentContact_lastname'] = $datasources['transmissions'][0]['currentContact_contact_lastname'];
            } else {
                $datasources['transmissions'][0]['currentContact_contact_lastname'] = $datasources['transmissions'][0]['currentContact_lastname'];
            }
            if (isset($datasources['transmissions'][0]['currentContact_function']) && $datasources['transmissions'][0]['currentContact_function'] == '') {
                $datasources['transmissions'][0]['currentContact_function'] = $datasources['transmissions'][0]['currentContact_contact_function'];
            } else {
                $datasources['transmissions'][0]['currentContact_contact_function'] = $datasources['transmissions'][0]['currentContact_function'];
            }
            if (isset($datasources['transmissions'][0]['currentContact_other_data']) && $datasources['transmissions'][0]['currentContact_other_data'] == '') {
                $datasources['transmissions'][0]['currentContact_other_data'] = $datasources['transmissions'][0]['currentContact_contact_other_data'];
            } else {
                $datasources['transmissions'][0]['currentContact_contact_other_data'] = $datasources['transmissions'][0]['currentContact_other_data'];
            }
        }

        $nb = 1;
        foreach ($_SESSION['transmissionContacts'] as $it => $transmission) {
            foreach ($transmission as $key => $value) {
                if ($key == 'title' || $key == 'contact_title') {
                    $datasources['transmissions'][0][$key.$nb] = $contacts->get_civility_contact($value);
                } else {
                    $datasources['transmissions'][0][$key.$nb] = $value;
                }
            }
            ++$nb;
        }

        $nb = 1;
        foreach ($_SESSION['transmissionContacts'] as $it => $transmission) {
            if (isset($datasources['transmissions'][0]['title'.$nb]) && $datasources['transmissions'][0]['title'.$nb] == '') {
                $datasources['transmissions'][0]['title'.$nb] = $datasources['transmissions'][0]['contact_title'.$nb];
            } else {
                $datasources['transmissions'][0]['contact_title'.$nb] = $datasources['transmissions'][0]['title'.$nb];
            }
            if (isset($datasources['transmissions'][0]['firstname'.$nb]) && $datasources['transmissions'][0]['firstname'.$nb] == '') {
                $datasources['transmissions'][0]['firstname'.$nb] = $datasources['transmissions'][0]['contact_firstname'.$nb];
            } else {
                $datasources['transmissions'][0]['contact_firstname'.$nb] = $datasources['transmissions'][0]['firstname'.$nb];
            }
            if (isset($datasources['transmissions'][0]['lastname'.$nb]) && $datasources['transmissions'][0]['lastname'.$nb] == '') {
                $datasources['transmissions'][0]['lastname'.$nb] = $datasources['transmissions'][0]['contact_lastname'.$nb];
            } else {
                $datasources['transmissions'][0]['contact_lastname'.$nb] = $datasources['transmissions'][0]['lastname'.$nb];
            }
            if (isset($datasources['transmissions'][0]['function'.$nb]) && $datasources['transmissions'][0]['function'.$nb] == '') {
                $datasources['transmissions'][0]['function'.$nb] = $datasources['transmissions'][0]['contact_function'.$nb];
            } else {
                $datasources['transmissions'][0]['contact_function'.$nb] = $datasources['transmissions'][0]['function'.$nb];
            }
            if (isset($datasources['transmissions'][0]['other_data'.$nb]) && $datasources['transmissions'][0]['other_data'.$nb] == '') {
                $datasources['transmissions'][0]['other_data'.$nb] = $datasources['transmissions'][0]['contact_other_data'.$nb];
            } else {
                $datasources['transmissions'][0]['contact_other_data'.$nb] = $datasources['transmissions'][0]['other_data'.$nb];
            }
            ++$nb;
        }
    }
}

if (empty($params['mailing'])) {
    $img_file_name = $_SESSION['config']['tmppath'].$_SESSION['user']['UserId'].time().rand().'_barcode_attachment.png';

    require_once 'apps/maarch_entreprise/tools/pdfb/barcode/pi_barcode.php';
    $objCode = new pi_barcode();
    
    $objCode->setCode($chronoAttachment);
    $objCode->setType('C128');
    $objCode->setSize(30, 50);
    
    $objCode->setText($chronoAttachment);
    
    $objCode->hideCodeType();
    
    $objCode->setFiletype('PNG');
    
    $objCode->writeBarcodeFile($img_file_name);
    
    $myAttachment['chronoBarCode'] = $img_file_name;
    $datasources['attachments'][] = $myAttachment;
}
