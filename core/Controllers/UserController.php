<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief User Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Controllers;

use Baskets\Models\BasketsModel;
use Core\Models\LangModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator;
use Core\Models\UserModel;

include_once 'core/class/docservers_controler.php';
//include_once 'modules/basket/class/class_modules_tools.php';

class UserController
{
    public function getCurrentUserInfos(RequestInterface $request, ResponseInterface $response)
    {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $user = UserModel::getById(['userId' => $_SESSION['user']['UserId'], 'select' => ['user_id', 'firstname', 'lastname', 'phone', 'mail', 'initials', 'thumbprint']]);
        $user['signatures'] = UserModel::getSignaturesById(['userId' => $_SESSION['user']['UserId']]);
        $user['emailSignatures'] = UserModel::getEmailSignaturesById(['userId' => $_SESSION['user']['UserId']]);
        $user['groups'] = UserModel::getGroupsById(['userId' => $_SESSION['user']['UserId']]);
        $user['entities'] = UserModel::getEntitiesById(['userId' => $_SESSION['user']['UserId']]);
        $user['lang'] = LangModel::getProfileLang();
        $user['baskets'] = BasketsModel::getBasketsByUserId(['userId' => $_SESSION['user']['UserId']]);
        $user['redirectedBaskets'] = BasketsModel::getBasketsRedirectedByUserId(['userId' => $_SESSION['user']['UserId']]);

        return $response->withJson($user);
    }

    public function update(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['firstname', 'lastname']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $r = UserModel::update(['userId' => $aArgs['id'] ,'user' => $data]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'User Update Error']);
        }

        return $response->withJson(['success' => _UPDATED_PROFILE]);
    }

    public function updateProfile(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['firstname', 'lastname']])
            || (!empty($data['mail']) && !filter_var($data['mail'], FILTER_VALIDATE_EMAIL))
            || (!empty($data['phone']) && !preg_match("/^(?:0|\+\d\d\s?)[1-9]([\.\-\s]?\d\d){4}$/", $data['phone']))) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $r = UserModel::update(['userId' => $_SESSION['user']['UserId'], 'user' => $data]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'User Update Error']);
        }

        return $response->withJson(['success' => _UPDATED_PROFILE]);
    }

    public function updateCurrentUserPassword(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['currentPassword', 'newPassword', 'reNewPassword']])) {
            return $response->withJson(['errors' => _EMPTY_PSW_FORM]);
        }

        if ($data['newPassword'] != $data['reNewPassword']) {
            return $response->withJson(['errors' => _WRONG_SECOND_PSW]);
        } elseif (!UserModel::checkPassword(['userId' => $_SESSION['user']['UserId'],'password' => $data['currentPassword']])) {
            return $response->withJson(['errors' => _WRONG_PSW]);
        }

        $r = UserModel::updatePassword(['userId' => $_SESSION['user']['UserId'], 'password' => $data['newPassword']]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Password Update Error']);
        }

        return $response->withJson(['success' => _UPDATED_PASSWORD]);
    }

    public function setCurrentUserBasketsRedirectionForAbsence(RequestInterface $request, ResponseInterface $response) {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        foreach ($data as $key => $value) {
            if (empty($value['newUser']) || empty($value['basketId']) || empty($value['basketOwner']) || empty($value['virtual'])) {
                return $response->withStatus(400)->withJson(['errors' => _FORM_ERROR]);
            }
            $newUser = strrchr($value['newUser'], '(');
            $newUser = str_replace(['(', ')'], '', $newUser);
            if (!empty($newUser)) {
                $check = UserModel::getById(['userId' => $newUser, 'select' => ['1']]);
            }
            if (empty($newUser) || empty($check)) {
                return $response->withStatus(400)->withJson(['errors' => _UNDEFINED_USER]);
            }
            $data[$key]['newUser'] = $newUser;
        }

        if (!empty($data)) {
            BasketsModel::setBasketsRedirection(['userId' => $_SESSION['user']['UserId'], 'data' => $data]);
        }

        return $response->withJson(['redirectedBaskets' => BasketsModel::getBasketsRedirectedByUserId(['userId' => $_SESSION['user']['UserId']])]);
    }

    public function deleteCurrentUserRedirectedBaskets(RequestInterface $request, ResponseInterface $response, $aArgs) {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        BasketsModel::deleteBasketRedirection(['userId' => $_SESSION['user']['UserId'], 'basketId' => $aArgs['id']]);

        return $response->withJson(['redirectedBaskets' => BasketsModel::getBasketsRedirectedByUserId(['userId' => $_SESSION['user']['UserId']])]);
    }

    public function activateAbsence(RequestInterface $request, ResponseInterface $response) {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        UserModel::activateAbsenceById(['userId' => $_SESSION['user']['UserId']]);

        return $response->withJson(['success' => 'success']);
    }

    public function createCurrentUserSignature(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['base64', 'name', 'label']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $file     = base64_decode($data['base64']);
        $tmpName  = 'tmp_file_' .$_SESSION['user']['UserId']. '_' .rand(). '_' .$data['name'];

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);
        $size     = strlen($file);
        $type     = explode('/', $mimeType);
        $ext      = strtoupper(substr($data['name'], strrpos($data['name'], '.') + 1));

        if (file_exists('custom/' .$_SESSION['custom_override_id']. '/apps/maarch_entreprise/xml/extensions.xml')) {
            $path = 'custom/' .$_SESSION['custom_override_id']. '/apps/maarch_entreprise/xml/extensions.xml';
        } else {
            $path = 'apps/maarch_entreprise/xml/extensions.xml';
        }

        $xmlfile  = simplexml_load_file($path);

        $fileAccepted = false;
        if (count($xmlfile->FORMAT) > 0) {
            foreach ($xmlfile->FORMAT as $value) {
                if(strtoupper($value->name) == $ext && strtoupper($value->mime) == strtoupper($mimeType)){
                    $fileAccepted = true;
                    break;
                }
            }
        }

        if (!$fileAccepted || $type[0] != 'image') {
            return $response->withJson(['errors' => _WRONG_FILE_TYPE]);
        } elseif ($size > 2000000){
            return $response->withJson(['errors' => _MAX_SIZE_UPLOAD_REACHED . ' (2 MB)']);
        }

        file_put_contents($_SESSION['config']['tmppath'] . $tmpName, $file);

        $docservers_controler = new \docservers_controler();
        $storeInfos = $docservers_controler->storeResourceOnDocserver(
            'templates',
            [
                'tmpDir'      => $_SESSION['config']['tmppath'],
                'size'        => $data['size'],
                'format'      => $ext,
                'tmpFileName' => $tmpName
            ]
        );

        if (!file_exists($storeInfos['path_template']. str_replace('#', '/', $storeInfos['destination_dir']) .$storeInfos['file_destination_name'])) {
            return $response->withJson(['errors' => $storeInfos['error'] .' templates']);
        }

        $r = UserModel::createSignature([
            'userId'            => $_SESSION['user']['UserId'],
            'signatureLabel'    => $data['label'],
            'signaturePath'     => $storeInfos['destination_dir'],
            'signatureFileName' => $storeInfos['file_destination_name'],
        ]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Signature Create Error']);
        }

        return $response->withJson([
            'success' => _NEW_SIGNATURE,
            'signatures' => UserModel::getSignaturesById(['userId' => $_SESSION['user']['UserId']])
        ]);
    }

    public function updateCurrentUserSignature(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['label']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $r = UserModel::updateSignature([
            'id'        => $aArgs['id'],
            'userId'    => $_SESSION['user']['UserId'],
            'label'     => $data['label']
        ]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Signature Update Error']);
        }

        return $response->withJson([
            'success' => _UPDATED_SIGNATURE,
            'signature' => UserModel::getSignatureWithSignatureIdById(['userId' => $_SESSION['user']['UserId'], 'signatureId' => $aArgs['id']])
        ]);
    }

    public function deleteCurrentUserSignature(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $r = UserModel::deleteSignature(['signatureId' => $aArgs['id'], 'userId' => $_SESSION['user']['UserId']]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Signature Creation Error']);
        }

        return $response->withJson([
            'success' => _DELETED_SIGNATURE,
            'signatures' => UserModel::getSignaturesById(['userId' => $_SESSION['user']['UserId']])
        ]);
    }

    public function createCurrentUserEmailSignature(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['title', 'htmlBody']])) {
            return $response->withJson(['errors' => _EMPTY_EMAIL_SIGNATURE_FORM]);
        }

        $r = UserModel::createEmailSignature([
            'userId'    => $_SESSION['user']['UserId'],
            'title'     => $data['title'],
            'htmlBody'  => $data['htmlBody']
        ]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Email Signature Creation Error']);
        }

        return $response->withJson([
            'success' => _NEW_EMAIL_SIGNATURE,
            'emailSignatures' => UserModel::getEmailSignaturesById(['userId' => $_SESSION['user']['UserId']])
        ]);
    }

    public function updateCurrentUserEmailSignature(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['title', 'htmlBody']])) {
            return $response->withJson(['errors' => _EMPTY_EMAIL_SIGNATURE_FORM]);
        }

        $r = UserModel::updateEmailSignature([
            'id'        => $aArgs['id'],
            'userId'    => $_SESSION['user']['UserId'],
            'title'     => $data['title'],
            'htmlBody'  => $data['htmlBody']
        ]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Email Signature Update Error']);
        }

        return $response->withJson([
            'success' => _UPDATED_EMAIL_SIGNATURE,
            'emailSignature' => UserModel::getEmailSignatureWithSignatureIdById(['userId' => $_SESSION['user']['UserId'], 'signatureId' => $aArgs['id']])
        ]);
    }

    public function deleteCurrentUserEmailSignature(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $r = UserModel::deleteEmailSignature([
            'id'        => $aArgs['id'],
            'userId'    => $_SESSION['user']['UserId']
        ]);

        if (!$r) {
            return $response->withStatus(500)->withJson(['errors' => 'Email Signature Delete Error']);
        }

        return $response->withJson([
            'success' => _DELETED_EMAIL_SIGNATURE,
            'emailSignatures' => UserModel::getEmailSignaturesById(['userId' => $_SESSION['user']['UserId']])
        ]);
    }

    public function setAbsence(RequestInterface $request, ResponseInterface $response)
    {
        $users = UserModel::get();

        $formattedUsers = [];
        foreach ($users as $value) {
            $formattedUsers[] = "{$value['firstname']} {$value['lastname']} ({$value['user_id']})";
        }

        return $response->withJson($formattedUsers);
    }

    public function getUsersForAutocompletion(RequestInterface $request, ResponseInterface $response)
    {
        $users = UserModel::get();

        $formattedUsers = [];
        foreach ($users as $value) {
            $formattedUsers[] = "{$value['firstname']} {$value['lastname']} ({$value['user_id']})";
        }

        return $response->withJson($formattedUsers);
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
}
