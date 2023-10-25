<?php

class tx_caretakerinstance_BlacklistedRecordsTestService extends tx_caretakerinstance_RemoteTestServiceBase
{
    /**
     * @return tx_caretaker_TestResult
     */
    public function runTest()
    {
        $table = $this->getConfigValue('table');
        $field = $this->getConfigValue('field');
        $blacklist = explode(chr(10), (string)$this->getConfigValue('blacklist'));

        $operations = [];
        foreach ($blacklist as $value) {
            $value = trim($value);
            if (strlen($value) !== 0) {
                $operations[] = [
                    'GetRecord',
                    [
                        'table' => $table,
                        'field' => $field,
                        'value' => $value,
                        'checkEnableFields' => true,
                    ],
                ];
            }
        }

        $commandResult = $this->executeRemoteOperations($operations);

        if (!$this->isCommandResultSuccessful($commandResult)) {
            return $this->getFailedCommandResultTestResult($commandResult);
        }

        $values = [];

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

        $blacklistedValuesFound = [];
        foreach ($blacklist as $value) {
            if (in_array($value, $values)) {
                $blacklistedValuesFound[] = $value;
            }
        }

        if ($blacklistedValuesFound !== []) {
            return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_error, 0, 'Values [' . implode(',', $blacklistedValuesFound) . '] in ' . $table . '.' . $field . ' are blacklisted and should not be active.');
        }

        return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_ok, 0, '');
    }
}
