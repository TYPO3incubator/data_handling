<?php
namespace TYPO3\CMS\DataHandling\Core\Service;

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

use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\EventSelector;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\EventStorePool;

class ProjectionService
{
    /**
     * @return ProjectionService
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param EventSelector $concerning
     */
    public function project(EventSelector $concerning)
    {
        $stream = EventStorePool::provide()
            ->getBestFor($concerning)
            ->stream(
                $concerning->getStreamName(),
                $concerning->getCategories()
            );

        /*
            ProjectionPool::provide()
                ->getFor($concerning)
                ->provide()->forStream()
                ->project($stream);
         */
    }
}
