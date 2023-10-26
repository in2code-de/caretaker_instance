<?php

namespace Caretaker\CaretakerInstance\Tests\Unit;

use Caretaker\CaretakerInstance\Tests\Unit\Fixtures\DummyOperation;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2011 by n@work GmbH and networkteam GmbH
 *
 * All rights reserved
 *
 * This script is part of the Caretaker project. The Caretaker project
 * is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This is a file of the caretaker project.
 * http://forge.typo3.org/projects/show/extension-caretaker
 *
 * Project sponsored by:
 * n@work GmbH - http://www.work.de
 * networkteam GmbH - http://www.networkteam.com/
 *
 * $Id$
 */

/**
 * Testcase for the ServiceFactory
 *
 * @author        Christopher Hlubek <hlubek (at) networkteam.com>
 * @author        Tobias Liebig <liebig (at) networkteam.com>
 */
class ServiceFactoryTest extends UnitTestCase
{
    public function testCommandServiceFactory(): void
    {
        // Simulate TYPO3 ext conf

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['caretaker_instance'] = [
            'crypto' => [
                'instance' => [
                    'publicKey' => 'FakePublicKey',
                    'privateKey' => 'FakePrivateKey',
                ],
                'client' => [
                    'publicKey' => 'FakeClientPublicKey',
                ],
            ],
            'security' => [
                'clientHostAddressRestriction' => '10.0.0.1',
            ],
        ];

        $factory = \tx_caretakerinstance_ServiceFactory::getInstance();
        $commandService = $factory->getCommandService();

        self::assertInstanceOf('\tx_caretakerinstance_CommandService', $commandService);

        $securityManager = $factory->getSecurityManager();

        self::assertInstanceOf('\tx_caretakerinstance_SecurityManager', $securityManager);

        // Test that properties have been set from extConf
        self::assertEquals('FakePublicKey', $securityManager->getPublicKey());
        self::assertEquals('FakePrivateKey', $securityManager->getPrivateKey());
        self::assertEquals('FakeClientPublicKey', $securityManager->getClientPublicKey());
        self::assertEquals('10.0.0.1', $securityManager->getClientHostAddressRestriction());
    }

    public function testOperationClassRegistrationByConfVars(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['caretaker_instance']['operations'] = ['dummy' => DummyOperation::class];
        $factory = \tx_caretakerinstance_ServiceFactory::getInstance();
        $operationManager = $factory->getOperationManager();

        $result = $operationManager->executeOperation('dummy', ['foo' => 'bar']);

        self::assertEquals('bar', $result->getValue());
    }

    public function testOperationInstanceRegistrationByConfVars(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['caretaker_instance']['operations'] = ['dummyInstance' => new DummyOperation()];
        $factory = \tx_caretakerinstance_ServiceFactory::getInstance();
        $operationManager = $factory->getOperationManager();

        $result = $operationManager->executeOperation('dummyInstance', ['foo' => 'bar']);

        self::assertEquals('bar', $result->getValue());
    }

    public function testRemoteCommandConnector(): void
    {
        $factory = \tx_caretakerinstance_ServiceFactory::getInstance();
        $connector = $factory->getRemoteCommandConnector();

        self::assertInstanceOf('\tx_caretakerinstance_RemoteCommandConnector', $connector);
    }

    public function tearDown(): void
    {
        // Destroy Service Factory singleton after each test
        \tx_caretakerinstance_ServiceFactory::destroy();
    }
}
