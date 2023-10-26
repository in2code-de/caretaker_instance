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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
 * An Operation that returns the version of an installed extension
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 */
class tx_caretakerinstance_Operation_GetExtensionVersion implements tx_caretakerinstance_IOperation, SingletonInterface
{
    /**
     * Get the extension version of the given extension by extension key
     *
     * @param array $parameter None
     * @return tx_caretakerinstance_OperationResult The extension version
     */
    public function execute($parameter = []): \tx_caretakerinstance_OperationResult
    {
        $EM_CONF = [];
        $extensionKey = $parameter['extensionKey'];

        if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
            return new tx_caretakerinstance_OperationResult(false, 'Extension [' . $extensionKey . '] is not loaded');
        }

        $_EXTKEY = $extensionKey;
        @include(ExtensionManagementUtility::extPath($extensionKey, 'ext_emconf.php'));

        if (is_array($EM_CONF[$extensionKey])) {
            return new tx_caretakerinstance_OperationResult(true, $EM_CONF[$extensionKey]['version']);
        }

        return new tx_caretakerinstance_OperationResult(false, 'Cannot read EM_CONF for extension [' . $extensionKey . ']');
    }
}
