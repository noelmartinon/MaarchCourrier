<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Controller
 * @author dev@maarch.org
 */

namespace Email\controllers;

use Attachment\controllers\AttachmentController;
use Attachment\models\AttachmentModel;
use Configuration\models\ConfigurationModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Email\models\EmailModel;
use Entity\models\EntityModel;
use Group\models\ServiceModel;
use History\controllers\HistoryController;
use History\models\HistoryModel;
use MessageExchange\models\MessageExchangeModel;
use Note\controllers\NoteController;
use Note\models\NoteEntityModel;
use Note\models\NoteModel;
use PHPMailer\PHPMailer\PHPMailer;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class EmailController
{
    public function send(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'sendmail', 'userId' => $GLOBALS['userId'], 'location' => 'sendmail', 'type' => 'use'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $isSent = EmailController::createEmail(['userId' => $user['id'], 'data' => $body]);

        if (!empty($isSent['errors'])) {
            $httpCode = empty($isSent['code']) ? 400 : $isSent['code'];
            return $response->withStatus($httpCode)->withJson(['errors' => $isSent['errors']]);
        }

        return $response->withStatus(204);
    }

    public static function createEmail(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'data']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::arrayType($args, ['data', 'options']);

        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);

        $check = EmailController::controlCreateEmail(['login' => $user['user_id'], 'data' => $args['data']]);
        if (!empty($check['errors'])) {
            return ['errors' => $check['errors'], 'code' => $check['code']];
        }

        $id = EmailModel::create([
            'userId'                => $args['userId'],
            'sender'                => json_encode($args['data']['sender']),
            'recipients'            => json_encode($args['data']['recipients']),
            'cc'                    => empty($args['data']['cc']) ? '[]' : json_encode($args['data']['cc']),
            'cci'                   => empty($args['data']['cci']) ? '[]' : json_encode($args['data']['cci']),
            'object'                => empty($args['data']['object']) ? null : $args['data']['object'],
            'body'                  => empty($args['data']['body']) ? null : $args['data']['body'],
            'document'              => empty($args['data']['document']) ? null : json_encode($args['data']['document']),
            'isHtml'                => $args['data']['isHtml'] ? 'true' : 'false',
            'status'                => $args['data']['status'] == 'DRAFT' ? 'DRAFT' : 'WAITING',
            'messageExchangeId'     => empty($args['data']['messageExchangeId']) ? null : $args['data']['messageExchangeId']
        ]);

        HistoryController::add([
            'tableName'    => 'emails',
            'recordId'     => $id,
            'eventType'    => 'ADD',
            'eventId'      => 'emailCreation',
            'info'         => _EMAIL_ADDED
        ]);

        if (!empty($args['data']['document']['id'])) {
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $args['data']['document']['id'],
                'eventType' => 'ADD',
                'eventId'   => 'emailCreation',
                'info'      => _EMAIL_ADDED
            ]);
        }

        $isSent = ['success' => 'success'];
        if ($args['data']['status'] != 'DRAFT') {
            if ($args['data']['status'] == 'EXPRESS') {
                $isSent = EmailController::sendEmail(['emailId' => $id, 'userId' => $args['userId']]);
                if (!empty($isSent['success'])) {
                    EmailModel::update(['set' => ['status' => 'SENT', 'send_date' => 'CURRENT_TIMESTAMP'], 'where' => ['id = ?'], 'data' => [$id]]);
                } else {
                    EmailModel::update(['set' => ['status' => 'ERROR'], 'where' => ['id = ?'], 'data' => [$id]]);
                }
            } else {
                $customId = CoreConfigModel::getCustomId();
                if (empty($customId)) {
                    $customId = 'null';
                }
                $encryptKey = CoreConfigModel::getEncryptKey();
                $options = empty($args['options']) ? '' : serialize($args['options']);
                exec("php src/app/email/scripts/sendEmail.php {$customId} {$id} {$args['userId']} '{$encryptKey}' '{$options}' > /dev/null &");
            }
        }

        return $isSent;
    }

    public static function getById(array $args)
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);
        
        $emailArray  = EmailModel::getById(['id' => $args['id']]);
        $document      = (array)json_decode($emailArray['document']);

        if (!ResController::hasRightByResId(['resId' => [$document['id']], 'userId' => $GLOBALS['userId']])) {
            return ['errors' => 'Document out of perimeter', 'code' => 403];
        }

        $sender        = (array)json_decode($emailArray['sender']);
        $email['to']   = (array)json_decode($emailArray['recipients']);
        $email['cc']   = (array)json_decode($emailArray['cc']);
        $email['cci']  = (array)json_decode($emailArray['cci']);
        $email['id']    = $emailArray['id'];
        $email['resId'] = $document['id'];

        $user = UserModel::getById(['id' => $emailArray['user_id'], 'select' => ['user_id']]);
        $email['userId'] = $user['user_id'];

        $email['attachments'] = [];
        $email['attachments_version'] = [];

        if (!empty($document['attachments'])) {
            $document['attachments'] = (array)$document['attachments'];
            foreach ($document['attachments'] as $attachment) {
                $attachment = (array)$attachment;
                if ($attachment['isVersion']) {
                    $email['attachments_version'][] = $attachment['id'];
                } else {
                    $email['attachments'][] = $attachment['id'];
                }
            }
        }

        $email['notes'] = $document['notes'];

        $email['object']            = $emailArray['object'];
        $email['body']              = $emailArray['body'];
        $email['resMasterAttached'] = ($document['isLinked']) ? 'Y' : 'N';
        $email['isHtml']            = ($emailArray['is_html']) ? 'Y' : 'N';
        $email['status']            = $emailArray['status'];
        $email['creationDate']      = $emailArray['creation_date'];
        $email['sendDate']          = $emailArray['send_date'];

        if (!empty($sender['entityId'])) {
            $entity = EntityModel::getById(['select' => ['entity_id'], 'id' => $sender['entityId']]);
            $email['sender_email'] = $entity['entity_id'] . ',' . $sender['email'];
        } else {
            $email['sender_email'] = $sender['email'];
        }

        return $email;
    }

    public static function update(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'data', 'emailId']);
        ValidatorModel::intVal($args, ['userId', 'emailId']);
        ValidatorModel::arrayType($args, ['data', 'options']);

        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);

        $check = EmailController::controlCreateEmail(['login' => $user['user_id'], 'data' => $args['data']]);
        if (!empty($check['errors'])) {
            return ['errors' => $check['errors'], 'code' => $check['code']];
        }

        EmailModel::update([
            'set' => [
                'sender'      => json_encode($args['data']['sender']),
                'recipients'  => json_encode($args['data']['recipients']),
                'cc'          => empty($args['data']['cc']) ? '[]' : json_encode($args['data']['cc']),
                'cci'         => empty($args['data']['cci']) ? '[]' : json_encode($args['data']['cci']),
                'object'      => empty($args['data']['object']) ? null : $args['data']['object'],
                'body'        => empty($args['data']['body']) ? null : $args['data']['body'],
                'document'    => empty($args['data']['document']) ? null : json_encode($args['data']['document']),
                'is_html'     => $args['data']['isHtml'] ? 'true' : 'false',
                'status'      => $args['data']['status'] == 'DRAFT' ? 'DRAFT' : 'WAITING'
            ],
            'where' => ['id = ?'],
            'data' => [$args['emailId']]
        ]);

        HistoryController::add([
            'tableName'    => 'emails',
            'recordId'     => $args['emailId'],
            'eventType'    => 'UP',
            'eventId'      => 'emailModification',
            'info'         => _EMAIL_UPDATED
        ]);

        if (!empty($args['data']['document']['id'])) {
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $args['data']['document']['id'],
                'eventType' => 'UP',
                'eventId'   => 'emailModification',
                'info'      => _EMAIL_UPDATED
            ]);
        }

        if ($args['data']['status'] != 'DRAFT') {
            $customId = CoreConfigModel::getCustomId();
            if (empty($customId)) {
                $customId = 'null';
            }
            $encryptKey = CoreConfigModel::getEncryptKey();
            $options = empty($args['options']) ? '' : serialize($args['options']);
            exec("php src/app/email/scripts/sendEmail.php {$customId} {$args['emailId']} {$args['userId']} '{$encryptKey}' '{$options}' > /dev/null &");
        }

        return true;
    }

    public static function sendEmail(array $args)
    {
        ValidatorModel::notEmpty($args, ['emailId', 'userId']);
        ValidatorModel::intVal($args, ['emailId', 'userId']);

        $email = EmailModel::getById(['id' => $args['emailId']]);
        $email['sender']        = (array)json_decode($email['sender']);
        $email['recipients']    = json_decode($email['recipients']);
        $email['cc']            = json_decode($email['cc']);
        $email['cci']           = json_decode($email['cci']);

        $configuration = ConfigurationModel::getByService(['service' => 'admin_email_server', 'select' => ['value']]);
        $configuration = (array)json_decode($configuration['value']);
        if (empty($configuration)) {
            return ['errors' => 'Configuration is missing'];
        }

        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['firstname', 'lastname', 'user_id']]);

        $phpmailer = new PHPMailer();

        $emailFrom = empty($configuration['from']) ? $email['sender']['email'] : $configuration['from'];
        if (empty($email['sender']['entityId'])) {
            // Usefull for old sendmail server which doesn't support accent encoding
            $setFrom = TextFormatModel::normalize(['string' => "{$user['firstname']} {$user['lastname']}"]);
            $phpmailer->setFrom($emailFrom, ucwords($setFrom));
        } else {
            $entity = EntityModel::getById(['id' => $email['sender']['entityId'], 'select' => ['short_label']]);
            // Usefull for old sendmail server which doesn't support accent encoding
            $setFrom = TextFormatModel::normalize(['string' => $entity['short_label']]);
            $phpmailer->setFrom($emailFrom, ucwords($setFrom));
        }
        if (in_array($configuration['type'], ['smtp', 'mail'])) {
            if ($configuration['type'] == 'smtp') {
                $phpmailer->isSMTP();
            } elseif ($configuration['type'] == 'mail') {
                $phpmailer->isMail();
            }

            $phpmailer->Host = $configuration['host'];
            $phpmailer->Port = $configuration['port'];
            $phpmailer->SMTPAutoTLS = false;
            if (!empty($configuration['secure'])) {
                $phpmailer->SMTPSecure = $configuration['secure'];
            }
            $phpmailer->SMTPAuth = $configuration['auth'];
            if ($configuration['auth']) {
                $phpmailer->Username = $configuration['user'];
                if (!empty($configuration['password'])) {
                    $phpmailer->Password = PasswordModel::decrypt(['cryptedPassword' => $configuration['password']]);
                }
            }
        } elseif ($configuration['type'] == 'sendmail') {
            $phpmailer->isSendmail();
        } elseif ($configuration['type'] == 'qmail') {
            $phpmailer->isQmail();
        }

        $phpmailer->addReplyTo($email['sender']['email']);
        $phpmailer->CharSet = $configuration['charset'];

        foreach ($email['recipients'] as $recipient) {
            $phpmailer->addAddress($recipient);
        }
        foreach ($email['cc'] as $recipient) {
            $phpmailer->addCC($recipient);
        }
        foreach ($email['cci'] as $recipient) {
            $phpmailer->addBCC($recipient);
        }

        if ($email['is_html']) {
            $phpmailer->isHTML(true);

            $dom = new \DOMDocument();
            $dom->loadHTML($email['body'], LIBXML_NOWARNING);
            $images = $dom->getElementsByTagName('img');

            foreach ($images as $key => $image) {
                $originalSrc = $image->getAttribute('src');
                if (preg_match('/^data:image\/(\w+);base64,/', $originalSrc)) {
                    $encodedImage = substr($originalSrc, strpos($originalSrc, ',') + 1);
                    $imageFormat = substr($originalSrc, 11, strpos($originalSrc, ';') - 11);
                    $phpmailer->addStringEmbeddedImage(base64_decode($encodedImage), "embeded{$key}", "embeded{$key}.{$imageFormat}");
                    $email['body'] = str_replace($originalSrc, "cid:embeded{$key}", $email['body']);
                }
            }
        }

        $phpmailer->Subject = $email['object'];
        $phpmailer->Body = $email['body'];
        if (empty($email['body'])) {
            $phpmailer->AllowEmpty = true;
        }

        //zip M2M
        if ($email['message_exchange_id']) {
            $messageExchange = MessageExchangeModel::getMessageByIdentifier(['messageId' => $email['message_exchange_id'], 'select' => ['docserver_id','path','filename','fingerprint','reference']]);
            $docserver       = DocserverModel::getByDocserverId(['docserverId' => $messageExchange[0]['docserver_id']]);
            $docserverType   = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id']]);

            $pathDirectory = str_replace('#', DIRECTORY_SEPARATOR, $messageExchange[0]['path']);
            $filePath      = $docserver['path_template'] . $pathDirectory . $messageExchange[0]['filename'];
            $fingerprint   = StoreController::getFingerPrint([
                'filePath' => $filePath,
                'mode'     => $docserverType['fingerprint_mode'],
            ]);

            if ($fingerprint != $messageExchange[0]['fingerprint']) {
                $email['document'] = (array)json_decode($email['document']);
                return ['errors' => 'Pb with fingerprint of document. ResId master : ' . $email['document']['id']];
            }

            if (is_file($filePath)) {
                $fileContent = file_get_contents($filePath);
                if ($fileContent === false) {
                    return ['errors' => 'Document not found on docserver'];
                }

                $title = preg_replace(utf8_decode('@[\\/:*?"<>|]@i'), '_', substr($messageExchange[0]['reference'], 0, 30));

                $phpmailer->addStringAttachment($fileContent, $title . '.zip');
            }
        } else {
            if (!empty($email['document'])) {
                $email['document'] = (array)json_decode($email['document']);
                if ($email['document']['isLinked']) {
                    $encodedDocument = ResController::getEncodedDocument(['resId' => $email['document']['id'], 'original' => $email['document']['original']]);
                    if (empty($encodedDocument['errors'])) {
                        $phpmailer->addStringAttachment(base64_decode($encodedDocument['encodedDocument']), $encodedDocument['fileName']);
                    }
                }
                if (!empty($email['document']['attachments'])) {
                    $email['document']['attachments'] = (array)$email['document']['attachments'];
                    foreach ($email['document']['attachments'] as $attachment) {
                        $attachment = (array)$attachment;
                        $encodedDocument = AttachmentController::getEncodedDocument(['id' => $attachment['id'], 'isVersion' => $attachment['isVersion'], 'original' => $attachment['original']]);
                        if (empty($encodedDocument['errors'])) {
                            $phpmailer->addStringAttachment(base64_decode($encodedDocument['encodedDocument']), $encodedDocument['fileName']);
                        }
                    }
                }
                if (!empty($email['document']['notes'])) {
                    $email['document']['notes'] = (array)$email['document']['notes'];
                    $encodedDocument = NoteController::getEncodedPdfByIds(['ids' => $email['document']['notes']]);
                    if (empty($encodedDocument['errors'])) {
                        $phpmailer->addStringAttachment(base64_decode($encodedDocument['encodedDocument']), 'notes.pdf');
                    }
                }
            }
        }

        $phpmailer->Timeout = 30;
        $phpmailer->SMTPDebug = 1;
        $phpmailer->Debugoutput = function ($str) {
            if (strpos($str, 'SMTP ERROR') !== false) {
                HistoryController::add([
                    'tableName'    => 'emails',
                    'recordId'     => 'email',
                    'eventType'    => 'ERROR',
                    'eventId'      => 'sendEmail',
                    'info'         => $str
                ]);
            }
        };

        $isSent = $phpmailer->send();
        if (!$isSent) {
            $history = HistoryModel::get([
                'select'    => ['info'],
                'where'     => ['user_id = ?', 'event_id = ?', 'event_type = ?'],
                'data'      => [$user['user_id'], 'sendEmail', 'ERROR'],
                'orderBy'   => ['event_date DESC'],
                'limit'     => 1
            ]);
            if (!empty($history[0]['info'])) {
                return ['errors' => $history[0]['info']];
            }

            return ['errors' => $phpmailer->ErrorInfo];
        }

        return ['success' => 'success'];
    }

    private static function controlCreateEmail(array $args)
    {
        ValidatorModel::notEmpty($args, ['login']);
        ValidatorModel::stringType($args, ['login']);
        ValidatorModel::arrayType($args, ['data']);

        if (!Validator::arrayType()->notEmpty()->validate($args['data']['sender']) || !Validator::stringType()->notEmpty()->validate($args['data']['sender']['email'])) {
            return ['errors' => 'Data sender email is not set', 'code' => 400];
        } elseif (!Validator::arrayType()->notEmpty()->validate($args['data']['recipients'])) {
            return ['errors' => 'Data recipients is not an array or empty', 'code' => 400];
        } elseif (!Validator::boolType()->validate($args['data']['isHtml'])) {
            return ['errors' => 'Data isHtml is not a boolean or empty', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['data']['status'])) {
            return ['errors' => 'Data status is not a string or empty', 'code' => 400];
        }

        if (!empty($args['data']['document'] && !empty($args['data']['document']['id']))) {
            $check = Validator::intVal()->notEmpty()->validate($args['data']['document']['id']);
            $check = $check && Validator::boolType()->validate($args['data']['document']['isLinked']);
            $check = $check && Validator::boolType()->validate($args['data']['document']['original']);
            if (!$check) {
                return ['errors' => 'Data document errors', 'code' => 400];
            }
            if (!ResController::hasRightByResId(['resId' => [$args['data']['document']['id']], 'userId' => $args['login']])) {
                return ['errors' => 'Document out of perimeter', 'code' => 403];
            }
            if (!empty($args['data']['document']['attachments'])) {
                if (!is_array($args['data']['document']['attachments'])) {
                    return ['errors' => 'Data document[attachments] is not an array', 'code' => 400];
                }
                foreach ($args['data']['document']['attachments'] as $attachment) {
                    $check = Validator::intVal()->notEmpty()->validate($attachment['id']);
                    $check = $check && Validator::boolType()->validate($attachment['isVersion']);
                    $check = $check && Validator::boolType()->validate($attachment['original']);
                    if (!$check) {
                        return ['errors' => 'Data document[attachments] errors', 'code' => 400];
                    }
                    $checkAttachment = AttachmentModel::getById(['id' => $attachment['id'], 'isVersion' => $attachment['isVersion'], 'select' => ['res_id_master']]);
                    if (empty($checkAttachment) || $checkAttachment['res_id_master'] != $args['data']['document']['id']) {
                        return ['errors' => 'Attachment out of perimeter', 'code' => 403];
                    }
                }
            }
            if (!empty($args['data']['document']['notes'])) {
                if (!is_array($args['data']['document']['notes'])) {
                    return ['errors' => 'Data document[notes] is not an array', 'code' => 400];
                }
                foreach ($args['data']['document']['notes'] as $note) {
                    if (!Validator::intVal()->notEmpty()->validate($note)) {
                        return ['errors' => 'Data document[notes] errors', 'code' => 400];
                    }
                    $checkNote = NoteModel::getById(['id' => $note, 'select' => ['identifier']]);
                    if (empty($checkNote) || $checkNote['identifier'] != $args['data']['document']['id']) {
                        return ['errors' => 'Note out of perimeter', 'code' => 403];
                    }

                    $rawUserEntities = EntityModel::getByLogin(['login' => $args['login'], 'select' => ['entity_id']]);
                    $userEntities = [];
                    foreach ($rawUserEntities as $rawUserEntity) {
                        $userEntities[] = $rawUserEntity['entity_id'];
                    }
                    $noteEntities = NoteEntityModel::get(['select' => ['item_id'], 'where' => ['note_id = ?'], 'data' => [$note]]);
                    if (!empty($noteEntities)) {
                        $found = false;
                        foreach ($noteEntities as $noteEntity) {
                            if (in_array($noteEntity['item_id'], $userEntities)) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            return ['errors' => 'Note out of perimeter', 'code' => 403];
                        }
                    }
                }
            }
        }

        return ['success' => 'success'];
    }

    public static function delete(Request $request, Response $response, array $args)
    {
        $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $email = EmailModel::getById(['select' => ['user_id', 'document'], 'id' => $args['id']]);
        if (empty($email)) {
            return $response->withStatus(400)->withJson(['errors' => 'Email does not exist']);
        }
        if ($email['user_id'] != $user['id']) {
            return $response->withStatus(403)->withJson(['errors' => 'Email out of perimeter']);
        }
        
        EmailModel::delete([
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        HistoryController::add([
            'tableName'    => 'emails',
            'recordId'     => $args['id'],
            'eventType'    => 'DEL',
            'eventId'      => 'emailDeletion',
            'info'         => _EMAIL_REMOVED
        ]);

        if (!empty($args['data']['document'])) {
            $document = (array)json_decode($email['document']);

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $document['id'],
                'eventType' => 'DEL',
                'eventId'   => 'emailDeletion',
                'info'      => _EMAIL_REMOVED
            ]);
        }

        return $response->withStatus(204);
    }
}
