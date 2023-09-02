<?php

namespace Caretaker\CaretakerInstance\Service\Test;

use Caretaker\Caretaker\Constants;
use Caretaker\Caretaker\Entity\Result\TestResult;

class BlacklistedRecordsTestService extends RemoteTestServiceBase
{
    /**
     * @return TestResult
     */
    public function runTest()
    {
        $table = $this->getConfigValue('table');
        $field = $this->getConfigValue('field');
        $blacklist = explode(chr(10), $this->getConfigValue('blacklist'));

        $operations = array();
        foreach ($blacklist as $value) {
            $value = trim($value);
            if (strlen($value)) {
                $operations[] = array(
                    'GetRecord',
                    array('table' => $table, 'field' => $field, 'value' => $value, 'checkEnableFields' => true),
                );
            }
        }

        $commandResult = $this->executeRemoteOperations($operations);

        if (!$this->isCommandResultSuccessful($commandResult)) {
            return $this->getFailedCommandResultTestResult($commandResult);
        }

        $values = array();

        $results = $commandResult->getOperationResults();
        foreach ($results as $operationResult) {
            if ($operationResult->isSuccessful()) {
                $value = $operationResult->getValue();
                if ($value !== false) {
                    $values[] = $value[$field];
                }
            } else {
                return $this->getFailedOperationResultTestResult($operationResult);
            }
        }

        $blacklistedValuesFound = array();
        foreach ($blacklist as $value) {
            if (in_array($value, $values)) {
                $blacklistedValuesFound[] = $value;
            }
        }
        if (count($blacklistedValuesFound) > 0) {
            return TestResult::create(Constants::state_error, 0, 'Values [' . implode(',', $blacklistedValuesFound) . '] in ' . $table . '.' . $field . ' are blacklisted and should not be active.');
        }

        return TestResult::create(Constants::state_ok, 0, '');
    }
}
