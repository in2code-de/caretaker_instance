<?php

namespace Caretaker\CaretakerInstance\Tests\Unit;

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
 * Testcase for the OperationManager
 *
 * @author        Christopher Hlubek <hlubek (at) networkteam.com>
 * @author        Tobias Liebig <liebig (at) networkteam.com>
 */
class OperationManagerTest extends UnitTestCase
{
    public function testRegisterOperationAsClass(): void
    {
        $operationManager = new \tx_caretakerinstance_OperationManager();
        $operationManager->registerOperation(
            'get_php_version',
            'tx_caretakerinstance_Operation_GetPHPVersion'
        );
        $operation = $operationManager->getOperation('get_php_version');
        self::assertInstanceOf('\tx_caretakerinstance_Operation_GetPHPVersion', $operation);
    }

    public function testRegisterOperationAsInstance(): void
    {
        $operationManager = new \tx_caretakerinstance_OperationManager();
        $operationManager->registerOperation(
            'get_php_version',
            new \tx_caretakerinstance_Operation_GetPHPVersion()
        );
        $operation = $operationManager->getOperation('get_php_version');
        self::assertInstanceOf('\tx_caretakerinstance_Operation_GetPHPVersion', $operation);
    }

    public function testGetOperationForUnknownOperation(): void
    {
        $operationManager = new \tx_caretakerinstance_OperationManager();
        $operation = $operationManager->getOperation('me_no_operation');
        self::assertFalse($operation);
    }

    public function testExecuteUnknownOperation(): void
    {
        $operationManager = new \tx_caretakerinstance_OperationManager();
        $result = $operationManager->executeOperation('me_no_operation');
        self::assertFalse($result->isSuccessful());
        self::assertEquals('Operation [me_no_operation] unknown', $result->getValue());
    }

    public function testExecuteOperation(): void
    {
        $operationManager = new \tx_caretakerinstance_OperationManager();

        $operation = $this->getMockBuilder('tx_caretakerinstance_IOperation')
            ->setMethods(['execute'])
            ->getMock();
        $operation->expects(self::once())
            ->method('execute')
            ->with(self::equalTo(['foo' => 'bar']))
            ->willReturn(new \tx_caretakerinstance_OperationResult(true, 'bar'));

        $operationManager->registerOperation('mock', $operation);

        $result = $operationManager->executeOperation('mock', ['foo' => 'bar']);
        self::assertTrue($result->isSuccessful());
        self::assertEquals('bar', $result->getValue());
    }
}
