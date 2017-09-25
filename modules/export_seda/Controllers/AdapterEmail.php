<?php

require_once __DIR__. DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR. 'RequestSeda.php';
require_once __DIR__. DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR. '..'. DIRECTORY_SEPARATOR. 'sendmail'. DIRECTORY_SEPARATOR. 'Models'. DIRECTORY_SEPARATOR. 'MailModel.php';

class AdapterEmail{

    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
        $this->xml = simplexml_load_file(__DIR__. DIRECTORY_SEPARATOR. '..' .DIRECTORY_SEPARATOR. 'xml' . DIRECTORY_SEPARATOR . "config.xml");
    }

    public function send($messageObject)
    {
        $res['status'] = 0;
        $res['content'] = '';

        $gec = strtolower($this->xml->M2M->gec);

        if ($gec == 'maarch_courrier') {
            $sendmail = new stdClass();
            $sendmail->coll_id                = 'letterbox_coll';
            $sendmail->res_id                 = $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingSystemId;
            $sendmail->user_id                = $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->UserIdentifier;
            $sendmail->to_list                = $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value;
            $sendmail->cc_list                = '';
            $sendmail->cci_list               = '';
            $sendmail->email_object           = $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0];
            $sendmail->email_body             = $messageObject->Comment[0]->value;
            $sendmail->is_res_master_attached = 'N';
            $sendmail->email_status           = 'W';
            $sendmail->sender_email           = $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact[0]->Communication[1]->value;

            $message = $this->db->getMessageByReference($messageObject->MessageIdentifier->value);
            $sendmail->message_exchange_id = $message->message_id;

            $date = new DateTime;
            $sendmail->creation_date = $date->format(DateTime::ATOM);

            $mailModel = new MailModel();
            $mailModel->createMail($sendmail);

            $this->db->updateStatusMessage($messageObject->MessageIdentifier->value,'I');
        }

        return $res;
    }
}