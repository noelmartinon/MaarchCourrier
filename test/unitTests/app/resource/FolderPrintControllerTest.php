<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class FolderPrintControllerTest extends TestCase
{
    private static $noteId = null;
    private static $attachmentId = null;

    public function testGenerateFile()
    {
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        // CREATE NOTE
        $noteController = new \Note\controllers\NoteController();
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'value'     => "Test d'ajout d'une note par php unit",
            'entities'  => ['COU', 'CAB'],
            'resId'     => $GLOBALS['resources'][0]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $noteController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        self::$noteId = $responseBody->noteId;
        $this->assertIsInt(self::$noteId);

        //  CREATE ATTACHMENT
        $attachmentController = new \Attachment\controllers\AttachmentController();
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'title'         => 'Nulle pierre ne peut être polie sans friction, nul homme ne peut parfaire son expérience sans épreuve.',
            'type'          => 'response_project',
            'chrono'        => 'MAARCH/2019D/14',
            'resIdMaster'   => $GLOBALS['resources'][0],
            'encodedFile'   => $encodedFile,
            'format'        => 'txt',
            'recipientId'   => 1,
            'recipientType' => 'contact'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        self::$attachmentId = $responseBody->id;
        $this->assertIsInt(self::$attachmentId);


        //  CREATE LINK
        \Resource\models\ResModel::update(['set' => ['linked_resources' => json_encode([$GLOBALS['resources'][1]])], 'where' => ['res_id = ?'], 'data' => [$GLOBALS['resources'][0]]]);

        // GENERATE FOLDER PRINT

        $folderPrintController = new \Resource\controllers\FolderPrintController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            "resources" => [[
                "resId"                   => $GLOBALS['resources'][0],
                "document"                => true,
                "attachments"             => [self::$attachmentId],
                "notes"                   => [self::$noteId],
                "acknowledgementReceipts" => [],
                "emails"                  => [],
                "linkedResources"         => [
                    [
                        'resId'     => $GLOBALS['resources'][1],
                        'document'  => true
                    ]
                ],
            ]],
            "summarySheet" => [
                [
                    "unit" => "qrcode",
                    "label" => ""
                ],
                [
                    "unit" => "primaryInformations",
                    "label" => "Informations primaires"
                ],
                [
                    "unit" => "senderRecipientInformations",
                    "label" => "Informations de traitement"
                ],
                [
                    "unit" => "secondaryInformations",
                    "label" => "Informations secondaires"
                ],
                [
                    "unit" => "diffusionList",
                    "label" => "Liste de diffusion"
                ],
                [
                    "unit" => "opinionWorkflow",
                    "label" => "Liste d'avis"
                ],
                [
                    "unit" => "visaWorkflow",
                    "label" => "Circuit de visa"
                ]
            ],
            "withSeparator" => true,
        ];
        
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $folderPrintController->generateFile($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        // GENERATE FOLDER PRINT 2

        $folderPrintController = new \Resource\controllers\FolderPrintController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            "resources" => [[
                "resId"                   => $GLOBALS['resources'][0],
                "document"                => true,
                "attachments"             => true,
                "notes"                   => true,
                "acknowledgementReceipts" => true,
                "emails"                  => true,
            ]],
            "summarySheet" => [
                [
                    "unit" => "qrcode",
                    "label" => ""
                ],
                [
                    "unit" => "primaryInformations",
                    "label" => "Informations primaires"
                ],
                [
                    "unit" => "senderRecipientInformations",
                    "label" => "Informations de traitement"
                ],
                [
                    "unit" => "secondaryInformations",
                    "label" => "Informations secondaires"
                ],
                [
                    "unit" => "diffusionList",
                    "label" => "Liste de diffusion"
                ],
                [
                    "unit" => "opinionWorkflow",
                    "label" => "Liste d'avis"
                ],
                [
                    "unit" => "visaWorkflow",
                    "label" => "Circuit de visa"
                ]
            ],
            "withSeparator" => true,
        ];
        
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $folderPrintController->generateFile($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        // DELETE NOTE
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $noteController = new \Note\controllers\NoteController();
        $response         = $noteController->delete($request, new \Slim\Http\Response(), ['id' => self::$noteId]);

        $this->assertSame(204, $response->getStatusCode());

        // DELETE ATTACHMENT
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $attachmentController->delete($request, new \Slim\Http\Response(), ['id' => self::$attachmentId]);
        $this->assertSame(204, $response->getStatusCode());

        // //ERRORS
        // $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        // $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // unset($aArgs['data'][2]['label']);
        // $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        // $response = $ExportController->updateExport($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        // $responseBody = json_decode((string)$response->getBody());
        // $this->assertSame('One data is not set well', $responseBody->errors);
    }
}
