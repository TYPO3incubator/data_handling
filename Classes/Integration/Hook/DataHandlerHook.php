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
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\CommandMapper;

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

        CommandMapper::instance()
            ->setDataCollection($dataHandler->datamap)
            ->setActionCollection($dataHandler->cmdmap)
            ->mapCommands()
            ->emitCommands();

        $dataHandler->datamap = [];
        $dataHandler->cmdmap = [];
    }
}
