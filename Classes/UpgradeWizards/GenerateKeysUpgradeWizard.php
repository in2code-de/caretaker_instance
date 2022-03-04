<?php

namespace Caretaker\CaretakerInstance\UpgradeWizards;

use Exception;
use tx_caretakerinstance_ServiceFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class GenerateKeysUpgradeWizard implements UpgradeWizardInterface
{
    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     */
    public function getIdentifier(): string
    {
        return 'caretakerInstanceGenerateKeys';
    }

    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'Generate public / private key pairs for caretaker_instance.';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'Generate public / private key pairs for caretaker_instance.';
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     */
    public function executeUpdate(): bool
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $extConf = $extensionConfiguration->get('caretaker_instance');

        $factory = tx_caretakerinstance_ServiceFactory::getInstance();
        try {
            [$publicKey, $privateKey] = $factory->getCryptoManager()->generateKeyPair();
            $extConf['crypto']['instance']['publicKey'] = $publicKey;
            $extConf['crypto']['instance']['privateKey'] = $privateKey;
            $extensionConfiguration->set('caretaker_instance', $extConf);
        } catch (Exception $exception) {
            return false;
        }
        return true;
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     */
    public function updateNecessary(): bool
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('caretaker_instance');
        return true;
        return !isset($extConf['crypto']['instance']['publicKey']) && !isset($extConf['crypto']['instance']['privateKey']);
    }

    /**
     * Returns an array of class names of prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     */
    public function getPrerequisites(): array
    {
        return [];
    }
}