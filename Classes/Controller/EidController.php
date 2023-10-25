<?php
namespace Caretaker\CaretakerInstance\Controller;

use Psr\Http\Message\ServerRequestInterface;

class EidController
{
    public function execute(ServerRequestInterface $request): void
    {
        try {
            $factory = \tx_caretakerinstance_ServiceFactory::getInstance();
            $commandService = $factory->getCommandService();

            $remoteAddress = $_SERVER['REMOTE_ADDR'];

            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                if (isset($_GET['rst'])) {
                    $token = $commandService->requestSessionToken($remoteAddress);
                    if ($token === '' || $token === '0') {
                        header('HTTP/1.0 403 Request not allowed');
                    } else {
                        echo $token;
                    }
                } else {
                    header('HTTP/1.0 500 Invalid request');
                }
            } else {
                $sessionToken = null;
                $data = null;
                $signature = null;
                if (isset($_POST['st']) && isset($_POST['d']) && isset($_POST['s'])) {
                    $sessionToken = $_POST['st'];
                    $data = $_POST['d'];
                    $signature = $_POST['s'];
                } else {
                    header('HTTP/1.0 500 Invalid request');
                }

                $request = new \tx_caretakerinstance_CommandRequest(
                    [
                        'session_token' => $sessionToken,
                        'client_info' => [
                            'host_address' => $remoteAddress,
                        ],
                        'data' => [],
                        'raw' => $data,
                        'signature' => $signature,
                    ]
                );

                $result = $commandService->executeCommand($request);

                // TODO Check for result failure and maybe throw a HTTP status code

                echo $commandService->wrapCommandResult($result);
            }
        } catch (\Exception $exception) {
            echo json_encode(
                [
                    'status' => \tx_caretakerinstance_CommandResult::status_undefined,
                    'exception' => [
                        'code' => $exception->getCode(),
                    ],
                    'message' => $exception->getMessage(),
                ],
                JSON_THROW_ON_ERROR
            );
        }

        exit;
    }
}
