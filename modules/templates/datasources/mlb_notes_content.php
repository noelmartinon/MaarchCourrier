<?php
/*
* @requires
*   $res_view	= Name of res view
*   $maarchApps = name of app
*   $maarchUrl	= Url to maarch (root url)
* 	$recipient	= recipient of notification
*	$events 	= array of events related to letterbox mails
*
* @returns
    [notes] = detail of notes added
*/

use Contact\models\ContactModel;
use Note\models\NoteModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use User\models\UserModel;

$dbDatasource = new Database();

$datasources['recipient'][0] = (array)$recipient;

$datasources['notes'] = array();

foreach ($events as $event) {
    $note = array();
    
    // Query
    switch ($event->table_name) {
        case 'notes':
            $query = "SELECT mlb.*, notes.*, users.* "
                . "FROM " . $res_view . " mlb "
                . "JOIN notes on notes.identifier = mlb.res_id "
                . "JOIN users on users.id = notes.user_id "
                . "WHERE notes.id = ? ";
            $arrayPDO = array($event->record_id);
            break;
        
        case "res_letterbox":
        case "res_view_letterbox":
            $query = "SELECT mlb.*, "
                . "notes.*, "
                . "users.* "
                . "FROM listinstance li JOIN " . $res_view . " mlb ON mlb.res_id = li.res_id "
                . "JOIN notes on notes.identifier = li.res_id "
                . "JOIN users on users.id = notes.user_id "
                . "WHERE li.item_id = ? "
                . "AND li.item_mode = 'dest' "
                . "AND li.item_type = 'user_id' "
                . "AND li.res_id = ? ";
            $arrayPDO = array($recipient->user_id, $event->record_id);
            break;
    }
    
    if ($GLOBALS['logger']) {
        $GLOBALS['logger']->write($query, 'DEBUG');
    }
    
    $stmt = $dbDatasource->query($query, $arrayPDO);

    if ($event->table_name != 'notes') {
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        $resId = $note['identifier'];
    } else {
        $note = NoteModel::getById(['id' => $event->record_id]);
        $resId = $note['identifier'];
        $resLetterbox = ResModel::getById([
            'select' => ['*'],
            'resId'  => $resId
        ]);
        $datasources['res_letterbox'][] = $resLetterbox;
    }
    
    // Lien vers la page détail
    $urlToApp = trim($maarchUrl, '/').'/apps/'.trim($maarchApps, '/').'/index.php?';

    $user   = \User\models\UserModel::getByLogin(['login' => $datasources['recipient'][0]['user_id'], 'select' => ['id']]);
    $basket = \Basket\models\BasketModel::getByBasketId(['select' => ['id'], 'basketId' => 'MyBasket']);
    $preferenceBasket = \User\models\UserBasketPreferenceModel::get([
        'select'  => ['group_serial_id'],
        'where'   => ['user_serial_id = ?', 'basket_id = ?'],
        'data'    => [$user['id'], 'MyBasket']
    ]);

    $note['linktodoc']     = $urlToApp . 'linkToDoc='.$resId;
    $note['linktodetail']  = $urlToApp . 'linkToDetail='.$resId;

    if (!empty($resId) && !empty($preferenceBasket[0]['group_serial_id']) && !empty($basket['id']) && !empty($user['id'])) {
        $note['linktoprocess'] = $urlToApp . 'linkToProcess='.$resId.'&groupId='.$preferenceBasket[0]['group_serial_id'].'&basketId='.$basket['id'].'&userId='.$user['id'];
    }

    if (!empty($note['user_id'])) {
        $userInfo        = UserModel::getById(['select' => ['firstname', 'lastname'], 'id' => $note['user_id']]);
        $note['user_id'] = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
    }

    $resourceContacts = ResourceContactModel::get([
        'where' => ['res_id = ?', "type = 'contact'", "mode = 'sender'"],
        'data'  => [$resId],
        'limit' => 1
    ]);
    $resourceContacts = $resourceContacts[0];

    if ($event->table_name == 'notes') {
        $datasources['res_letterbox'][0]['linktodoc'] = $note['linktodoc'];
        $datasources['res_letterbox'][0]['linktodetail'] = $note['linktodetail'];
        $datasources['res_letterbox'][0]['linktoprocess'] = $note['linktodoc'];
    }

    $contact = [];
    if (!empty($resourceContacts)) {
        $contact = ContactModel::getById(['id' => $resourceContacts['item_id'], 'select' => ['*']]);
    }
    $datasources['contact'][] = $contact;
    
    // Insertion
    $datasources['notes'][] = $note;
}

$datasources['images'][0]['imgdetail'] = str_replace('//', '/', $maarchUrl . '/apps/' . $maarchApps . '/img/object.gif');
$datasources['images'][0]['imgdoc'] = str_replace('//', '/', $maarchUrl . '/apps/' . $maarchApps . '/img/picto_dld.gif');
