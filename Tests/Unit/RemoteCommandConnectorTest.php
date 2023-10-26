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
 * Testcase for the RemoteCommandConnector
 *
 * @author        Christopher Hlubek <hlubek (at) networkteam.com>
 * @author        Tobias Liebig <liebig (at) networkteam.com>
 */
class RemoteCommandConnectorTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\tx_caretakerinstance_ISecurityManager
     */
    protected $securityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\tx_caretakerinstance_ICryptoManager
     */
    protected $cryptoManager;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $publicKey;

    public function setUp(): void
    {
        $this->securityManager = $this->getMockBuilder('tx_caretakerinstance_ISecurityManager')
            ->getMock();
        $this->cryptoManager = $this->getMockBuilder('tx_caretakerinstance_ICryptoManager')
            ->getMock();

        $this->privateKey = 'YTozOntpOjA7czo4OiLphXB4HJXTjyI7aToxO3M6ODoidb0GlUnBGmgiO2k6MjtzOjc6InByaXZhdGUiO30=';
        $this->publicKey = 'YTozOntpOjA7czo4OiLphXB4HJXTjyI7aToxO3M6MzoiAQABIjtpOjI7czo2OiJwdWJsaWMiO30=';
    }

    public function testExecuteOperationsReturnsValidCommandResult(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\tx_caretaker_InstanceNode $instance */
        $instance = $this->getMockBuilder('tx_caretaker_InstanceNode')
            ->setMethods(['getUrl', 'getPublicKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $instance->expects(self::atLeastOnce())->method('getUrl')->willReturn('http:://foo.bar/');
        $instance->expects(self::atLeastOnce())->method('getPublicKey')->willReturn('publicKey');

        // Mock the http/curl request
        /** @var \PHPUnit_Framework_MockObject_MockObject|\tx_caretakerinstance_RemoteCommandConnector $connector */
        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['requestSessionToken', 'buildCommandRequest', 'executeRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();
        $request = $this->getMockBuilder('tx_caretakerinstance_CommandRequest')
            ->setMethods(['setSignature'])
            ->setConstructorArgs([[]])
            ->getMock();
        $exceptedResult = new \tx_caretakerinstance_CommandResult(true, ['foo' => 'bar'], 'foobar');

        $connector->expects(self::once())->method('requestSessionToken')->willReturn('SessionToken');
        $connector->expects(self::once())->method('buildCommandRequest')->willReturn($request);
        $connector->expects(self::once())->method('executeRequest')->with(self::isInstanceOf('\tx_caretakerinstance_CommandRequest'))->willReturn($exceptedResult);
        $request->expects(self::once())->method('setSignature');

        $connector->setInstance($instance);
        $result = $connector->executeOperations(['foo' => 'bar']);

        self::assertInstanceOf('\tx_caretakerinstance_CommandResult', $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals($exceptedResult, $result);
    }

    public function testExecuteOperationsReturnsFalseResultIfSessionTokenIsInvalid(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $instance = $this->getMockBuilder('tx_caretaker_InstanceNode')
            ->getMock();
        $instance->expects(self::any())->method('getUrl')->willReturn('http:://foo.bar/');
        $instance->expects(self::any())->method('getPublicKey')->willReturn('publicKey');

        // Mock the http/curl request
        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['requestSessionToken', 'executeRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();

        $connector->setInstance($instance);
        $connector->expects(self::once())->method('requestSessionToken')->will(self::throwException(new \tx_caretakerinstance_RequestSessionTokenFailedException()));
        $connector->expects(self::never())->method('executeRequest');

        $result = $connector->executeOperations(['foo' => 'bar']);

        self::assertInstanceOf('\tx_caretakerinstance_CommandResult', $result);
        self::assertFalse($result->isSuccessful());
    }

    public function testExecuteOperationsReturnsFalseResultIfURLMissing(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $instance = $this->getMockBuilder('tx_caretaker_InstanceNode')
            ->getMock();
        $instance->expects(self::any())->method('getUrl')->willReturn('');
        $instance->expects(self::any())->method('getPublicKey')->willReturn('publicKey');

        $connector = new \tx_caretakerinstance_RemoteCommandConnector($this->cryptoManager, $this->securityManager);
        $connector->setInstance($instance);

        $result = $connector->executeOperations(['foo' => 'bar']);

        self::assertInstanceOf('\tx_caretakerinstance_CommandResult', $result);
        self::assertFalse($result->isSuccessful());
    }

    public function testGetCommandRequestCreatesValidEncryptedCommandRequest(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $connector = new \tx_caretakerinstance_RemoteCommandConnector($this->cryptoManager, $this->securityManager);

        $this->cryptoManager->expects(self::once())->method('encrypt')->willReturn('encryptedString');

        $request = $connector->buildCommandRequest('sessionToken', 'publicKey', 'http://foo.barr/', 'rawData');

        self::assertInstanceOf('\tx_caretakerinstance_CommandRequest', $request);
        self::assertEquals('sessionToken', $request->getSessionToken());
        self::assertEquals('publicKey', $request->getServerKey());
        self::assertEquals('http://foo.barr/', $request->getServerUrl());
        self::assertEquals('{"encrypted":"encryptedString"}', $request->getRawData());
        self::assertEquals('{"encrypted":"encryptedString"}', $request->getData());
    }

    public function testRequestSessionTokenReturnsValidToken(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $url = 'http://foo.bar/';
        $fakeSessionToken = '1242475687:d566026bfd3aa7d2d5de8a70ea525a0c4c578cdc45b8';

        $instance = $this->getMockBuilder('tx_caretaker_InstanceNode')
            ->getMock();
        $instance->expects(self::any())->method('getUrl')->willReturn($url);
        $instance->expects(self::any())->method('getPublicKey')->willReturn('publicKey');

        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['executeHttpRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();

        $connector->expects(self::once())->method('executeHttpRequest')
            ->with(self::equalTo($url . '?eID=\tx_caretakerinstance&rst=1'))
            ->willReturn(
                ['response' => $fakeSessionToken, 'info' => ['http_code' => 200]]
            );

        $connector->setInstance($instance);
        $sessionToken = $connector->requestSessionToken();

        self::assertEquals($fakeSessionToken, $sessionToken);
    }

    public function testRequestSessionTokenThrowsExceptionWithInvalidToken(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $url = 'http://foo.bar/';
        $fakeSessionToken = '==invalidtoken==';

        $instance = $this->getMockBuilder('tx_caretaker_InstanceNode')
            ->getMock();
        $instance->expects(self::any())->method('getUrl')->willReturn($url);
        $instance->expects(self::any())->method('getPublicKey')->willReturn('publicKey');

        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['executeHttpRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();

        $connector->expects(self::once())
            ->method('executeHttpRequest')
            ->with(self::equalTo($url . '?eID=\tx_caretakerinstance&rst=1'))
            ->willReturn(
                ['response' => $fakeSessionToken, 'info' => ['http_code' => 200]]
            );

        $connector->setInstance($instance);

        try {
            $connector->requestSessionToken();
            self::fail("requestSessionToken should throw an \tx_caretakerinstance_RequestSessionTokenFailedException exception");
        } catch (\tx_caretakerinstance_RequestSessionTokenFailedException) {
            // ok
        } catch (Exception) {
            self::fail("requestSessionToken should throw an \tx_caretakerinstance_RequestSessionTokenFailedException exception");
        }
    }

    public function testRequestSessionTokenThrowsExceptionIfHttpRequestFails(): void
    {
        self::markTestSkipped('Accesses tx_caretaker classes, which cant be found');

        $url = 'http://foo.bar/';
        $fakeSessionToken = '1242475687:d566026bfd3aa7d2d5de8a70ea525a0c4c578cdc45b8';

        $instance = $this->getMockBuilder('tx_caretaker_InstanceNode')
            ->getMock();
        $instance->expects(self::any())->method('getUrl')->willReturn($url);
        $instance->expects(self::any())->method('getPublicKey')->willReturn('publicKey');

        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['executeHttpRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();

        $connector->expects(self::once())->method('executeHttpRequest')
            ->with(self::equalTo($url . '?eID=\tx_caretakerinstance&rst=1'))
            ->willReturn(
                ['response' => $fakeSessionToken, 'info' => ['http_code' => 404]]
            );

        $connector->setInstance($instance);

        try {
            $connector->requestSessionToken();
            self::fail("requestSessionToken should throw an \tx_caretakerinstance_RequestSessionTokenFailedException exception");
        } catch (\tx_caretakerinstance_RequestSessionTokenFailedException) {
            // ok
        } catch (Exception) {
            self::fail("requestSessionToken should throw an \tx_caretakerinstance_RequestSessionTokenFailedException exception");
        }
    }

    public function testGetRequestSignature(): void
    {
        $request = $this->getMockBuilder('tx_caretakerinstance_CommandResult')
            ->setMethods(['getDataForSignature'])
            ->setConstructorArgs([[]])
            ->getMock();
        // FIXME: interface for CommandResult?

        $request->expects(self::once())->method('getDataForSignature')->willReturn('==SomeData==');
        $this->cryptoManager->expects(self::once())->method('createSignature')->with()->willReturn('==aSignature==');

        $connector = new \tx_caretakerinstance_RemoteCommandConnector($this->cryptoManager, $this->securityManager);

        $signature = $connector->getRequestSignature($request);

        self::assertEquals('==aSignature==', $signature);
    }

    public function testExecuteRequestCreatesValidCommandResult(): void
    {
        $url = 'http://foo.bar/';

        $request = $this->getMockBuilder('tx_caretakerinstance_CommandResult')
            ->setMethods(['getSessionToken', 'getData', 'getSignature', 'getServerUrl'])
            ->setConstructorArgs([[]])
            ->getMock();

        $request->expects(self::once())->method('getSessionToken')->willReturn('==sessionToken==');
        $request->expects(self::once())->method('getData')->willReturn('==data==');
        $request->expects(self::once())->method('getSignature')->willReturn('==Signature==');
        $request->expects(self::once())->method('getServerUrl')->willReturn($url);

        $this->securityManager->expects(self::once())->method('decodeResult')->with(self::equalTo('==encryptedString=='))->willReturn('{"status":true,"results":[{"status":true,"value":"foo"},{"status":true,"value":false},{"status":true,"value":["foo","bar"]}],"message":"Test message"}');

        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['executeHttpRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();

        // Mock session token request
        $connector->expects(self::once())->method('executeHttpRequest')
            ->with(
                self::equalTo($url),
                self::equalTo(['d' => '==data==', 'st' => '==sessionToken==', 's' => '==Signature=='])
            )->willReturn(
                ['response' => '==encryptedString==', 'info' => ['http_code' => 200]]
            );

        $result = $connector->executeRequest($request);

        self::assertInstanceOf('\tx_caretakerinstance_CommandResult', $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals('Test message', $result->getMessage());
        self::assertEquals([new \tx_caretakerinstance_OperationResult(true, 'foo'), new \tx_caretakerinstance_OperationResult(true, false), new \tx_caretakerinstance_OperationResult(true, ['foo', 'bar'])], $result->getOperationResults());
    }

    public function testExecuteRequestReturnsFalseCommandResultOnFailure(): void
    {
        $url = 'http://foo.bar/';

        $request = $this->getMockBuilder('tx_caretakerinstance_CommandResult')
            ->setMethods(['getSessionToken', 'getData', 'getSignature', 'getServerUrl'])
            ->setConstructorArgs([[]])
            ->getMock();

        $request->expects(self::once())->method('getSessionToken')->willReturn('==sessionToken==');
        $request->expects(self::once())->method('getData')->willReturn('==data==');
        $request->expects(self::once())->method('getSignature')->willReturn('==Signature==');
        $request->expects(self::once())->method('getServerUrl')->willReturn($url);

        $connector = $this->getMockBuilder('tx_caretakerinstance_RemoteCommandConnector')
            ->setMethods(['executeHttpRequest'])
            ->setConstructorArgs([$this->cryptoManager, $this->securityManager])
            ->getMock();

        // Mock session token request
        $connector->expects(self::once())->method('executeHttpRequest')
            ->with(
                self::equalTo($url),
                self::equalTo(['d' => '==data==', 'st' => '==sessionToken==', 's' => '==Signature=='])
            )->willReturn(
                ['response' => 'AnyStringButJson', 'info' => ['http_code' => 404]]
            );

        $result = $connector->executeRequest($request);

        self::assertInstanceOf('\tx_caretakerinstance_CommandResult', $result);
        self::assertFalse($result->isSuccessful());
        self::assertNull($result->getOperationResults());
    }
}
