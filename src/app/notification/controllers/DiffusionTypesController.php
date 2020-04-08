<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Notifications Controller
 *
 * @author dev@maarch.org
 * @ingroup notifications
 */

namespace Notification\controllers;

use Notification\models\NotificationModel;
use SrcCore\models\DatabaseModel;
use User\models\UserModel;

class DiffusionTypesController
{
    public static function getRecipients($args = [])
    {
        $diffusionTypes = NotificationModel::getDiffusionType();
        foreach ($diffusionTypes as $diffusionType) {
            if ($diffusionType['id'] == $args['notification']['diffusion_type']) {
                $function = $diffusionType['function'];
                break;
            }
        }
        $recipients = DiffusionTypesController::$function(['request' => $args['request'], 'notification' => $args['notification']]);
        return $recipients;
    }

    public static function getRecipientsByContact($args = [])
    {
        if ($args['request'] == 'recipients') {
            $contactsMatch = DatabaseModel::select([
                'select'    => ['contact.id as user_id', 'contact.email as mail'],
                'table'     => ['resource_contacts', 'contacts'],
                'left_join' => ['resource_contacts.item_id = contacts.id'],
                'where'     => ['res_id = ?', 'type = ?', 'mode = ?'],
                'data'      => [$args['event']['record_id'], 'contact', 'sender']
            ]);
            return $contactsMatch;
        } else {
            return [];
        }
    }

    public static function getRecipientsByCopie($args = [])
    {
        switch ($request) {
            case 'recipients':
                $recipients = array();
                $dbRecipients = new Database();
        
                // Copy to users
                $select = 'SELECT distinct us.*';
                $from = ' FROM listinstance li '
                    .' JOIN users us ON li.item_id = us.user_id';
                $where = " WHERE li.coll_id = 'letterbox_coll'   AND li.item_mode = 'cc'"
                    ." AND item_type='user_id'";
        
                $arrayPDO = array(':recordid' => $event->record_id);
        
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id'
                            .' AND ('
                                .' notes.id not in (SELECT DISTINCT note_id FROM note_entities) '
                                .' OR us.user_id IN (SELECT ue.user_id FROM note_entities ne JOIN users_entities ue ON ne.item_id = ue.entity_id WHERE ne.note_id = :recordid)'
                            .')'
                        ;
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= " AND listinstance_id = :recordid AND lb.status not in ('INIT', 'AVAL') AND li.item_id <> :userid";
                        $arrayPDO = array_merge($arrayPDO, array(':userid' => $event->user_id));
                }
        
                $query = $select.$from.$where;
        
                $stmt = $dbRecipients->query($query, $arrayPDO);
        
                while ($recipient = $stmt->fetchObject()) {
                    $recipients[] = $recipient;
                }
        
                $arrayPDO = array(':recordid' => $event->record_id);
                // Copy to entities
                $select = 'SELECT distinct us.*';
                $from = ' FROM listinstance li '
                    .' LEFT JOIN users_entities ue ON li.item_id = ue.entity_id '
                    .' JOIN users us ON ue.user_id = us.user_id';
                $where = " WHERE li.coll_id = 'letterbox_coll'   AND li.item_mode = 'cc'"
                    ." AND item_type='entity_id'";
        
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id'
                            .' AND ('
                                .' notes.id not in (SELECT DISTINCT note_id FROM note_entities) '
                                .' OR us.user_id IN (SELECT ue.user_id FROM note_entities ne JOIN users_entities ue ON ne.item_id = ue.entity_id WHERE ne.note_id = :recordid)'
                            .')'
                        ;
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        break;
        
                    case 'listinstance':
                    default:
                        $where .= ' AND listinstance_id = :recordid';
                }
        
                $query = $select.$from.$where;
        
                $stmt = $dbRecipients->query($query, $arrayPDO);
        
                while ($recipient = $stmt->fetchObject()) {
                    $recipients[] = $recipient;
                }
                break;
        
            case 'res_id':
                $arrayPDO = array(':recordid' => $event->record_id);
                $select = 'SELECT li.res_id';
                $from = ' FROM listinstance li';
                $where = " WHERE li.coll_id = 'letterbox_coll'   ";
        
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id';
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        break;
        
                    case 'listinstance':
                    default:
                        $where .= ' AND listinstance_id = :recordid';
                }
        
                $query = $query = $select.$from.$where;
        
                $dbResId = new Database();
                $stmt = $dbResId->query($query, $arrayPDO);
                $res_id_record = $stmt->fetchObject();
                $res_id = $res_id_record->res_id;
                break;
        }
    }

    public static function getRecipientsByDestEntity($args = [])
    {
        switch ($request) {
            case 'recipients':
                $recipients = array();
                $dbRecipients = new Database();
        
                $select = 'SELECT distinct en.entity_id, en.enabled, en.email AS mail';
                $from = ' FROM res_view_letterbox rvl JOIN entities en ON rvl.destination = en.entity_id';
                $where = ' WHERE rvl.res_id = :recordid';
        
                $arrayPDO = array(':recordid' => $event->record_id);
        
                $query = $select.$from.$where;
        
                $stmt = $dbRecipients->query($query, $arrayPDO);
        
                while ($recipient = $stmt->fetchObject()) {
                    $recipients[] = $recipient;
                }
                break;
        
            case 'res_id':
                $select = 'SELECT li.res_id';
                $from = ' FROM listinstance li';
                $where = " WHERE li.coll_id = 'letterbox_coll'   ";
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $query = $select.$from.$where;
        
                $dbResId = new Database();
                $stmt = $dbResId->query($query, $arrayPDO);
                $res_id_record = $stmt->fetchObject();
                $res_id = $res_id_record->res_id;
                break;
        }
    }

    public static function getRecipientsByDestUserSign($args = [])
    {
        switch ($request) {
            case 'recipients':
                $recipients = array();
                $dbRecipients = new Database();
        
                $select = 'SELECT distinct us.*';
                $from = ' FROM listinstance li JOIN users us ON li.item_id = us.user_id';
                $where = " WHERE li.coll_id = 'letterbox_coll' AND li.item_mode = 'sign' "
                    .'and process_date IS NULL ';
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id'
                            .' AND ('
                                .' notes.id not in (SELECT DISTINCT note_id FROM note_entities) '
                                .' OR us.user_id IN (SELECT ue.user_id FROM note_entities ne JOIN '
                                .' users_entities ue ON ne.item_id = ue.entity_id WHERE ne.note_id = :recordid)'
                            .')';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
        
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $select.$from.$where;
        
                $stmt = $dbRecipients->query($query, $arrayPDO);
        
                while ($recipient = $stmt->fetchObject()) {
                    $recipients[] = $recipient;
                }
                break;
        
            case 'res_id':
                $select = 'SELECT li.res_id';
                $from = ' FROM listinstance li';
                $where = " WHERE li.coll_id = 'letterbox_coll'   ";
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $query = $select.$from.$where;
        
                $dbResId = new Database();
                $stmt = $dbResId->query($query, $arrayPDO);
                $res_id_record = $stmt->fetchObject();
                $res_id = $res_id_record->res_id;
                break;
        }
    }

    public static function getRecipientsByDestUserVisa($args = [])
    {
        switch ($request) {
            case 'recipients':
                $recipients = array();
                $dbRecipients = new Database();
        
                $select = 'SELECT distinct us.*';
                $from = ' FROM listinstance li JOIN users us ON li.item_id = us.user_id';
                $where = " WHERE li.coll_id = 'letterbox_coll' AND li.item_mode = 'visa' "
                    .'and process_date IS NULL ';
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id'
                            .' AND ('
                                .' notes.id not in (SELECT DISTINCT note_id FROM note_entities) '
                                .' OR us.user_id IN (SELECT ue.user_id FROM note_entities ne JOIN '
                                .' users_entities ue ON ne.item_id = ue.entity_id WHERE ne.note_id = :recordid)'
                            .')';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
        
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $select.$from.$where;
        
                $stmt = $dbRecipients->query($query, $arrayPDO);
        
                while ($recipient = $stmt->fetchObject()) {
                    $recipients[] = $recipient;
                }
                break;
        
            case 'res_id':
                $select = 'SELECT li.res_id';
                $from = ' FROM listinstance li';
                $where = " WHERE li.coll_id = 'letterbox_coll'   ";
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $query = $select.$from.$where;
        
                $dbResId = new Database();
                $stmt = $dbResId->query($query, $arrayPDO);
                $res_id_record = $stmt->fetchObject();
                $res_id = $res_id_record->res_id;
                break;
        }
    }

    public static function getRecipientsByDestUser($args = [])
    {
        switch ($request) {
            case 'recipients':
                $recipients = array();
                $dbRecipients = new Database();
        
                $select = 'SELECT distinct us.*';
                $from = ' FROM listinstance li JOIN users us ON li.item_id = us.user_id';
                $where = " WHERE li.item_mode = 'dest'";
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' AND notes.id = :recordid AND us.id != notes.user_id'
                            .' AND ('
                                .' notes.id not in (SELECT DISTINCT note_id FROM note_entities) '
                                .' OR us.user_id IN (SELECT ue.user_id FROM note_entities ne JOIN users_entities ue ON ne.item_id = ue.entity_id WHERE ne.note_id = :recordid)'
                            .')';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
        
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' AND listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $select.$from.$where;
        
                $stmt = $dbRecipients->query($query, $arrayPDO);
        
                while ($recipient = $stmt->fetchObject()) {
                    $recipients[] = $recipient;
                }
                break;
        
            case 'res_id':
                $select = 'SELECT li.res_id';
                $from = ' FROM listinstance li JOIN users us ON li.item_id = us.user_id';
                $where = " WHERE ";
        
                $arrayPDO = array(':recordid' => $event->record_id);
                switch ($event->table_name) {
                    case 'notes':
                        $from .= ' JOIN notes ON notes.identifier = li.res_id';
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                        $where .= ' notes.id = :recordid AND us.id != notes.user_id';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'res_letterbox':
                    case 'res_view_letterbox':
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' lb.res_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                        break;
        
                    case 'listinstance':
                    default:
                        $from .= ' JOIN res_letterbox lb ON lb.res_id = li.res_id';
                        $where .= ' listinstance_id = :recordid';
                        if ($notification->diffusion_properties != '') {
                            $status_tab = explode(',', $notification->diffusion_properties);
                            $where .= ' AND lb.status in (:statustab)';
                            $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                        }
                }
        
                $query = $query = $select.$from.$where;
        
                $dbResId = new Database();
                $stmt = $dbResId->query($query, $arrayPDO);
                $res_id_record = $stmt->fetchObject();
                $res_id = $res_id_record->res_id;
                break;
        }
    }

    public static function getRecipientsByEntity($args = [])
    {
        if ($args['request'] == 'recipients') {
            $aEntities  = explode(",", $args['notification']['diffusion_properties']);
            $recipients = DatabaseModel::select([
                'select'    => ['users.*'],
                'table'     => ['users_entities, users'],
                'where'     => ['users_entities.entity_id in (?)', 'users_entities.user_id = users.id', 'users.status != ?'],
                'data'      => [$aEntities, 'DEL']
            ]);
            return $recipients;
        } else {
            return [];
        }
    }

    public static function getRecipientsByGroup($args = [])
    {
        if ($args['request'] == 'recipients') {
            $aGroups  = explode(",", $args['notification']['diffusion_properties']);
            $recipients = DatabaseModel::select([
                'select'    => ['us.*'],
                'table'     => ['usergroup_content ug, users us, usergroups'],
                'where'     => ['us.id = ug.user_id', 'ug.group_id = usergroups.id', 'usergroups.group_id in (?)', 'us.status != ?'],
                'data'      => [$aGroups, 'DEL']
            ]);
            return $recipients;
        } else {
            return [];
        }
    }

    public static function getRecipientsByUser($args = [])
    {
        if ($args['request'] == 'recipients') {
            $aUsers     = explode(",", $args['notification']['diffusion_properties']);
            $recipients = UserModel::get(['select' => ['*'], 'where' => ['id in (?)'], 'data' => [$aUsers]]);
            return $recipients;
        } else {
            return [];
        }
    }
}
