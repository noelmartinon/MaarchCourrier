<?php
/*
* @requires
*   $res_view	= Name of res view
*   $maarchUrl	= Url to maarch (root url)
* 	$recipient	= recipient of notification
*	$events 	= array of events related to letterbox mails
*
* @returns
    [notes] = detail of notes added
*/

use Basket\models\BasketModel;
use Contact\models\ContactModel;
use Note\models\NoteModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use User\models\UserModel;

$datasources['recipient'][0] = (array)$recipient;
$datasources['notes'] = array();

// Link to detail page
$urlToApp = trim($maarchUrl, '/').'/apps/maarch_entreprise/index.php?';

$basket = BasketModel::getByBasketId(['select' => ['id'], 'basketId' => 'MyBasket']);
$preferenceBasket = UserBasketPreferenceModel::get([
    'select'  => ['group_serial_id'],
    'where'   => ['user_serial_id = ?', 'basket_id = ?'],
    'data'    => [$recipient['user_id'], 'MyBasket']
]);

foreach ($events as $event) {
    $note = [];
    
    if ($event['table_name'] != 'notes') {
        $note = DatabaseModel::select([
            'select'    => ['mlb.*', 'notes.*', 'users.*'],
            'table'     => ['listinstance', $res_view . ' mlb', 'notes', 'users'],
            'left_join' => ['mlb.res_id = li.res_id', 'notes.identifier = li.res_id', 'users.id = notes.user_id'],
            'where'     => ['li.item_id = ?', 'li.item_mode = \'dest\'', 'li.item_type = \'user_id\'', 'li.res_id = ?'],
            'data'      => [$recipient['user_id'], $event['record_id']],
        ])[0];
        $resId = $note['identifier'];
    } else {
        $note         = NoteModel::getById(['id' => $event['record_id']]);
        $resId        = $note['identifier'];
        $resLetterbox = ResModel::getById(['select' => ['*'], 'resId'  => $resId]);
        $datasources['res_letterbox'][] = $resLetterbox;
    }

    $note['linktodoc']     = $urlToApp . 'linkToDoc='.$resId;
    $note['linktodetail']  = $urlToApp . 'linkToDetail='.$resId;

    if (!empty($resId) && !empty($preferenceBasket[0]['group_serial_id']) && !empty($basket['id']) && !empty($recipient['user_id'])) {
        $note['linktoprocess'] = $urlToApp . 'linkToProcess='.$resId.'&groupId='.$preferenceBasket[0]['group_serial_id'].'&basketId='.$basket['id'].'&userId='.$recipient['user_id'];
    }

    $resourceContacts = ResourceContactModel::get([
        'where' => ['res_id = ?', "type = 'contact'", "mode = 'sender'"],
        'data'  => [$resId],
        'limit' => 1
    ]);
    $resourceContacts = $resourceContacts[0];

    if ($event['table_name'] == 'notes') {
        $datasources['res_letterbox'][0]['linktodoc']     = $note['linktodoc'];
        $datasources['res_letterbox'][0]['linktodetail']  = $note['linktodetail'];
        $datasources['res_letterbox'][0]['linktoprocess'] = $note['linktodoc'];

        $labelledUser = UserModel::getLabelledUserById(['id' => $note['user_id']]);
        $creationDate = TextFormatModel::formatDate($note['creation_date'], 'd/m/Y');
        $note = "{$labelledUser}  {$creationDate} : {$note['note_text']}\n";
    }

    if (!empty($resourceContacts)) {
        $contact = ContactModel::getById(['id' => $resourceContacts['item_id'], 'select' => ['*']]);
        $datasources['sender'][] = $contact;
    }
    
    $datasources['notes'] = $note;
}
