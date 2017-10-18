<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Attachments Controller
* @author dev@maarch.org
* @ingroup attachments
*/

namespace Attachments\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator;
use Attachments\Models\AttachmentsModel;
use Core\Controllers\ResController;

require_once 'modules/attachments/Models/AttachmentsModel.php';

class AttachmentsController
{
    
    public function storeAttachmentResource($aArgs)
    {
        if (empty($aArgs['resId'])) {

            return ['errors' => 'resId ' . _EMPTY];
        }

        if (empty($aArgs['encodedFile'])) {

            return ['errors' => 'encodedFile ' . _EMPTY];
        }

        if (empty($aArgs['data'])) {

            return ['errors' => 'data ' . _EMPTY];
        }

        if (empty($aArgs['collId'])) {

            return ['errors' => 'collId ' . _EMPTY];
        }

        if (empty($aArgs['collIdMaster'])) {

            return ['errors' => 'collIdMaster ' . _EMPTY];
        }

        if (empty($aArgs['table'])) {

            return ['errors' => 'table ' . _EMPTY];
        }

        if (empty($aArgs['fileFormat'])) {

            return ['errors' => 'fileFormat ' . _EMPTY];
        }

        $resId = $aArgs['resId'];
        $encodedFile = $aArgs['encodedFile'];
        $collId = $aArgs['collId'];
        $collIdMaster = $aArgs['collIdMaster'];
        $table = $aArgs['table'];
        $fileFormat = $aArgs['fileFormat'];

        $aArgs = [
            'data'   => $aArgs['data'],
            'collIdMaster' => $collIdMaster,
            'resId'  => $resId,
        ];
        
        $returnPrepare = $this->prepareStorage($aArgs);
        
        $aArgs = [
            'encodedFile'   => $encodedFile,
            'data'          => $returnPrepare['data'],
            'collId'        => $collId,
            'table'         => $table,
            'fileFormat'    => $fileFormat,
            'status'        => $returnPrepare['status'],
        ];

        $res = new \Core\Controllers\ResController();
        $response = $res->storeResource($aArgs);

        //return $response;
        if (!is_numeric($response[0])) {

            return ['errors' => 'Pb with SQL insertion : ' . $response[0]];
        } else {
            require_once 'core/class/class_history.php';
            require_once 'core/class/class_security.php';
            $hist = new \history();
            $sec = new \security();
            $view = $sec->retrieve_view_from_coll_id($collIdMaster);
            
            $hist->add(
                $view, $resId, "ADD", 'attachadd',
                ucfirst(_DOC_NUM) . $response[0] . ' '
                . _NEW_ATTACH_ADDED . ' ' . _TO_MASTER_DOCUMENT
                . $resId,
                $_SESSION['config']['databasetype'],
                'apps'
            );
            $hist->add(
                $table, $response[0], "ADD",'attachadd',
                _NEW_ATTACH_ADDED,
                $_SESSION['config']['databasetype'],
                'attachments'
            );
        }

        if ($response[0] == 0) {
            $response[0] = '';
        }

        return [$response[0]];
    }

    /**
     * Prepares storage on database.
     * @param  $data array
     * @param  $resId bigint
     * @param  $collIdMaster string
     * @return array $data
     */
    public function prepareStorage($aArgs)
    {
        if (empty($aArgs['data'])) {

            return ['errors' => 'data ' . _EMPTY];
        }

        if (empty($aArgs['resId'])) {

            return ['errors' => 'resId ' . _EMPTY];
        }

        if (empty($aArgs['collIdMaster'])) {

            return ['errors' => 'collIdMaster ' . _EMPTY];
        }

        $statusFound = false;
        $typistFound = false;
        $typeIdFound = false;
        $attachmentTypeFound = false;
        
        $data = $aArgs['data'];
        
        $countD = count($data);
        for ($i=0;$i<$countD;$i++) {

            if (
                strtoupper($data[$i]['type']) == 'INTEGER' || 
                strtoupper($data[$i]['type']) == 'FLOAT'
            ) {
                if ($data[$i]['value'] == '') {
                    $data[$i]['value'] = '0';
                }
            }

            if (strtoupper($data[$i]['type']) == 'STRING') {
               $data[$i]['value'] = $data[$i]['value'];
               $data[$i]['value'] = str_replace(";", "", $data[$i]['value']);
               $data[$i]['value'] = str_replace("--", "", $data[$i]['value']);
            }

            if (strtoupper($data[$i]['column']) == strtoupper('status')) {
                $statusFound = true;
                $status = $data[$i]['value'];
            }

            if (strtoupper($data[$i]['column']) == strtoupper('typist')) {
                $typistFound = true;
            }

            if (strtoupper($data[$i]['column']) == strtoupper('type_id')) {
                $typeIdFound = true;
            }

            if (strtoupper($data[$i]['column']) == strtoupper('attachment_type')) {
                $attachmentTypeFound = true;
            }
        }

        if (!$typistFound) {
            array_push(
                $data,
                array(
                    'column' => 'typist',
                    'value' => $_SESSION['user']['UserId'],
                    'type' => 'string',
                )
            );
        }

        if (!$typeIdFound) {
            array_push(
                $data,
                array(
                    'column' => 'type_id',
                    'value' => 0,
                    'type' => 'int',
                )
            );
        }
        
        if (!$statusFound) {
            array_push(
                $data,
                array(
                    'column' => 'status',
                    'value' => 'NEW',
                    'type' => 'string',
                )
            );
            $status = 'NEW';
        }
        
        if (!$attachmentTypeFound) {
            array_push(
                $data,
                array(
                    'column' => 'attachment_type',
                    'value' => 'response_project',
                    'type' => 'string',
                )
            );
        }

        //BASICS
        array_push(
            $data,
            array(
                'column' => "coll_id",
                'value' => $aArgs['collIdMaster'],
                'type' => "string",
            )
        );

        array_push(
            $data,
            array(
                'column' => "res_id_master",
                'value' => $aArgs['resId'],
                'type' => "integer",
            )
        );

        array_push(
            $data,
            array(
                'column' => "relation",
                'value' => 1,
                'type' => "integer",
            )
        );

        $return = [
            'data'   => $data,
            'status' => $status,
        ];

        return $return;
    }

    /**
     * Prepares storage for transmission.
     * @param  $nb integer
     * @return array $data
     */
    public function setTransmissionData($aArgs)
    {
        if (empty($aArgs['nb'])) {

            return ['errors' => 'nb ' . _EMPTY];
        }

        $transmissionData = [];

        $transmissionData[] = [
            'column' => 'format',
            'value' => $_SESSION['upfileTransmission'][$nb]['format'],
            'type' => 'string'
        ];
        
        if (!empty($_REQUEST["transmissionExpectedDate{$nb}"])) {
            $rturn = $_REQUEST["transmissionExpectedDate{$nb}"];
        } else {
            $rturn = 'NO_RTURN';
        }
        //TODO
        $transmissionData[] = [
            'column' => 'status',
            'value' => $rturn,
            'type' => 'string'
        ];
        
        $transmissionData[] = [
            'column' => 'title',
            'value' => str_replace("&#039;", "'", $_REQUEST["transmissionTitle{$nb}"]),
            'type' => 'string'
        ];
        $transmissionData[] = [
            'column' => 'attachment_type',
            'value' => $_REQUEST["transmissionType{$nb}"],
            'type' => 'string'
        ];
        $transmissionData[] = [
            'column' => 'coll_id',
            'value' => $_SESSION['collection_id_choice'],
            'type' => 'string'
        ];
        //TODO
        $transmissionData[] = [
            'column' => 'res_id_master',
            'value' => $_SESSION['doc_id'],
            'type' => 'integer'
        ];
        $transmissionData[] = [
            'column' => 'identifier',
            'value' => $_REQUEST["transmissionChrono{$nb}"],
            'type' => 'string'
        ];

        if (!empty($_REQUEST["transmissionBackDate{$nb}"])) {
            $transmissionData[] = [
                'column' => 'validation_date',
                'value' => $_REQUEST["transmissionBackDate{$nb}"],
                'type' => 'date'
            ];
        }

        if (
            !empty($_REQUEST["transmissionContactidAttach{$nb}"]) && 
            is_numeric($_REQUEST["transmissionContactidAttach{$nb}"])
        ) {
            $transmissionData[] = [
                'column' => 'dest_contact_id',
                'value' => $_REQUEST["transmissionContactidAttach{$nb}"],
                'type' => 'integer'
            ];
        } else if (
            !empty($_REQUEST["transmissionContactidAttach{$nb}"]) && 
            !is_numeric($_REQUEST["transmissionContactidAttach{$nb}"])
        ) {
            $transmissionData[] = [
                'column' => 'dest_user',
                'value' => $_REQUEST["transmissionContactidAttach{$nb}"],
                'type' => 'string'
            ];
        }

        if (
            !empty($_REQUEST["transmissionAddressidAttach{$nb}"]) && 
            is_numeric($_REQUEST["transmissionAddressidAttach{$nb}"])
        ) {
            $transmissionData[] = [
                'column' => 'dest_address_id',
                'value' => $_REQUEST["transmissionAddressidAttach{$nb}"],
                'type' => 'integer'
            ];
        }

        return $transmissionData;
    }

    function setTransmissionDataPdf($aArgs, $nb, $storeResult) 
    {
        $transmissionDataPdf = [];

        //TODO
        $file    = $_SESSION['config']['tmppath'] . $_SESSION['upfileTransmission'][$nb]['fileNamePdfOnTmp'];
        $newfile = $storeResult['path_template'] . str_replace('#',"/",$storeResult['destination_dir']) . substr ($storeResult['file_destination_name'], 0, strrpos  ($storeResult['file_destination_name'], "." )) . '.pdf';

        copy($file, $newfile);

        $transmissionDataPdf[] = [
            'column' => 'format',
            'value' => 'pdf',
            'type' => 'string'
        ];
        //TODO
        $transmissionDataPdf[] = [
            'column' => 'status',
            'value' => 'TRA',
            'type' => 'string'
        ];
        $transmissionDataPdf[] = [
            'column' => 'title',
            'value' => str_replace("&#039;", "'", $_REQUEST["transmissionTitle{$nb}"]),
            'type' => 'string'
        ];
        $transmissionDataPdf[] = [
            'column' => 'attachment_type',
            'value' => 'converted_pdf',
            'type' => 'string'
        ];
        $transmissionDataPdf[] = [
            'column' => 'coll_id',
            'value' => $_SESSION['collection_id_choice'],
            'type' => 'string'
        ];
        //TODO
        $transmissionDataPdf[] = [
            'column' => 'res_id_master',
            'value' => $_SESSION['doc_id'],
            'type' => 'integer'
        ];
        
        return $transmissionDataPdf;
    }

    public function setInSignatureBook(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        //TODO Controle de droit de modification de cet attachment

        $data = $request->getParams();

        $attachment = AttachmentsModel::getById(['id' => $aArgs['id'], 'isVersion' => $data['isVersion']]);

        if (empty($attachment)) {
            return $response->withStatus(400)->withJson(['errors' => 'Attachment not found']);
        }

        AttachmentsModel::setInSignatureBook(['id' => $aArgs['id'], 'isVersion' => $data['isVersion'], 'inSignatureBook' => !$attachment['in_signature_book']]);

        return $response->withJson(['success' => 'success']);
    }
}