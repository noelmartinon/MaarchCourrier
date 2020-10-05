<?php

require '../../vendor/autoload.php';

chdir('../..');

$customs =  scandir('custom');

foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    $language = \SrcCore\models\CoreConfigModel::getLanguage();
    if (file_exists("custom/{$customId}/src/core/lang/lang-{$language}.php")) {
        require_once("custom/{$customId}/src/core/lang/lang-{$language}.php");
    }
    require_once("src/core/lang/lang-{$language}.php");

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);

    $migrated = 0;

    $savedQueries = \SrcCore\models\DatabaseModel::select([
        'select'    => ['*'],
        'table'     => ['saved_queries']
    ]);

    $superadmin = \User\models\UserModel::getByLogin(['select' => ['id'], 'login' => 'superadmin']);
    if (empty($superadmin)) {
        $firstMan = \User\models\UserModel::get(['select' => ['id'], 'orderBy' => ['id'], 'limit' => 1]);
        $masterOwnerId = $firstMan[0]['id'];
    } else {
        $masterOwnerId = $superadmin['id'];
    }

    foreach ($savedQueries as $savedQuery) {
        $query = [];
        $savedQuery['query_txt'] = str_replace("'", '"', $savedQuery['query_txt']);
        $queryTxt = json_decode($savedQuery['query_txt'], true);

        foreach ($queryTxt as $key => $value) {
            if ($key == 'subject' && !empty($value['fields']['subject'][0])) {
                $query[] = ['identifier' => 'subject', 'values' => $value['fields']['subject'][0]];
            } elseif ($key == 'chrono' && !empty($value['fields']['chrono'][0])) {
                $query[] = ['identifier' => 'chrono', 'values' => $value['fields']['chrono'][0]];
            } elseif ($key == 'barcode' && !empty($value['fields']['barcode'][0])) {
                $query[] = ['identifier' => 'barcode', 'values' => $value['fields']['barcode'][0]];
            } elseif ($key == 'sender' && !empty($value['fields']['sender_id'][0])) {
                $type = $value['fields']['sender_type'][0] == 'onlyContact' ? 'contact' : $value['fields']['sender_type'][0];
                $query[] = ['identifier' => 'senders', 'values' => [['id' => $value['fields']['sender_id'][0], 'type' => $type, 'label' => '']]];
            } elseif ($key == 'recipient' && !empty($value['fields']['recipient_id'][0])) {
                $type = $value['fields']['recipient_type'][0] == 'onlyContact' ? 'contact' : $value['fields']['recipient_type'][0];
                $query[] = ['identifier' => 'recipients', 'values' => [['id' => $value['fields']['recipient_id'][0], 'type' => $type, 'label' => '']]];
            } elseif ($key == 'signatory_name' && !empty($value['fields']['signatory_name_id'][0])) {
                $user = \User\models\UserModel::getByLogin(['login' => $value['fields']['signatory_name_id'][0], 'select' => ['id', 'firstname', 'lastname']]);
                if (!empty($user)) {
                    $query[] = ['identifier' => 'role_sign', 'values' => [['id' => $user['id'], 'type' => 'user', 'label' => "{$user['firstname']} {$user['lastname']}"]]];
                }
            } elseif ($key == 'fulltext' && !empty($value['fields']['fulltext'][0])) {
                $query[] = ['identifier' => 'fulltext', 'values' => $value['fields']['fulltext'][0]];
            } elseif ($key == 'multifield' && !empty($value['fields']['multifield'][0])) {
                $query[] = ['identifier' => 'searchTerm', 'values' => $value['fields']['multifield'][0]];
            } elseif ($key == 'destinataire' && !empty($value['fields']['destinataire_chosen'])) {
                $allUsers = [];
                foreach ($value['fields']['destinataire_chosen'] as $field) {
                    $user = \User\models\UserModel::getByLogin(['login' => $field, 'select' => ['id', 'firstname', 'lastname']]);
                    if (!empty($user)) {
                        $allUsers[] = ['id' => $user['id'], 'type' => 'user', 'label' => "{$user['firstname']} {$user['lastname']}"];
                    }
                }
                $query[] = ['identifier' => 'role_dest', 'values' => $allUsers];
            } elseif ($key == 'category' && !empty($value['fields']['category'][0])) {
                $query[] = ['identifier' => 'category', 'values' => [['id' => $value['fields']['category'][0], 'label' => \Resource\models\ResModel::getCategoryLabel(['categoryId' => $value['fields']['category'][0]])]]];
            } elseif ($key == 'confidentiality' && !empty($value['fields']['confidentiality'][0])) {
                $query[] = ['identifier' => 'confidentiality', 'values' => [['id' => $value['fields']['confidentiality'][0] == 'Y', 'label' => $value['fields']['confidentiality'][0] == 'Y' ? 'Oui' : 'Non']]];
            } elseif ($key == 'creation_date') {
                $query[] = ['identifier' => 'creationDate', 'values' => ['start' => getFormattedDate($value['fields']['creation_date_from'][0]), 'end' => getFormattedDate($value['fields']['creation_date_to'][0])]];
            } elseif ($key == 'doc_date') {
                $query[] = ['identifier' => 'documentDate', 'values' => ['start' => getFormattedDate($value['fields']['doc_date_from'][0]), 'end' => getFormattedDate($value['fields']['doc_date_to'][0])]];
            } elseif ($key == 'admission_date') {
                $query[] = ['identifier' => 'arrivalDate', 'values' => ['start' => getFormattedDate($value['fields']['admission_date_from'][0]), 'end' => getFormattedDate($value['fields']['admission_date_to'][0])]];
            } elseif ($key == 'exp_date') {
                $query[] = ['identifier' => 'departureDate', 'values' => ['start' => getFormattedDate($value['fields']['exp_date_from'][0]), 'end' => getFormattedDate($value['fields']['exp_date_to'][0])]];
            } elseif ($key == 'process_limit_date') {
                $query[] = ['identifier' => 'processLimitDate', 'values' => ['start' => getFormattedDate($value['fields']['process_limit_date_from'][0]), 'end' => getFormattedDate($value['fields']['process_limit_date_to'][0])]];
            } elseif ($key == 'destination_mu' && !empty($value['fields']['services_chosen'])) {
                $allEntities = [];
                foreach ($value['fields']['services_chosen'] as $field) {
                    $entity = \Entity\models\EntityModel::getByEntityId(['entityId' => $field, 'select' => ['id']]);
                    $allEntities[] = ['id' => $entity['id'], 'title' => '', 'label' => ''];
                }
                $query[] = ['identifier' => 'destination', 'values' => $allEntities];
            } elseif ($key == 'initiator_mu' && !empty($value['fields']['initiatorServices_chosen'])) {
                $allEntities = [];
                foreach ($value['fields']['initiatorServices_chosen'] as $field) {
                    $entity = \Entity\models\EntityModel::getByEntityId(['entityId' => $field, 'select' => ['id']]);
                    $allEntities[] = ['id' => $entity['id'], 'title' => '', 'label' => ''];
                }
                $query[] = ['identifier' => 'initiator', 'values' => $allEntities];
            } elseif ($key == 'tag_mu' && !empty($value['fields']['tags_chosen'])) {
                $allTags = [];
                foreach ($value['fields']['tags_chosen'] as $field) {
                    $tag = \Tag\models\TagModel::getById(['id' => $field, 'select' => ['label', 'id']]);
                    if (!empty($tag)) {
                        $allTags[] = ['id' => $tag['id'], 'label' => $tag['label']];
                    }
                }
                $query[] = ['identifier' => 'tags', 'values' => $allTags];
            } elseif ($key == 'status' && !empty($value['fields']['status_chosen'])) {
                $allStatuses = [];
                foreach ($value['fields']['status_chosen'] as $field) {
                    $status = \Status\models\StatusModel::getById(['select' => ['identifier', 'label_status'], 'id' => $field]);
                    if (!empty($status)) {
                        $allStatuses[] = ['id' => $status['identifier'], 'label' => $status['label_status']];
                    }
                }
                $query[] = ['identifier' => 'status', 'values' => $allStatuses];
            } elseif ($key == 'visa_user' && !empty($value['fields']['visa_user'][0])) {
                $user = \User\models\UserModel::getByLogin(['login' => $value['fields']['visa_user'][0], 'select' => ['id', 'firstname', 'lastname']]);
                if (!empty($user)) {
                    $query[] = ['identifier' => 'role_visa', 'values' => [['id' => $user['id'], 'type' => 'user', 'label' => "{$user['firstname']} {$user['lastname']}"]]];
                }
            } elseif ($key == 'numged' && !empty($value['fields']['numged'][0])) {
                $query[] = ['identifier' => 'resId', 'values' => ['start' => $value['fields']['numged'][0], 'end' => $value['fields']['numged'][0]]];
            }
        }

        if (!empty($query)) {
            $user = \User\models\UserModel::getByLogin(['login' => $savedQuery['user_id'], 'select' => ['id']]);

            \Search\models\SearchTemplateModel::create([
                'user_id'       => !empty($user['id']) ? $user['id'] : $masterOwnerId,
                'label'         => $savedQuery['query_name'],
                'query'         => json_encode($query)
            ]);

            $migrated++;
        }
    }

    printf("Migration recherches sauvegardés (CUSTOM {$custom}) : {$migrated} sauvegarde(s) trouvée(s) et migrée(s).\n");
}

function getFormattedDate(string $date)
{
    if (empty($date)) {
        return null;
    }
    $date = new \DateTime($date);
    $date->setTime(00, 00, 00);

    return $date->format('Y-m-d');
}
