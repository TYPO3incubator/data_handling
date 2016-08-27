<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta;

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

use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;

class OriginEventRepository implements EventRepository
{
    /**
     * @return GenericEntityEventRepository
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(GenericEntityEventRepository::class);
    }

    /**
     * @var string
     */
    protected $aggregateType;

    /**
     * @param UuidInterface $uuid
     * @return void
     * @throws \RuntimeException
     */
    public function findByUuid(UuidInterface $uuid)
    {
        throw new \RuntimeException('This stream does not provide more specific streams');
    }

    /**
     * @param BaseEvent|AbstractEvent $event
     */
    public function addEvent(BaseEvent $event)
    {
        $streamName = Common::STREAM_PREFIX_META_ORIGIN;

        $eventSelector = EventSelector::instance()
            ->setEvents([get_class($event)])
            ->setStreamName($streamName);

        EventStorePool::provide()
            ->getAllFor($eventSelector)
            ->attach($streamName, $event);
    }
}
