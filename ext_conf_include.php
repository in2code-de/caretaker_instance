<?php

use Caretaker\Caretaker\Helper\ServiceHelper;

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
defined('TYPO3') or die();

// Register Caretaker Services
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('caretaker')) {
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_Extension', 'TYPO3 -> Extension', 'Check for a specific Extension');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_TYPO3Version', 'TYPO3 -> Version', 'Check for the TYPO3 version');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_FindInsecureExtension', 'TYPO3 -> Find insecure Extensions', 'Find Extensions wich are marked insecure in TER');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_FindExtensionUpdates', 'TYPO3 -> Find Extension Updates', 'Find available Updates for installed Extensions');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_BackendUser', 'TYPO3 -> Check backend user accounts', 'Find unwanted backend user accounts');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_CheckConfVars', 'TYPO3 -> Check TYPO3_CONF_VARS', 'Check Settings of TYPO3_CONF_VARS');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_CheckPath', 'FILE -> Check path', 'Checks for some path stats');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_DiskSpace', 'Disk Space', 'Check for disk space');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_Reports', 'TYPO3 -> Reports', 'Check for TYPO3 Reports');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_BlacklistedRecords', 'TYPO3 -> Check blacklisted records', 'Find unwanted records');
    ServiceHelper::registerCaretakerTestService('caretaker_instance', 'services', 'tx_caretakerinstance_SchedulerFailures', 'TYPO3 -> Check scheduler failures', 'Scan for recent failures of scheduler tasks');
}
