<?php

/*
*   Copyright 2008-2017 Maarch
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
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__ . '/DOMTemplateProcessor.php';

class ArchiveTransferReply {

    public function __construct()
    {
    }

    public function send($data, $resIds)
    {
        //$xml = simplexml_load_file($fileName);
        $messageObject = new stdClass();

        if ($data->comments) {
            $messageObject->comment = [];
            if (is_array($data->comments)) {
                foreach ($data->comments as $comment) {
                    $messageObject->comment[] = $comment;
                }
            } else {
                $messageObject->comment[] = $data->comments;
            }
        }

        $messageObject->date = $data->date;
        $messageObject->messageIdentifier =  new stdClass();
        $messageObject->messageIdentifier->value = $data->reference;

        $messageObject->messageReceivedIdentifier =  new stdClass();
        $messageObject->messageReceivedIdentifier->value = $data->requestReference;

        $messageObject->sender = new stdClass();
        $messageObject->sender->identifier = new stdClass();
        $messageObject->sender->identifier->value = $data->senderOrgRegNumber;

        $messageObject->receiver = new stdClass();
        $messageObject->receiver->identifier = new stdClass();
        $messageObject->receiver->identifier->value = $data->recipientOrgRegNumber;

        $this->saveXml($messageObject);

        foreach ($resIds as $resId) {
            $this->addAttachment($messageObject->messageIdentifier->value, $resId, $messageObject->messageIdentifier->value.".txt", "txt", "Accusé de reception");
        }
    }

}