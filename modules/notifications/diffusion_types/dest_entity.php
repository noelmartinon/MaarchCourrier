<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   dest_entity
*
* @author  dev <dev@maarch.org>
* @ingroup notifications
*/
switch ($request) {
    case 'recipients':
        $recipients = array();
        $dbRecipients = new Database();

        $select = 'SELECT distinct en.entity_id, en.enabled, en.email AS mail';
        $from = ' FROM res_view_letterbox rvl JOIN entities en ON rvl.destination = en.entity_id';
        $where = ' WHERE rvl.res_id = :recordid';

        $arrayPDO = array(':recordid' => $event->record_id);

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
        $where = " WHERE li.coll_id = 'letterbox_coll'   ";

        $arrayPDO = array(':recordid' => $event->record_id);
        switch ($event->table_name) {
            case 'notes':
                $from .= ' JOIN notes ON notes.identifier = li.res_id';
                $from .= ' JOIN res_letterbox lb ON lb.res_id = notes.identifier';
                $where .= ' AND notes.id = :recordid AND li.item_id != notes.user_id';
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
