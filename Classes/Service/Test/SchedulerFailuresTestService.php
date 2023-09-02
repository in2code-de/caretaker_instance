<?php

namespace Caretaker\CaretakerInstance\Service\Test;

use Caretaker\Caretaker\Constants;
use Caretaker\Caretaker\Entity\Result\TestResult;
use Caretaker\CaretakerInstance\Entity\Operation\OperationResult;

class SchedulerFailuresTestService extends RemoteTestServiceBase
{
    /**
     * {@inheritDoc}
     * @return TestResult
     */
    public function runTest(): TestResult
    {
        $operations = array(
            array('GetScheduler', array()),
        );

        $commandResult = $this->executeRemoteOperations($operations);

        if (!$this->isCommandResultSuccessful($commandResult)) {
            return $this->getFailedCommandResultTestResult($commandResult);
        }

        $results = $commandResult->getOperationResults();

        $errors = array();

        /** @var OperationResult $operationResult */
        foreach ($results as $operationResult) {
            if (!$operationResult->isSuccessful()) {
                $exceptions = $operationResult->getValue();
                if (is_string($exceptions)) {
                    if ('Operation [GetScheduler] unknown' === $exceptions) {
                        return TestResult::create(
                            Constants::state_error,
                            0,
                            'The Instance does not support this Operation. Did you forget to install the additional extension?' . PHP_EOL . 'Original Exception: ' . $exceptions
                        );
                    }
                    return TestResult::create(
                        Constants::state_error,
                        0,
                        'Command execution failed: ' . $exceptions
                    );
                }
                foreach ($exceptions as $taskUid => $exception) {
                    $errors[] = sprintf(
                        'Scheduler [%d] failed with message: Exception %d in %s line %d "%s"',
                        $taskUid,
                        $exception['code'],
                        $exception['file'],
                        $exception['line'],
                        $exception['message']
                    );
                }
            }
        }

        if (!empty($errors)) {
            return TestResult::create(
                Constants::state_error,
                0,
                'Operation execution failed: ' . PHP_EOL . implode(PHP_EOL, $errors)
            );
        }

        return TestResult::create(Constants::state_ok, 0, 'No Scheduler errors found');
    }
}
