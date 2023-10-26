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
 * Check content of TYPO3_CONF_VARS
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 */
class tx_caretakerinstance_CheckConfVarsTestService extends tx_caretakerinstance_RemoteTestServiceBase
{
    /**
     * Value Description
     *
     * @var string
     */
    protected $valueDescription = '';

    /**
     * Service type description in human readble form.
     *
     * @var string
     */
    protected $typeDescription = 'LLL:EXT:caretaker_instance/locallang.xml:typo3_conf_vars_test_description';

    /**
     * Template to display the test Configuration in human readable form.
     *
     * @var string
     */
    protected $configurationInfoTemplate = 'LLL:EXT:caretaker_instance/locallang.xml:typo3_conf_vars_test_configuration';

    /**
     * @return mixed
     */
    public function runTest()
    {
        $checkConfVars = explode(chr(10), (string)$this->getConfigValue('checkConfVars'));
        $operations = [];
        foreach ($checkConfVars as $checkConfVar) {
            $checkConfVar = trim($checkConfVar);

            // ignore empty and comment lines
            if ($checkConfVar == '' || str_starts_with($checkConfVar, '#') || str_starts_with($checkConfVar, '//')) {
                continue;
            }

            // detect comparison Opertor by regex
            $matches = [];
            preg_match('/([a-zA-Z0-9\|_]+)[\s]*([\=\!\<\>]{1,2})[\s]*(.*)/', $checkConfVar, $matches);

            if ($matches[1] && $matches[2] && isset($matches[3])) {
                $path = trim($matches[1]);
                $operator = trim($matches[2]);
                $value = trim($matches[3]);

                // numeric comparison
                if (is_numeric($value) && (int)$value == $value) {
                    $value = (int)$value;
                }

                if ($path && $operator) {
                    $operations[] = [
                        'MatchPredefinedVariable',
                        [
                            'key' => 'GLOBALS|TYPO3_CONF_VARS|' . $path,
                            'usingRegexp' => false,
                            'match' => $value,
                            'comparisonOperator' => $operator,
                        ],
                    ];
                }
            } // compare regex on :regex:
            elseif (strpos($checkConfVar, ':regex:') > 0) {
                [$path, $value] = explode(':regex:', $checkConfVar);
                $path = trim($path);
                $value = trim($value);

                if ($path && $value) {
                    $operations[] = [
                        'MatchPredefinedVariable',
                        [
                            'key' => 'GLOBALS|TYPO3_CONF_VARS|' . $path,
                            'usingRegexp' => true,
                            'match' => $value,
                            'comparisonOperator' => false,
                        ],
                    ];
                }
            }
        }

        if (count($operations) == 0) {
            return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_warning, 0, 'No conditions found');
        }

        $commandResult = $this->executeRemoteOperations($operations);
        if (!$this->isCommandResultSuccessful($commandResult)) {
            return $this->getFailedCommandResultTestResult($commandResult);
        }

        $results = $commandResult->getOperationResults();
        $success = [];
        $failures = [];

        foreach ($results as $key => $operationResult) {
            if ($operationResult->isSuccessful()) {
                if ($operations[$key][1]['usingRegexp'] == true) {
                    $success[] = 'Variable-Path ' . $operations[$key][1]['key'] . ' matched the regular expression ' . $operations[$key][1]['match'];
                } else {
                    $success[] = 'Variable-Path ' . $operations[$key][1]['key'] . ' matched the expectation ' . $operations[$key][1]['comparisonOperator'] . ' "' . $operations[$key][1]['match'] . '"';
                }
            } elseif ($operations[$key][1]['usingRegexp'] == true) {
                $failures[] = 'Variable-Path ' . $operations[$key][1]['key'] . ' did not match the regular expression ' . $operations[$key][1]['match'];
            } else {
                $failures[] = 'Variable-Path ' . $operations[$key][1]['key'] . ' did not match the expectation ' . $operations[$key][1]['comparisonOperator'] . ' "' . $operations[$key][1]['match'] . '"';
            }
        }

        $msg_failures = '';
        $msg_success = '';

        if ($success !== []) {
            $msg_success .= chr(10) . 'Matched Conditions: ' . chr(10) . implode(chr(10), $success);
        }

        $resultMatch = $this->getConfigValue('resultMatch');
        $resultNoMatch = $this->getConfigValue('resultNoMatch');

        if ($resultMatch == null) {
            $resultMatch = tx_caretaker_Constants::state_error;
        }

        if ($resultNoMatch == null) {
            $resultNoMatch = tx_caretaker_Constants::state_ok;
        }

        if ($failures !== []) {
            $msg_failures .= chr(10) . 'Not Matched Conditions: ' . chr(10) . implode(chr(10), $failures);

            return tx_caretaker_TestResult::create((int)$resultNoMatch, 0, $msg_failures . chr(10) . $msg_success);
        }

        return tx_caretaker_TestResult::create((int)$resultMatch, 0, $msg_success);
    }
}
