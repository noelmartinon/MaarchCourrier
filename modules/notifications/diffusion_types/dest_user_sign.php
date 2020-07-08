<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   dest_user_sign
*
* @author  dev <dev@maarch.org>
* @ingroup notifications
*/
switch ($request) {
    case 'recipients':
        $recipients = array();
        $dbRecipients = new Database();

        $select = 'SELECT distinct us.*';
        $from = ' FROM listinstance li JOIN users us ON li.item_id = us.user_id';
        $where = " WHERE AND li.item_mode = 'sign' "
            .'and process_date IS NULL ';

        $arrayPDO = array(':recordid' => $event->record_id);
        switch ($event->table_name) {
            case 'notes':
                $from .= ' JOIN notes ON notes.identifier = li.res_id';
                $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                $where .= ' AND notes.id = :recordid AND li.item_id != (SELECT user_id FROM users WHERE id = notes.user_id)'
                    .' AND ('
                        .' notes.id not in (SELECT DISTINCT note_id FROM note_entities) '
                        .' OR us.user_id IN (SELECT ue.user_id FROM note_entities ne JOIN '
                        .' users_entities ue ON ne.item_id = ue.entity_id WHERE ne.note_id = :recordid)'
                    .')';
                if ($notification->diffusion_properties != '') {
                    $status_tab = explode(',', $notification->diffusion_properties);
                    // $status_str=implode("','",$status_tab);
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
                    // $status_str=implode("','",$status_tab);
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
                    // $status_str=implode("','",$status_tab);
                    $where .= ' AND lb.status in (:statustab)';
                    $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                }
        }

        $query = $select.$from.$where;

        if ($GLOBALS['logger']) {
            $GLOBALS['logger']->write($query, 'DEBUG');
        }
        $stmt = $dbRecipients->query($query, $arrayPDO);

        while ($recipient = $stmt->fetchObject()) {
            $recipients[] = $recipient;
        }
        break;

    case 'attach':
        $attach = false;
        break;

    case 'res_id':
        $select = 'SELECT li.res_id';
        $from = ' FROM listinstance li';
        $where = " WHERE (1=1)   ";

        $arrayPDO = array(':recordid' => $event->record_id);
        switch ($event->table_name) {
            case 'notes':
                $from .= ' JOIN notes ON notes.identifier = li.res_id';
                $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                $where .= ' AND notes.id = :recordid AND li.item_id != (SELECT user_id FROM users WHERE id = notes.user_id)';
                if ($notification->diffusion_properties != '') {
                    $status_tab = explode(',', $notification->diffusion_properties);
                    // $status_str=implode("','",$status_tab);
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
                    // $status_str=implode("','",$status_tab);
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
                    // $status_str=implode("','",$status_tab);
                    $where .= ' AND lb.status in (:statustab)';
                    $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
                }
        }

        $query = $query = $select.$from.$where;

        if ($GLOBALS['logger']) {
            $GLOBALS['logger']->write($query, 'DEBUG');
        }
        $dbResId = new Database();
        $stmt = $dbResId->query($query, $arrayPDO);
        $res_id_record = $stmt->fetchObject();
        $res_id = $res_id_record->res_id;
        break;
}
