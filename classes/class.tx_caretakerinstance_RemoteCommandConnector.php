<?php
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
 * Connect to an Instance and execute a Command (bunch of Operations)
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 */
class tx_caretakerinstance_RemoteCommandConnector
{
    /**
     * @var tx_caretaker_InstanceNode
     */
    protected $instance;

    /**
     * Construct the remote command connector
     *
     * @param $cryptoManager tx_caretakerinstance_ICryptoManager
     * @param $securityManager tx_caretakerinstance_ISecurityManager
     * @return tx_caretakerinstance_RemoteCommandConnector
     */
    public function __construct(protected tx_caretakerinstance_ICryptoManager $cryptoManager, protected tx_caretakerinstance_ISecurityManager $securityManager)
    {
    }

    /**
     * Executes a bunch of operation an a remote instance and takes care to secure/encrypt the communication
     *
     * @param $operations array
     * @return tx_caretakerinstance_CommandResult
     */
    public function executeOperations(array $operations): \tx_caretakerinstance_CommandResult
    {
        if (!$this->getInstanceURL() || !$this->getInstancePublicKey()) {
            return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_error, null, 'No URL or PublicKey of instance given');
        }

        try {
            $sessionToken = $this->requestSessionToken();
        } catch (tx_caretakerinstance_RequestSessionTimeoutException $e) {
            return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_undefined, null, 'Request Session Token failed: ' . $e->getMessage());
        } catch (tx_caretakerinstance_RequestSessionTokenFailedException $e) {
            return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_error, null, 'Request Session Token failed: ' . chr(10) . $e->getMessage());
        } catch (Exception $e) {
            return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_error, null, 'Unknown Exception:' . chr(10) . $e->getMessage());
        }

        $commandRequest = $this->buildCommandRequest(
            $sessionToken,
            $this->getInstancePublicKey(),
            $this->getInstanceURL(),
            $this->getDataFromOperations($operations)
        );
        $commandRequest->setSignature(
            $this->getRequestSignature($commandRequest)
        );

        return $this->executeRequest($commandRequest);
    }

    /**
     * Get the URL of the current instance
     *
     * @return string
     */
    public function getInstanceURL(): bool|string
    {
        if (!$this->instance instanceof \tx_caretaker_InstanceNode || $this->instance->getUrl() === '') {
            return false;
        }

        $baseUrl = $this->instance->getUrl();

        return $baseUrl . (str_ends_with((string)$baseUrl, '/') ? '' : '/') . 'index.php?eID=tx_caretakerinstance';
    }

    /**
     * Get the public key of the current instance
     *
     * @return string
     */
    public function getInstancePublicKey()
    {
        if (!$this->instance instanceof \tx_caretaker_InstanceNode || $this->instance->getPublicKey() === '') {
            return false;
        }

        return $this->instance->getPublicKey();
    }

    /**
     * Create a CommandResult
     *
     * @param bool|int $status
     * @param array $operationResults
     * @param string $message
     * @return tx_caretakerinstance_CommandResult
     */
    protected function getCommandResult($status, $operationResults, $message): \tx_caretakerinstance_CommandResult
    {
        return new tx_caretakerinstance_CommandResult($status, $operationResults, $message);
    }

    /**
     * Request a session token from a remote instance
     *
     * @return string
     * @throws tx_caretakerinstance_RequestSessionTokenFailedException
     * @throws tx_caretakerinstance_RequestSessionTimeoutException
     */
    public function requestSessionToken()
    {
        $requestUrl = $this->getInstanceURL() . '&rst=1';
        $httpRequestResult = $this->executeHttpRequest($requestUrl);

        if (is_array($httpRequestResult)
            && $httpRequestResult['info']['http_code'] === 200
            && preg_match('/^(\d{10}:[a-z0-9].*)$/', (string)$httpRequestResult['response'], $matches)
        ) {
            return $matches[1];
        }

        if ($httpRequestResult['info']['http_code'] === 0) {
            throw new tx_caretakerinstance_RequestSessionTimeoutException('No Response/Timeout (Total-Time: ' . $httpRequestResult['info']['total_time'] . ')');
        }

        $msg = '- HTTP-URL: ' . $httpRequestResult['info']['url'] . chr(10) .
            '- HTTP-Status: ' . $httpRequestResult['info']['http_code'] . chr(10) .
            '- HTTP-Response: ' . $httpRequestResult['response'];
        throw new tx_caretakerinstance_RequestSessionTokenFailedException($msg);
    }

    /**
     * Execute an HTTP request for the POST values via CURL
     *
     * @param string $requestUrl The URL for the HTTP request
     * @param array $postValues POST values with key / value
     * @return array info/response
     */
    protected function executeHttpRequest($requestUrl, $postValues = null)
    {
        $curl = curl_init();
        if (!$curl) {
            return false;
        }

        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $additionalCurlOptions = $this->getCurlOptions();
        if (is_array($additionalCurlOptions)) {
            foreach ($additionalCurlOptions as $key => $value) {
                curl_setopt($curl, $key, $value);
            }
        }

        $headers = ['Cache-Control: no-cache', 'Pragma: no-cache'];

        if (is_array($postValues)) {
            $postQuery = '';
            foreach ($postValues as $key => $value) {
                $postQuery .= urlencode($key) . '=' . urlencode((string)$value) . '&';
            }

            rtrim($postQuery, '&');
            // echo $postQuery;
            $headers[] = 'Content-Length: ' . strlen($postQuery);
            $headers[] = 'Expect:'; // fix Problem with lighthttp
            curl_setopt($curl, CURLOPT_POST, count($postValues));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postQuery);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $info['request_headers'] = $headers;

        curl_close($curl);

        return ['response' => $response, 'info' => $info];
    }

    /**
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->instance->getCurlOptions();
    }

    /**
     * Build a CommandRequest
     *
     * @param string $sessionToken
     * @param string $instancePublicKey
     * @param string $url
     * @param array $rawData
     * @return tx_caretakerinstance_CommandRequest
     */
    public function buildCommandRequest($sessionToken, $instancePublicKey, $url, $rawData): \tx_caretakerinstance_CommandRequest
    {
        $encryptedData = json_encode(['encrypted' => $this->cryptoManager->encrypt($rawData, $instancePublicKey)], JSON_THROW_ON_ERROR);

        return new tx_caretakerinstance_CommandRequest(
            ['session_token' => $sessionToken, 'server_info' => ['server_key' => $instancePublicKey, 'server_url' => $url], 'data' => $encryptedData, 'raw' => $encryptedData]
        );
    }

    /**
     * Create a JSON string of operations
     *
     * @param array $operations
     * @return string json
     */
    protected function getDataFromOperations($operations): string
    {
        return json_encode(
            ['operations' => $operations],
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Get a signature for the given command request
     *
     * @param tx_caretakerinstance_CommandRequest $commandRequest
     * @return string
     */
    public function getRequestSignature($commandRequest)
    {
        return $this->cryptoManager->createSignature(
            $commandRequest->getDataForSignature(),
            $this->securityManager->getPrivateKey()
        );
    }

    /**
     * Execute the given command request
     *
     * @param tx_caretakerinstance_CommandRequest $commandRequest
     * @return tx_caretakerinstance_CommandResult
     */
    public function executeRequest($commandRequest): \tx_caretakerinstance_CommandResult
    {
        $httpRequestResult = $this->executeHttpRequest(
            $commandRequest->getServerUrl(),
            ['st' => $commandRequest->getSessionToken(), 'd' => $commandRequest->getData(), 's' => $commandRequest->getSignature()]
        );

        if (is_array($httpRequestResult)) {
            if ($httpRequestResult['info']['http_code'] === 200) {
                $json = $this->securityManager->decodeResult($httpRequestResult['response']);
                // TODO: check if valid json
                if ($json !== '' && $json !== '0') {
                    return tx_caretakerinstance_CommandResult::fromJson($json);
                }

                if (!empty($httpRequestResult['response'])) {
                    $json = json_decode((string)$httpRequestResult['response'], true, 512, JSON_THROW_ON_ERROR);
                    if ($json && $json['status'] == -1) {
                        return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_undefined, null, 'Error while executing remote command: ' . $json['message'] . ' (' . $json['exception']['code'] . ')');
                    }
                }

                return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_undefined, null, 'Cant decode remote command result');
            }

            if ($httpRequestResult['info']['http_code'] === 0) {
                // seems to be a timeout
                return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_undefined, null, 'No Response/Timeout (Total-Time: ' . $httpRequestResult['info']['total_time'] . ')');
            }

            return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_error, null, 'Invalid result: ' . $httpRequestResult['response'] . chr(10) . 'CURL Info: ' . var_export($httpRequestResult['info'], true));
        }

        return $this->getCommandResult(tx_caretakerinstance_CommandResult::status_error, null, 'Invalid result request could not be executed' . chr(10) . 'CURL Info: ' . var_export($httpRequestResult['info'], true));
    }

    /**
     * Set the current instance
     *
     * @param tx_caretaker_InstanceNode $instance
     */
    public function setInstance($instance): void
    {
        $this->instance = $instance;
    }

    /**
     * Get an encrypted JSON string of operations
     *
     * @param array $operations
     * @param string $publicKey
     * @return string encrypted json
     */
    protected function getEncryptedDataFromOperations($operations, $publicKey)
    {
        // FIXME rawdata / $operations
        $encryptedData = $this->cryptoManager->encrypt($this->getDataFromOperations($operations), $publicKey);

        return $encryptedData;
    }
}
