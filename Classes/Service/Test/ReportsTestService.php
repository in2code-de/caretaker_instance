<?php

namespace Caretaker\CaretakerInstance\Service\Test;

use Caretaker\Caretaker\Constants;
use Caretaker\Caretaker\Entity\Result\TestResult;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;

class ReportsTestService extends RemoteTestServiceBase
{
    /**
     * @return TestResult
     */
    public function runTest()
    {
        if (!ExtensionManagementUtility::isLoaded('reports')) {
            return TestResult::create(Constants::state_undefined, 0, 'Missing SYSEXT reports!');
        }
        /** @var \TYPO3\CMS\Reports\Report\Status\Status $statusReport */
        $statusReport = GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Report\Status\Status::class);
        $systemStatus = $statusReport->getSystemStatus();
        $highestSeverity = $statusReport->getHighestSeverity($systemStatus);
        if ($highestSeverity > Status::OK) {
            foreach ($systemStatus as $statusProvider) {
                /** @var Status $status */
                foreach ($statusProvider as $status) {
                    if ($status->getSeverity() > Status::OK) {
                        $systemIssues[] = '<h2>' . (string)$status . '</h2>' .
                            $status->getMessage() . '<hr style="background-color: #CCC;">';
                    }
                }
            }
            if (isset($systemIssues)) {
                return TestResult::create(
                    ($highestSeverity == Status::WARNING) ? (int) $this->getConfigValue('status_of_reports') : $highestSeverity,
                    0,
                    CRLF . str_replace(
                        '[WARN]',
                        '⚠️',
                        str_replace('[ERR]', '❌', implode('', $systemIssues))
                    )
                );
            }
        }

        return TestResult::create(Constants::state_ok, 0, 'OK!');
    }
}
