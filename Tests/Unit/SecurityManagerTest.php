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
 * Testcase for the SecurityManager
 *
 * @author        Christopher Hlubek <hlubek (at) networkteam.com>
 * @author        Tobias Liebig <liebig (at) networkteam.com>
 */
class SecurityManagerTest extends UnitTestCase
{
    /**
     * @var \tx_caretakerinstance_ICryptoManager
     */
    protected $cryptoManager;

    /**
     * @var \tx_caretakerinstance_ISecurityManager
     */
    protected $securityManager;

    /**
     * @var \tx_caretakerinstance_CommandRequest
     */
    protected $commandRequest;

    public function setUp(): void
    {
        $this->cryptoManager = $this->getMockBuilder('tx_caretakerinstance_ICryptoManager')
            ->getMock();

        $this->securityManager = new \tx_caretakerinstance_SecurityManager($this->cryptoManager);
        $this->securityManager->setPrivateKey('FakePrivateKey');
        $this->securityManager->setClientPublicKey('FakeClientPublicKey');

        $this->commandRequest = new \tx_caretakerinstance_CommandRequest(
            [
                'session_token' => '12345:abcdefg',
                'client_info' => ['host_address' => '192.168.10.100'],
                'data' => [
                    // Unpacked from raw data.
                    'operations' => [
                        ['mock', ['foo' => 'bar']],
                        ['mock', ['foo' => 'bar']],
                    ],
                    // Fake crypted JSON
                    'encrypted' => 'xxer4rt34x',
                ],
                // Data in JSON raw (fake)
                'raw' => '{"foo": "bar"}',
                // Signature over raw data and session token sent from client
                'signature' => 'abcdefg',
            ]
        );
    }

    public function testCreateSessionToken(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('createSessionToken')
            ->with(self::equalTo(time()), self::equalTo('FakePrivateKey'))
            ->willReturn('me_is_a_token');

        $token = $this->securityManager->createSessionToken('192.168.10.100');
        self::assertEquals('me_is_a_token', $token);
    }

    public function testClientRestrictionForSessionTokenCreation(): void
    {
        $this->securityManager->setClientHostAddressRestriction('192.168.10.200');

        $this->cryptoManager->expects(self::never())
            ->method('createSessionToken');

        $token = $this->securityManager->createSessionToken('192.168.10.100');
        self::assertFalse($token);
    }

    public function testDecodeRequest(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('decrypt')
            ->with(self::equalTo('xxer4rt34x'), self::equalTo('FakePrivateKey'))
            ->willReturn('{"secret": "top-secret"}');

        self::assertTrue($this->securityManager->decodeRequest($this->commandRequest));

        $data = $this->commandRequest->getData();
        self::assertEquals($data['foo'], 'bar', 'Plain JSON data was decoded');
        self::assertEquals($data['secret'], 'top-secret', 'Encrypted JSON data was decoded');
    }

    public function testDecodeInvalidEncryptedRequest(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('decrypt')
            ->willReturn(false);

        self::assertFalse($this->securityManager->decodeRequest($this->commandRequest));
    }

    public function testValidateValidRequest(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('verifySessionToken')
            ->with(self::equalTo('12345:abcdefg'), self::equalTo('FakePrivateKey'))
            ->willReturn(time() - 1);

        $this->cryptoManager->expects(self::any())
            ->method('verifySignature')
            ->willReturn(true);

        self::assertTrue($this->securityManager->validateRequest($this->commandRequest));
    }

    public function testValidateExpiredRequest(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('verifySessionToken')
            ->with(self::equalTo('12345:abcdefg'), self::equalTo('FakePrivateKey'))
            ->willReturn(time() - ($this->securityManager->getSessionTokenExpiration() + 1));

        $this->cryptoManager->expects(self::any())
            ->method('verifySignature')
            ->willReturn(true);

        $this->expectException('\tx_caretakerinstance_SessionTokenException');
        $this->securityManager->validateRequest($this->commandRequest);
    }

    public function testClientRestrictionForRequestValidation(): void
    {
        $this->securityManager->setClientHostAddressRestriction('192.168.10.200');

        $this->cryptoManager->expects(self::once())
            ->method('verifySessionToken')
            ->willReturn(time() - 1);

        $this->cryptoManager->expects(self::any())
            ->method('verifySignature')
            ->willReturn(true);

        $this->expectException('\tx_caretakerinstance_ClientHostAddressRestrictionException');
        $this->securityManager->validateRequest($this->commandRequest);
    }

    public function testValidationVerifiesSignature(): void
    {
        $this->cryptoManager->expects(self::any())
            ->method('verifySessionToken')
            ->willReturn(time() - 1);

        $this->cryptoManager->expects(self::once())
            ->method('verifySignature')
            // Verify session token and raw data
            ->with(
                self::equalTo('12345:abcdefg${"foo": "bar"}'),
                self::equalTo('abcdefg'),
                self::equalTo('FakeClientPublicKey')
            )
            ->willReturn(true);

        self::assertTrue($this->securityManager->validateRequest($this->commandRequest));
    }

    public function testWrongSignatureDoesntValidate(): void
    {
        $this->cryptoManager->expects(self::any())
            ->method('verifySessionToken')
            ->willReturn(time() - 1);

        $this->cryptoManager->expects(self::any())
            ->method('verifySignature')
            ->willReturn(false);

        $this->expectException('\tx_caretakerinstance_SignaturValidationException');
        $this->securityManager->validateRequest($this->commandRequest);
    }

    public function testEncodeResultEncodesStringWithClientPublicKey(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('encrypt')
            ->with(self::equalTo('My result data'), self::equalTo('FakeClientPublicKey'))
            ->willReturn('Encoded result');

        $encodedResult = $this->securityManager->encodeResult('My result data');
        self::assertEquals('Encoded result', $encodedResult);
    }

    public function testEncodeResultDecodesStringWithPrivateKey(): void
    {
        $this->cryptoManager->expects(self::once())
            ->method('decrypt')
            ->with(self::equalTo('Encoded result'), self::equalTo('FakePrivateKey'))
            ->willReturn('My result data');

        $encodedResult = $this->securityManager->decodeResult('Encoded result');
        self::assertEquals('My result data', $encodedResult);
    }
}
