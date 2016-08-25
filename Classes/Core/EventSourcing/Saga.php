<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\GenericStream;

class Saga
{
    /**
     * @return Saga
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Saga::class);
    }

    /**
     * @param EventApplicable $state
     * @param string|EventSelector $concerning
     * @return EventApplicable
     */
    public function tell(EventApplicable $state, $concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        if (empty($concerning->getStreamName())) {
            throw new \RuntimeException('No stream name defined', 1472124767);
        }

        $stream = EventStorePool::provide()
            ->getBestFor($concerning)
            ->stream($concerning->getStreamName());

        foreach ($stream as $event) {
            $state->apply($event);
        }

        return $state;
    }
}
