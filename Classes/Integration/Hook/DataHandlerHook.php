<?php
namespace TYPO3\CMS\DataHandling\Integration\Hook;

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
 * Fills generated uuid columns in schema
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
        if (empty($dataHandler->datamap) && empty($dataHandler->cmdmap)) {
            return;
        }
        // create command mapper for incoming data
        $commandMapper = DataHandlerTranslator::create(
            $dataHandler->datamap,
            $dataHandler->cmdmap
        );
        // processes incoming data and emits commands
        $commandMapper->process();
        // reset DataHandler maps
        $dataHandler->datamap = $commandMapper->getDataCollection();
        $dataHandler->cmdmap = $commandMapper->getActionCollection();
    }
}
