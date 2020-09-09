<?php

/*
 *   Copyright 2008-2015 Maarch
 *
 *   This file is part of Maarch Framework.
 *
 *   Maarch Framework is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Maarch Framework is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Maarch Framework. If not, see <http://www.gnu.org/licenses/>.
 */

/*
* @requires
*   $res_view   = Name of res view
*   $maarchApps = name of app
*   $maarchUrl  = Url to maarch (root url)
*   $recipient  = recipient of notification
*   $events     = array of events related to letterbox mails
*
* @returns
    [res_letterbox] = record of view + link to detail/doc page
*/

use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Resource\models\ResourceContactModel;
use User\models\UserModel;

$dbDatasource = new Database();
//$contacts = new contacts_v2();

$datasources['recipient'][0] = (array) $recipient;

$datasources['res_letterbox'] = array();
$datasources['contact'] = array();

foreach ($events as $event) {
    $res = array();
    $arrayPDO = array();

    $select = 'SELECT lb.*';
    $from = ' FROM '.$res_view.' lb ';
    $where = ' WHERE ';

    switch ($event->table_name) {
    case 'notes':
        $from .= ' JOIN notes ON notes.identifier = lb.res_id';
        $where .= ' notes.id = ? ';
        $arrayPDO = array_merge($arrayPDO, array($event->record_id));
        break;

    case 'listinstance':
        $from .= ' JOIN listinstance li ON lb.res_id = li.res_id';
        $where .= ' listinstance_id = ? ';
        $arrayPDO = array_merge($arrayPDO, array($coll_id, $event->record_id));
        break;

    case 'res_letterbox':
    case 'res_view_letterbox':
    default:
        $where .= ' lb.res_id = ? ';
        $arrayPDO = array_merge($arrayPDO, array($event->record_id));
    }

    $query = $select.$from.$where;

    if ($GLOBALS['logger']) {
        $GLOBALS['logger']->write($query, 'DEBUG');
    }

    // Main document resource from view
    $stmt = $dbDatasource->query($query, $arrayPDO);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    // Lien vers la page detail
    $urlToApp = trim($maarchUrl, '/').'/apps/'.trim($maarchApps, '/').'/index.php?';

    $user   = \User\models\UserModel::getByLogin(['login' => $datasources['recipient'][0]['user_id'], 'select' => ['id']]);
    $basket = \Basket\models\BasketModel::getByBasketId(['select' => ['id'], 'basketId' => 'MyBasket']);
    $preferenceBasket = \User\models\UserBasketPreferenceModel::get([
        'select'  => ['group_serial_id'],
        'where'   => ['user_serial_id = ?', 'basket_id = ?'],
        'data'    => [$user['id'], 'MyBasket']
    ]);

    $res['linktodoc']     = $urlToApp . 'linkToDoc='.$res['res_id'];
    $res['linktodetail']  = $urlToApp . 'linkToDetail='.$res['res_id'];
    if (!empty($res['res_id']) && !empty($preferenceBasket[0]['group_serial_id']) && !empty($basket['id']) && !empty($user['id'])) {
        $res['linktoprocess'] = $urlToApp . 'linkToProcess='.$res['res_id'].'&groupId='.$preferenceBasket[0]['group_serial_id'].'&basketId='.$basket['id'].'&userId='.$user['id'];
    }

    $stmt2 = $dbDatasource->query('SELECT * FROM entities WHERE entity_id = ? ', array($res['initiator']));
    $initiator = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (is_array($initiator) && !empty($initiator)) {
        foreach (array_keys($initiator) as $value) {
            $res['initiator_'.$value] = $initiator[$value];
        }
    }

    if (!empty($res['typist'])) {
        $userInfo      = UserModel::getById(['select' => ['firstname', 'lastname'], 'id' => $res['typist']]);
        $res['typist'] = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
    }

    // Insertion
    $datasources['res_letterbox'][] = $res;

    $resourceContacts = ResourceContactModel::get([
        'where' => ['res_id = ?', "mode='sender'", "type='contact'"],
        'data'  => [$res['res_id']],
        'limit' => 1
    ]);
    $resourceContacts = $resourceContacts[0];

    $contact = [];
    if (!empty($resourceContacts)) {
        $contact = ContactModel::getById(['id' => $resourceContacts['item_id'], 'select' => ['*']]);

        $postalAddress = ContactController::getContactAfnor($contact);
        unset($postalAddress[0]);
        $contact['postal_address'] = implode("\n", $postalAddress);
    }
    $datasources['contact'][] = $contact;
}

$datasources['images'][0]['imgdetail'] = $maarchUrl.'/apps/'.$maarchApps.'/img/object.gif';
$datasources['images'][0]['imgdoc'] = $maarchUrl.'/apps/'.$maarchApps.'/img/picto_dld.gif';
