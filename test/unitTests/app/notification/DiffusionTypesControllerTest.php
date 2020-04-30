<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   DiffusionTypesControllerTest
*
* @author  dev <dev@maarch.org>
* @ingroup notifications
*/
use PHPUnit\Framework\TestCase;

class DiffusionTypesControllerTest extends TestCase
{
    private static $id = null;

    public function testGetRecipientsByContact()
    {
        $diffusionTypesController = new \Notification\controllers\DiffusionTypesController();

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'contact'
                ],
                'request' => 'recipients',
                'event' => [
                    'record_id' => $resId
                ]
            ];
    
            $response = $diffusionTypesController->getItemsToNotify($args);
            foreach ($response as $contact) {
                $this->assertNotEmpty($contact['user_id']);
                $this->assertIsInt($contact['user_id']);
                $this->assertNotEmpty($contact['mail']);
                $this->assertIsString($contact['mail']);
            }

            $args['request'] = 'others';
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);
        }
    }

    public function testGetRecipientsByCopie()
    {
        $diffusionTypesController = new \Notification\controllers\DiffusionTypesController();

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'copy_list'
                ],
                'request' => 'recipients',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            foreach ($response as $user) {
                $this->assertSame(20, $user['id']);
                $this->assertSame('jjonasz', $user['user_id']);
                $this->assertSame('Jean', $user['firstname']);
                $this->assertSame('JONASZ', $user['lastname']);
                $this->assertEmpty($user['phone']);
                $this->assertSame('support@maarch.fr', $user['mail']);
                $this->assertSame('OK', $user['status']);
            }

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);
        }

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'copy_list'
                ],
                'request' => 'res_id',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertSame($resId, $response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsNumeric($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsNumeric($response);
        }
    }

    public function testGetRecipientsByDestUser()
    {
        $diffusionTypesController = new \Notification\controllers\DiffusionTypesController();

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_user',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT'
                ],
                'request' => 'recipients',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            foreach ($response as $user) {
                $this->assertSame(19, $user['id']);
                $this->assertSame('bbain', $user['user_id']);
                $this->assertSame('Barbara', $user['firstname']);
                $this->assertSame('BAIN', $user['lastname']);
                $this->assertEmpty($user['phone']);
                $this->assertSame('support@maarch.fr', $user['mail']);
                $this->assertSame('OK', $user['status']);
            }

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            foreach ($response as $user) {
                $this->assertSame(19, $user['id']);
                $this->assertSame('bbain', $user['user_id']);
                $this->assertSame('Barbara', $user['firstname']);
                $this->assertSame('BAIN', $user['lastname']);
                $this->assertEmpty($user['phone']);
                $this->assertSame('support@maarch.fr', $user['mail']);
                $this->assertSame('OK', $user['status']);
            }

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            foreach ($response as $user) {
                $this->assertSame(11, $user['id']);
                $this->assertSame('aackermann', $user['user_id']);
                $this->assertSame('Amanda', $user['firstname']);
                $this->assertSame('ACKERMANN', $user['lastname']);
                $this->assertEmpty($user['phone']);
                $this->assertSame('support@maarch.fr', $user['mail']);
                $this->assertSame('OK', $user['status']);
            }
        }

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_user',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT'
                ],
                'request' => 'res_id',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertSame($resId, $response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);
        }
    }

    public function testGetRecipientsByDestEntity()
    {
        $diffusionTypesController = new \Notification\controllers\DiffusionTypesController();

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_entity'
                ],
                'request' => 'recipients',
                'event' => [
                    'record_id' => $resId
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            foreach ($response as $entity) {
                $this->assertSame('PJS', $entity['entity_id']);
                $this->assertSame('Y', $entity['enabled']);
                $this->assertSame('support@maarch.fr', $entity['mail']);
            }
        }

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_entity',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT'
                ],
                'request' => 'res_id',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertSame($resId, $response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);
        }
    }

    public function testGetRecipientsByDestUserSign()
    {
        $diffusionTypesController = new \Notification\controllers\DiffusionTypesController();

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_user_sign',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT,ESIG,EVIS'
                ],
                'request' => 'recipients',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);
        }

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_user_sign',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT'
                ],
                'request' => 'res_id',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertSame($resId, $response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);
        }
    }

    public function testGetRecipientsByDestUserVisa()
    {
        $diffusionTypesController = new \Notification\controllers\DiffusionTypesController();

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_user_visa',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT,ESIG,EVIS'
                ],
                'request' => 'recipients',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertIsArray($response);
        }

        foreach ($GLOBALS['resources'] as $resId) {
            $args = [
                'notification' => [
                    'diffusion_type' => 'dest_user_visa',
                    'diffusion_properties' => 'NEW,COU,CLO,END,ATT,VAL,INIT'
                ],
                'request' => 'res_id',
                'event' => [
                    'record_id' => $resId,
                    'table_name' => 'res_letterbox'
                ]
            ];

            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertSame($resId, $response);

            $args['event']['table_name'] = 'notes';
            $args['event']['record_id'] = 1;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);

            $args['event']['table_name'] = 'listinstance';
            $args['event']['user_id'] = 19;
            $response = $diffusionTypesController->getItemsToNotify($args);
            $this->assertEmpty($response);
        }
    }
}
