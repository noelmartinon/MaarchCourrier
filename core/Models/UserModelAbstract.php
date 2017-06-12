<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief User Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class UserModelAbstract extends \Apps_Table_Service
{
    public static function getById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']],
        ]);

        return $aReturn[0];
    }

    public static function getByEmail(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['mail']);
        static::checkString($aArgs, ['mail']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users'],
            'where'     => ['mail = ? and status = ?'],
            'data'      => [$aArgs['mail'], 'OK'],
            'limit'     => 1,
        ]);

        return $aReturn;
    }

    public static function update(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['user', 'userId']);
        static::checkRequired($aArgs['user'], ['firstname', 'lastname']);
        static::checkString($aArgs['user'], ['firstname', 'lastname', 'mail', 'initials', 'thumbprint', 'phone']);

        $isUpdated = parent::update([
            'table'     => 'users',
            'set'       => [
                'firstname'     => $aArgs['user']['firstname'],
                'lastname'      => $aArgs['user']['lastname'],
                'mail'          => $aArgs['user']['mail'],
                'phone'         => $aArgs['user']['phone'],
                'initials'      => $aArgs['user']['initials'],
                'thumbprint'    => $aArgs['user']['thumbprint']
            ],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return $isUpdated;
    }

    public static function updatePassword(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'password']);
        static::checkString($aArgs, ['userId', 'password']);

        $isUpdated = parent::update([
            'table'     => 'users',
            'set'       => [
                'password'  => SecurityModel::getPasswordHash($aArgs['password'])
            ],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return $isUpdated;
    }

    public static function checkPassword(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'password']);
        static::checkString($aArgs, ['userId', 'password']);

        $aReturn = parent::select([
            'select'    => ['password'],
            'table'     => 'users',
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        if ($aReturn[0]['password'] === SecurityModel::getPasswordHash($aArgs['password'])) {
            return true;
        } else {
            return false;
        }
    }

    public static function createSignature(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'signatureLabel', 'signaturePath', 'signatureFileName']);
        static::checkString($aArgs, ['userId', 'signatureLabel', 'signaturePath', 'signatureFileName']);

        parent::insertInto(
            [
                'user_id'           => $aArgs['userId'],
                'signature_label'   => $aArgs['signatureLabel'],
                'signature_path'    => $aArgs['signaturePath'],
                'signature_file_name' => $aArgs['signatureFileName']
            ],
            'user_signatures'
        );

        return true;
    }

    public static function updateSignature(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['id', 'userId', 'label']);
        static::checkString($aArgs, ['userId', 'label']);
        static::checkNumeric($aArgs, ['id']);

        parent::update([
            'table'     => 'user_signatures',
            'set'       => [
                'signature_label'   => $aArgs['label']
            ],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['id']]
        ]);

        return true;
    }

    public static function deleteSignature(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['signatureId']);
        static::checkNumeric($aArgs, ['signatureId']);

        $where = ['id = ?'];
        $data = [$aArgs['signatureId']];

        if (!empty($aArgs['userId'])) {
            $where[] = 'user_id = ?';
            $data[] = $aArgs['userId'];
        }

        parent::deleteFrom([
            'table'     => 'user_signatures',
            'where'     => $where,
            'data'      => $data,
        ]);

        return true;
    }

    public static function createEmailSignature(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'title', 'htmlBody']);
        static::checkString($aArgs, ['userId', 'title', 'htmlBody']);

        parent::insertInto(
            [
                'user_id'   => $aArgs['userId'],
                'title'     => $aArgs['title'],
                'html_body' => $aArgs['htmlBody']
            ],
            'users_email_signatures'
        );

        return true;
    }

    public static function updateEmailSignature(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['id','userId', 'title', 'htmlBody']);
        static::checkString($aArgs, ['userId', 'title', 'htmlBody']);
        static::checkNumeric($aArgs, ['id']);

        parent::update([
            'table'     => 'users_email_signatures',
            'set'       => [
                'title'     => $aArgs['title'],
                'html_body' => $aArgs['htmlBody'],
            ],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['id']]
        ]);

        return true;
    }

    public static function deleteEmailSignature(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['id', 'userId']);
        static::checkString($aArgs, ['userId']);

        parent::deleteFrom([
            'table'     => 'users_email_signatures',
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['id']]
        ]);

        return true;
    }

    public static function getSignaturesById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);

        $aReturn = static::select([
            'select'    => ['id', 'user_id', 'signature_label', 'signature_path', 'signature_file_name'],
            'table'     => ['user_signatures'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']],
            'order_by'  => 'id'
        ]);

        if (!empty($aReturn)) {
            $docserver = DocserverModel::getByTypeId(['docserver_type_id' => 'TEMPLATES', 'select' => ['path_template']]);
        }

        foreach($aReturn as $key => $value) {
            $pathToSignature = $docserver[0]['path_template'] . str_replace('#', '/', $value['signature_path']) . $value['signature_file_name'];

            $extension = explode('.', $pathToSignature);
            $extension = $extension[count($extension) - 1];
            $fileNameOnTmp = 'tmp_file_' . $_SESSION['user']['UserId'] . '_' . rand() . '.' . strtolower($extension);
            $filePathOnTmp = $_SESSION['config']['tmppath'] . $fileNameOnTmp; // TODO No Session
            if (file_exists($pathToSignature) && copy($pathToSignature, $filePathOnTmp)) {
                $aReturn[$key]['pathToSignatureOnTmp'] = $_SESSION['config']['businessappurl'] . '/tmp/' . $fileNameOnTmp; // TODO No Session
            } else {
                $aReturn[$key]['pathToSignatureOnTmp'] = '';
            }
            $aReturn[$key]['pathToSignature'] = $pathToSignature;

            unset($aReturn[$key]['signature_path'], $aReturn[$key]['signature_file_name']);
        }

        return $aReturn;
    }

    public static function getSignatureWithSignatureIdById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'signatureId']);
        static::checkString($aArgs, ['userId']);
        static::checkNumeric($aArgs, ['signatureId']);

        $aReturn = static::select([
            'select'    => ['id', 'user_id', 'signature_label'],
            'table'     => ['user_signatures'],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['signatureId']],
        ]);

        return $aReturn[0];
    }

    public static function getEmailSignaturesById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_email_signatures'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']],
            'order_by'  => 'id'
        ]);

        return $aReturn;
    }

    public static function getEmailSignatureWithSignatureIdById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'signatureId']);
        static::checkString($aArgs, ['userId']);
        static::checkNumeric($aArgs, ['signatureId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_email_signatures'],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['signatureId']],
        ]);

        return $aReturn[0];
    }

    public static function getLabelledUserById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['id']);
        static::checkString($aArgs, ['id']);


        $rawUser = self::getById(['userId' => $aArgs['id'], 'select' => ['firstname', 'lastname']]);

        $labelledUser = '';
        if (!empty($rawUser)) {
            $labelledUser = $rawUser['firstname']. ' ' .$rawUser['lastname'];
        }

        return $labelledUser;
    }

    public static function getCurrentConsigneById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resId']);
        static::checkNumeric($aArgs, ['resId']);


        $aReturn = static::select([
            'select'    => ['process_comment'],
            'table'     => ['listinstance'],
            'where'     => ['res_id = ?', 'process_date is null', 'item_mode in (?)'],
            'data'      => [$aArgs['resId'], ['visa', 'sign']],
            'order_by'  => 'listinstance_id ASC',
            'limit'     => 1
        ]);

        if (empty($aReturn[0])) {
            return '';
        }

        return $aReturn[0]['process_comment'];
    }

    public static function getGroupsById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);


        $aGroups = static::select([
            'select'    => ['usergroup_content.group_id', 'usergroups.group_desc', 'usergroup_content.primary_group'],
            'table'     => ['usergroup_content, usergroups'],
            'where'     => ['usergroup_content.group_id = usergroups.group_id', 'usergroup_content.user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return $aGroups;
    }

    public static function getEntitiesById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);


        $aEntities = static::select([
            'select'    => ['users_entities.entity_id', 'entities.entity_label', 'users_entities.primary_entity'],
            'table'     => ['users_entities, entities'],
            'where'     => ['users_entities.entity_id = entities.entity_id', 'users_entities.user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return $aEntities;
    }
}
