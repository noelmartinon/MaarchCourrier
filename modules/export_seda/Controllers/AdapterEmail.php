<?php

require_once __DIR__. DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR. 'RequestSeda.php';

class AdapterEmail
{
    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
        $this->xml = simplexml_load_file(__DIR__. DIRECTORY_SEPARATOR. '..' .DIRECTORY_SEPARATOR. 'xml' . DIRECTORY_SEPARATOR . "config.xml");
    }

    public function send($messageObject, $messageId)
    {
        $res['status'] = 0;
        $res['content'] = '';

        $gec = strtolower($this->xml->M2M->gec);

        if ($gec == 'maarch_courrier') {
            $document = ['id' => $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingSystemId, 'isLinked' => false, 'original' => false];
            $userInfo = \User\models\UserModel::getByLogin(['login' => $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->UserIdentifier, 'select' => ['id', 'mail']]);

            if (!empty($messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact[0]->Communication[1]->value)) {
                $senderEmail = $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact[0]->Communication[1]->value;
            } else {
                $senderEmail = $userInfo['mail'];
            }

            \Email\controllers\EmailController::createEmail([
                'userId'    => $userInfo['id'],
                'data'      => [
                    'sender'        => ['email' => $senderEmail],
                    'recipients'    => [$messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value],
                    'cc'            => '',
                    'cci'           => '',
                    'object'        => $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0],
                    'body'          => $messageObject->Comment[0]->value,
                    'document'      => $document,
                    'isHtml'        => true,
                    'status'        => 'TO_SEND',
                    'messageExchangeId' => $messageId
                ]
            ]);

            $this->db->updateStatusMessage($messageId, 'I');
        }

        return $res;
    }
}
