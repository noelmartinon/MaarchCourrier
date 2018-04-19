<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   saveTransmissionContact
*
* @author  dev <dev@maarch.org>
* @ingroup attachment
*/
unset($_SESSION['transmissionContacts']);

if (isset($_POST['transmissionContactidAttach']) && !empty($_POST['transmissionContactidAttach'])) {
    $db = new Database();

    foreach ($_POST['transmissionContactidAttach'] as $key => $contactId) {
        if (is_numeric($_POST['transmissionContactidAttach'][$key]['val'])) {
            if (isset($_POST['transmissionAddressidAttach'][$key]['val'])) {
                $stmt = $db->query('SELECT * FROM view_contacts WHERE contact_id = ? AND ca_id = ?', [$_POST['transmissionContactidAttach'][$key]['val'], $_POST['transmissionAddressidAttach'][$key]['val']]);
            } else {
                $stmt = $db->query('SELECT * FROM view_contacts WHERE contact_id = ?', [$_POST['transmissionContactidAttach'][$key]['val']]);
            }
        } else {
            $stmt = $db->query('SELECT firstname, lastname, user_id, mail, phone, initials FROM users WHERE user_id = ?', [$_POST['transmissionContactidAttach'][$key]['val']]);
        }

        $contact = $stmt->fetchObject();
        foreach ($contact as $column => $value) {
            $_SESSION['transmissionContacts'][$_POST['transmissionAddressidAttach'][$key]['index']][$column] = $value;
        }
    }
}
