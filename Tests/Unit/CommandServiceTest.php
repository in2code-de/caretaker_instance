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
 * Testcase for the CommandService
 *
 * @author        Christopher Hlubek <hlubek (at) networkteam.com>
 * @author        Tobias Liebig <liebig (at) networkteam.com>
 */
class CommandServiceTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\tx_caretakerinstance_SecurityManager
     */
    protected $securityManager;

    /**
     * @var \tx_caretakerinstance_CommandService
     */
    protected $commandService;

    /**
     * @var \tx_caretakerinstance_CommandRequest
     */
    protected $commandRequest;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\tx_caretakerinstance_OperationManager
     */
    protected $operationManager;

    public function setUp(): void
    {
        $this->operationManager = $this->getMockBuilder('tx_caretakerinstance_OperationManager')
            ->setMethods(['executeOperation'])
            ->getMock();

        $this->securityManager = $this->getMockBuilder('tx_caretakerinstance_ISecurityManager')
            ->getMock();

        $this->commandService = new \tx_caretakerinstance_CommandService(
            $this->operationManager,
            $this->securityManager
        );

        $this->commandRequest = new \tx_caretakerinstance_CommandRequest(
            [
                'data' => [
                    'operations' => [
                        [
                            'mock',
                            ['foo' => 'bar'],
                        ],
                        [
                            'mock',
                            ['foo' => 'bar'],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testWrapCommandResultEncodesResult(): void
    {
        $result = new \tx_caretakerinstance_CommandResult(
            true,
            new \tx_caretakerinstance_OperationResult(true, ['foo' => 'bar'])
        );

        $data = $result->toJson();

        $this->securityManager->expects(self::once())
            ->method('encodeResult')
            ->with(self::equalTo($data))
            ->willReturn('Encoded result data');

        $wrap = $this->commandService->wrapCommandResult($result);

        self::assertEquals('Encoded result data', $wrap);
    }

    public function testExecuteCommandWithSecurity(): void
    {
        $this->securityManager->expects(self::once())
            ->method('validateRequest')
            ->with(self::equalTo($this->commandRequest))
            ->willReturn(true);

        $this->securityManager->expects(self::once())
            ->method('decodeRequest')
            ->with(self::equalTo($this->commandRequest))
            ->willReturn(true);

        $this->operationManager->expects(self::exactly(2))
            ->method('executeOperation')
            ->with(self::equalTo('mock'), self::equalTo(['foo' => 'bar']))
            ->willReturn(new \tx_caretakerinstance_OperationResult(true, 'bar'));

        $result = $this->commandService->executeCommand($this->commandRequest);

        self::assertInstanceOf('\tx_caretakerinstance_CommandResult', $result);

        self::assertTrue($result->isSuccessful());

        /** @var \tx_caretakerinstance_OperationResult $operationResult */
        foreach ($result->getOperationResults() as $operationResult) {
            self::assertInstanceOf('\tx_caretakerinstance_OperationResult', $operationResult);
            self::assertTrue($operationResult->isSuccessful());
            self::assertEquals('bar', $operationResult->getValue());
        }
    }

    public function testExecuteCommandSecurityCheckFailed(): void
    {
        $this->securityManager->expects(self::once())
            ->method('validateRequest')
            ->with(self::equalTo($this->commandRequest))
            ->willReturn(false);

        $this->securityManager->expects(self::never())
            ->method('decodeRequest');

        $result = $this->commandService->executeCommand($this->commandRequest);

        self::assertFalse($result->isSuccessful());

        self::assertEquals('The request could not be verified', $result->getMessage());
    }

    public function testExecuteCommandDecryptionFailed(): void
    {
        $this->securityManager->expects(self::once())
            ->method('validateRequest')
            ->with(self::equalTo($this->commandRequest))
            ->willReturn(true);

        $this->securityManager->expects(self::once())
            ->method('decodeRequest');

        $this->operationManager->expects(self::never())
            ->method('executeOperation');

        $result = $this->commandService->executeCommand($this->commandRequest);

        self::assertFalse($result->isSuccessful());

        self::assertEquals('The request could not be decrypted', $result->getMessage());
    }

    public function testRequestSessionToken(): void
    {
        $this->securityManager->expects(self::once())
            ->method('createSessionToken')
            ->with(self::equalTo('10.0.0.1'))
            ->willReturn('me-is-token');

        $token = $this->commandService->requestSessionToken('10.0.0.1');
        self::assertEquals('me-is-token', $token);
    }
}
