<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ParametersController
* @author  dev <dev@maarch.org>
* @ingroup core
*/

/**
 * @brief Parameter Controller
 * @author dev@maarch.org
 */

namespace Parameter\controllers;

use Attachment\models\AttachmentTypeModel;
use Basket\models\BasketModel;
use Doctype\models\DoctypeModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use IndexingModel\models\IndexingModelModel;
use MessageExchange\controllers\ReceiveMessageExchangeController;
use Parameter\models\ParameterModel;
use Priority\models\PriorityModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use Status\models\StatusModel;

class ParameterController
{
    public function get(Request $request, Response $response)
    {
        $where = [];
        $data  = [];
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            $where = ['id = ?'];
            $data  = ['traffic_record_summary_sheet'];
        }

        $parameters = ParameterModel::get(['where' => $where, 'data' => $data]);

        foreach ($parameters as $key => $parameter) {
            if (!empty($parameter['param_value_string'])) {
                $parameters[$key]['value'] = $parameter['param_value_string'];
            } elseif (is_int($parameter['param_value_int'])) {
                $parameters[$key]['value'] = $parameter['param_value_int'];
            } elseif (!empty($parameter['param_value_date'])) {
                $parameters[$key]['value'] = $parameter['param_value_date'];
            }
        }

        $parameterIds = array_column($parameters, 'id');
        if (!in_array('loginpage_message', $parameterIds)) {
            $parameters[] = [
                "description"        => null,
                "id"                 => "loginpage_message",
                "param_value_date"   => null,
                "param_value_int"    => null,
                "param_value_string" => "",
                "value"              => ""
            ];
        }
        if (!in_array('homepage_message', $parameterIds)) {
            $parameters[] = [
                "description"        => null,
                "id"                 => "homepage_message",
                "param_value_date"   => null,
                "param_value_int"    => null,
                "param_value_string" => "",
                "value"              => ""
            ];
        }

        return $response->withJson(['parameters' => $parameters]);
    }

    public function getById(Request $request, Response $response, array $aArgs)
    {
        if (!in_array($aArgs['id'], ['minimumVisaRole', 'maximumSignRole']) && !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $parameter = ParameterModel::getById(['id' => $aArgs['id']]);

        if (empty($parameter)) {
            return $response->withStatus(400)->withJson(['errors' => 'Parameter not found']);
        }

        return $response->withJson(['parameter' => $parameter]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['id']) && preg_match("/^[\w-]*$/", $data['id']);
        $check = $check && (empty($data['param_value_int']) || Validator::intVal()->validate($data['param_value_int']));
        $check = $check && (empty($data['param_value_string']) || Validator::stringType()->validate($data['param_value_string']));
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $parameter = ParameterModel::getById(['id' => $data['id']]);
        if (!empty($parameter)) {
            return $response->withStatus(400)->withJson(['errors' => _PARAMETER_ID_ALREADY_EXISTS]);
        }

        ParameterModel::create($data);
        HistoryController::add([
            'tableName' => 'parameters',
            'recordId'  => $data['id'],
            'eventType' => 'ADD',
            'info'      => _PARAMETER_CREATION . " : {$data['id']}",
            'moduleId'  => 'parameter',
            'eventId'   => 'parameterCreation',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        $customId = CoreConfigModel::getCustomId();
        if (in_array($args['id'], ['logo', 'bodyImage'])) {
            if (empty($customId)) {
                return $response->withStatus(400)->withJson(['errors' => 'A custom is needed for this operation']);
            }

            $tmpPath = CoreConfigModel::getTmpPath();
            if (!is_dir("custom/{$customId}/img")) {
                mkdir("custom/{$customId}/img", 0755, true);
            }
            if ($args['id'] == 'logo') {
                if (strpos($body['image'], 'data:image/svg+xml;base64,') === false) {
                    return $response->withStatus(400)->withJson(['errors' => 'Body image is not a base64 image']);
                }
                $tmpFileName  = $tmpPath . 'parameter_logo_' . rand() . '_file.svg';
                $body['logo'] = str_replace('data:image/svg+xml;base64,', '', $body['image']);
                $file         = base64_decode($body['logo']);
                file_put_contents($tmpFileName, $file);

                $size = strlen($file);
                if ($size > 5000000) {
                    return $response->withStatus(400)->withJson(['errors' => 'Logo size is not allowed']);
                }
                copy($tmpFileName, "custom/{$customId}/img/logo.svg");
            } elseif ($args['id'] == 'bodyImage') {
                if (strpos($body['image'], 'data:image/jpeg;base64,') === false) {
                    if (!is_file("dist/{$body['image']}")) {
                        return $response->withStatus(400)->withJson(['errors' => 'Body image does not exist']);
                    }
                    copy("dist/{$body['image']}", "custom/{$customId}/img/bodylogin.jpg");
                } else {
                    $tmpFileName   = $tmpPath . 'parameter_body_' . rand() . '_file.jpg';
                    $body['image'] = str_replace('data:image/jpeg;base64,', '', $body['image']);
                    $file          = base64_decode($body['image']);
                    file_put_contents($tmpFileName, $file);

                    $size       = strlen($file);
                    $imageSizes = getimagesize($tmpFileName);
                    if ($imageSizes[0] < 1920 || $imageSizes[1] < 1080) {
                        return $response->withStatus(400)->withJson(['errors' => 'Body image is not wide enough']);
                    } elseif ($size > 10000000) {
                        return $response->withStatus(400)->withJson(['errors' => 'Body size is not allowed']);
                    }
                    copy($tmpFileName, "custom/{$customId}/img/bodylogin.jpg");
                }
            }
            if (!empty($tmpFileName) && is_file($tmpFileName)) {
                unset($tmpFileName);
            }
        } elseif (in_array($args['id'], ['applicationName', 'maarchUrl'])) {
            $config = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);
            $config['config'][$args['id']] = $body[$args['id']];
            if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/config.json")) {
                $fp = fopen("custom/{$customId}/apps/maarch_entreprise/xml/config.json", 'w');
            } else {
                $fp = fopen("apps/maarch_entreprise/xml/config.json", 'w');
            }
            fwrite($fp, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fclose($fp);
        } elseif (in_array($args['id'], ['bindingDocumentFinalAction', 'nonBindingDocumentFinalAction'])) {
            $parameter = ParameterModel::getById(['id' => $args['id']]);
            if (empty($parameter)) {
                return $response->withStatus(400)->withJson(['errors' => 'Parameter not found']);
            }
            if (!in_array($body['param_value_string'], ['restrictAccess', 'transfer', 'copy', 'delete'])) {
                return $response->withStatus(400)->withJson(['errors' => 'param_value_string must be between : restrictAccess, transfer, copy, delete']);
            }
            ParameterModel::update([
                'description'        => '',
                'param_value_string' => $body['param_value_string'],
                'id'                 => $args['id']
            ]);
        } else {
            $parameter = ParameterModel::getById(['id' => $args['id']]);
            if (empty($parameter)) {
                if (!in_array($args['id'], ['loginpage_message', 'homepage_message'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Parameter not found']);
                }
                ParameterModel::create(['id' => $args['id']]);
            }
    
            $check = (empty($body['param_value_int']) || Validator::intVal()->validate($body['param_value_int']));
            $check = $check && (empty($body['param_value_string']) || Validator::stringType()->validate($body['param_value_string']));
            if (!$check) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
            }
    
            $body['id'] = $args['id'];
            ParameterModel::update($body);
        }

        HistoryController::add([
            'tableName' => 'parameters',
            'recordId'  => $args['id'],
            'eventType' => 'UP',
            'info'      => _PARAMETER_MODIFICATION . " : {$args['id']}",
            'moduleId'  => 'parameter',
            'eventId'   => 'parameterModification',
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        ParameterModel::delete(['id' => $aArgs['id']]);
        HistoryController::add([
            'tableName' => 'parameters',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _PARAMETER_SUPPRESSION . " : {$aArgs['id']}",
            'moduleId'  => 'parameter',
            'eventId'   => 'parameterSuppression',
        ]);

        $parameters = ParameterModel::get();
        foreach ($parameters as $key => $parameter) {
            if (!empty($parameter['param_value_string'])) {
                $parameters[$key]['value'] = $parameter['param_value_string'];
            } elseif (!empty($parameter['param_value_int'])) {
                $parameters[$key]['value'] = $parameter['param_value_int'];
            } elseif (!empty($parameter['param_value_date'])) {
                $parameters[$key]['value'] = $parameter['param_value_date'];
            }
        }

        return $response->withJson(['parameters' => $parameters]);
    }

    public function getM2MConfiguration(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $xmlConfig = ReceiveMessageExchangeController::readXmlConfig();

        $attachmentType = AttachmentTypeModel::getByTypeId(['select' => ['id'], 'typeId' => $xmlConfig['res_attachments']['attachment_type']]);
        $status         = StatusModel::getById(['select' => ['identifier'], 'id' => $xmlConfig['res_letterbox']['status']]);

        $config = [
            "metadata" => [
                'typeId'           => (int)$xmlConfig['res_letterbox']['type_id'],
                'statusId'         => (int)$status['identifier'],
                'priorityId'       => $xmlConfig['res_letterbox']['priority'],
                'indexingModelId'  => (int)$xmlConfig['res_letterbox']['indexingModelId'],
                'attachmentTypeId' => (int)$attachmentType['id']
            ],
            'basketToRedirect' => $xmlConfig['basketRedirection_afterUpload'][0],
            'communications' => [
                'email' => $xmlConfig['m2m_communication_type']['email'],
                'uri'   => $xmlConfig['m2m_communication_type']['url']
            ]
        ];


        $config['annuary']['enabled']      = $xmlConfig['annuaries']['enabled'] == "true" ? true : false;
        $config['annuary']['organization'] = $xmlConfig['annuaries']['organization'] ?? null;

        if (!is_array($xmlConfig['annuaries']['annuary'])) {
            $xmlConfig['annuaries']['annuary'] = [$xmlConfig['annuaries']['annuary']];
        }
        foreach ($xmlConfig['annuaries']['annuary'] as $value) {
            $config['annuary']['annuaries'][] = [
                'uri'      => (string)$value->uri,
                'baseDN'   => (string)$value->baseDN,
                'login'    => (string)$value->login,
                'password' => (string)$value->password,
                'ssl'      => (string)$value->ssl == "true" ? true : false
            ];
        }

        return $response->withJson(['configuration' => $config]);
    }

    public function setM2MConfiguration(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body = $body['configuration'];
        
        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is empty']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['basketToRedirect'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body basketToRedirect is empty, not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['metadata']['priorityId'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body[metadata] priorityId is empty or not a string']);
        }

        foreach (['attachmentTypeId', 'indexingModelId', 'statusId', 'typeId'] as $value) {
            if (!Validator::intVal()->notEmpty()->validate($body['metadata'][$value] ?? null)) {
                return $response->withStatus(400)->withJson(['errors' => 'Body[metadata] ' . $value . ' is empty, not a string']);
            }
        }

        $basket = BasketModel::getByBasketId(['select' => [1], 'basketId' => $body['basketToRedirect']]);
        if (empty($basket)) {
            return $response->withStatus(400)->withJson(['errors' => 'Basket not found', 'lang' => 'basketDoesNotExist']);
        }

        $priority = PriorityModel::getById(['select' => [1], 'id' => $body['metadata']['priorityId']]);
        if (empty($priority)) {
            return $response->withStatus(400)->withJson(['errors' => 'Priority not found', 'lang' => 'priorityDoesNotExist']);
        }

        $attachmentType = AttachmentTypeModel::getById(['select' => ['type_id'], 'id' => $body['metadata']['attachmentTypeId']]);
        if (empty($attachmentType)) {
            return $response->withStatus(400)->withJson(['errors' => 'Basket not found', 'lang' => 'attachmentTypeDoesNotExist']);
        }

        $indexingModel = IndexingModelModel::getById(['select' => [1], 'id' => $body['metadata']['indexingModelId']]);
        if (empty($indexingModel)) {
            return $response->withStatus(400)->withJson(['errors' => 'Basket not found', 'lang' => 'indexingModelDoesNotExist']);
        }

        $status = StatusModel::getByIdentifier(['select' => ['id'], 'identifier' => $body['metadata']['statusId']]);
        if (empty($status)) {
            return $response->withStatus(400)->withJson(['errors' => 'Basket not found', 'lang' => 'statusDoesNotExist']);
        }

        $doctype = DoctypeModel::getById(['select' => [1], 'id' => $body['metadata']['typeId']]);
        if (empty($doctype)) {
            return $response->withStatus(400)->withJson(['errors' => 'Basket not found', 'lang' => 'typeIdDoesNotExist']);
        }

        $customId = CoreConfigModel::getCustomId();
        $path = "custom/{$customId}/apps/maarch_entreprise/xml/m2m_config.xml";
        if (!file_exists($path)) {
            copy("apps/maarch_entreprise/xml/m2m_config.xml", $path);
        }

        $communication = [];
        foreach ($body['communications'] as $value) {
            if (!empty($value)) {
                $communication[] = $value;
            }
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => $path]);
        $loadedXml->res_letterbox->type_id           = $body['metadata']['typeId'];
        $loadedXml->res_letterbox->status            = $status[0]['id'];
        $loadedXml->res_letterbox->priority          = $body['metadata']['priorityId'];
        $loadedXml->res_letterbox->indexingModelId   = $body['metadata']['indexingModelId'];
        $loadedXml->res_attachments->attachment_type = $attachmentType['type_id'];
        $loadedXml->basketRedirection_afterUpload    = $body['basketToRedirect'];
        $loadedXml->m2m_communication                = implode(',', $communication);

        unset($loadedXml->annuaries);
        $loadedXml->annuaries->enabled      = $body['annuary']['enabled'] ? 'true' : 'false';
        $loadedXml->annuaries->organization = $body['annuary']['organization'] ?? '';

        if ($body['annuary']['enabled'] && !empty($body['annuary']['annuaries'])) {
            foreach ($body['annuary']['annuaries'] as $value) {
                $annuary = $loadedXml->annuaries->addChild('annuary');
                $annuary->addChild('uri', $value['uri']);
                $annuary->addChild('baseDN', $value['baseDN']);
                $annuary->addChild('login', $value['login']);
                $annuary->addChild('password', $value['password']);
                $annuary->addChild('ssl', $value['ssl'] ? 'true' : 'false');
            }
        }

        $res = ParameterController::formatXml($loadedXml);
        $fp = fopen($path, "w+");
        if ($fp) {
            fwrite($fp, $res);
        }

        return $response->withStatus(204);
    }

    public static function formatXml($simpleXMLElement)
    {
        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($simpleXMLElement->asXML());

        return $xmlDocument->saveXML();
    }
}
