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
 * Testcase for the ServiceFactory
 *
 * @author        Martin Ficzel <ficzel@work.de>
 */
class ServicesTest extends UnitTestCase
{
    public function testFindInsecureExtensionCommand(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $stub = $this->getMockBuilder('tx_caretakerinstance_FindInsecureExtensionTestService')
            ->setMethods(['getLocationList', 'executeRemoteOperations', 'checkExtension'])
            ->getMock();

        $stub->expects(self::once())
            ->method('getLocationList')
            ->with()
            ->willReturn(['local']);

        $stub->expects(self::once())
            ->method('executeRemoteOperations')
            ->with(self::equalTo([['GetExtensionList', ['locations' => ['local']]]]))
            ->willReturn(

                new \tx_caretakerinstance_CommandResult(
                    true,
                    [
                        new \tx_caretakerinstance_OperationResult(
                            true,
                            [
                                'tt_address' => [
                                    'isInstalled' => true,
                                    'version' => '2.1.4',
                                    'location' => ['local'],
                                ],
                            ]
                        ),
                    ]
                )

            );

        $stub->expects(self::once())
            ->method('checkExtension')
            ->with()
            ->willReturn(true);

        $result = $stub->runTest();

        self::assertInstanceOf('\tx_caretaker_TestResult', $result);
        self::assertEquals(\tx_caretaker_Constants::state_ok, $result->getState());
    }

    public function providerFindInsecureExtensionGetLocationList(): array
    {
        return [
            [1, ['system']],
            [2, ['global']],
            [4, ['local']],
            [3, ['system', 'global']],
            [6, ['global', 'local']],
        ];
    }

    /**
     * @dataProvider providerFindInsecureExtensionGetLocationList
     */
    public function testFindInsecureExtensionGetLocationList(mixed $input, mixed $output): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $stub = $this->getMockBuilder('tx_caretakerinstance_FindInsecureExtensionTestService')
            ->setMethods(['getConfigValue'])
            ->getMock();

        $stub->expects(self::once())
            ->method('getConfigValue')
            ->with()
            ->willReturn($input);

        self::assertEquals($output, $stub->getLocationList());
    }
}
