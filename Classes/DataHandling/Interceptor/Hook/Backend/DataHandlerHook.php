<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Interceptor\Hook\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\DataHandlerTranslator;

/**
 * Intercepts calls to DataHandler and translates to proper domain commands.
 */
class DataHandlerHook
{
    public function processDatamap_beforeStart(DataHandler $dataHandler)
    {
        $this->process($dataHandler);
    }

    public function processCmdmap_beforeStart(DataHandler $dataHandler)
    {
        $this->process($dataHandler);
    }

    protected function process(DataHandler $dataHandler)
    {
        // skip, in case there's actually nothing to do
        if (empty($dataHandler->datamap) && empty($dataHandler->cmdmap)) {
            return;
        }
        // skip, in case this is an import process
        // @todo `ext:impexp` still should be handled at some point (later™)
        if ($dataHandler->isImporting) {
            return;
        }
        // create command mapper for incoming data
        $commandTranslator = DataHandlerTranslator::create(
            $dataHandler->datamap,
            $dataHandler->cmdmap
        );
        // processes incoming data and emits commands
        $commandTranslator->process();
        // reset DataHandler maps
        $dataHandler->datamap = $commandTranslator->getDataCollection();
        $dataHandler->cmdmap = $commandTranslator->getActionCollection();
        // apply new subjects
        foreach ($commandTranslator->getNewSubjects() as $placeholder => $newSubject) {
            $dataHandler->substNEWwithIDs[$placeholder] = $newSubject->getUid();
            $dataHandler->substNEWwithIDs_table[$placeholder] = $newSubject->getName();
        }
    }
}
