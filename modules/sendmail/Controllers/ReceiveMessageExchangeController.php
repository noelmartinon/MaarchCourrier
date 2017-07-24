<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Receive Message Exchange Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Sendmail\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
// require_once 'apps/maarch_entreprise/Models/ResModel.php';
// require_once 'modules/export_seda/RequestSeda.php';

class ReceiveMessageExchangeController
{
     public function saveMessageExchange(RequestInterface $request, ResponseInterface $response)
    {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['base64', 'name', 'label', 'base64ForJs', 'type', 'size']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $file     = base64_decode($data['base64']);
        $tmpName  = 'tmp_file_' .$_SESSION['user']['UserId']. '_' .rand(). '_' .$data['name'];

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);
        $size     = strlen($file);
        $type     = explode('/', $mimeType);
        $ext      = strtoupper(substr($data['name'], strrpos($data['name'], '.') + 1));

        if ($mimeType != "application/x-tar" && $mimeType != "application/zip" && $mimeType != "application/tar" && $mimeType != "application/x-gzip") {
            return $response->withJson(['errors' => _WRONG_FILE_TYPE]);
        }

        file_put_contents($_SESSION['config']['tmppath'] . $tmpName, $file);

        return $response->withJson([true]);

    }

    private function checkNeededParameters($aArgs = [])
    {
        foreach ($aArgs['needed'] as $value) {
            if (empty($aArgs['data'][$value])) {
                return false;
            }
        }

        return true;
    }

    // protected function control($aArgs = [])
    // {
    //     $errors = [];

    //     if (empty($aArgs['id'])) {
    //         array_push($errors, 'wrong format for id');
    //     }

    //     return $errors;
    // }

}
