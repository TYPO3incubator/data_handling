<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Driver;

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

use EventStore\ValueObjects\Identity\UUID as GetEventStoreUUID;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\EventSelector;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\EventStream;

class GetEventStoreDriver implements PersistableDriver
{
    const FORMAT_DATETIME = 'Y-m-d H:i:s.u';

    /**
     * @param string $url
     * @param string $username
     * @param string $password
     * @param bool $mute
     * @return GetEventStoreDriver
     */
    public static function create(string $url, string $username = null, string $password = null, bool $mute = false)
    {
        return GeneralUtility::makeInstance(GetEventStoreDriver::class, $url, $username, $password, $mute);
    }

    /**
     * @var \EventStore\EventStore
     */
    protected $eventStore;

    /**
     * @var bool
     */
    protected $available = false;

    /**
     * @param string $url
     * @param string|null $username
     * @param string|null $password
     * @param bool $mute
     * @throws \Exception
     */
    public function __construct(string $url, string $username = null, string $password = null, bool $mute = false)
    {
        $eventStoreClient = null;

        if ($username !== null && $password !== null) {
            $guzzleClient = new \GuzzleHttp\Client([
                'auth' => [$username, $password],
                'handler' => new \GuzzleHttp\Handler\CurlMultiHandler(),
            ]);
            $eventStoreClient = new \EventStore\Http\GuzzleHttpClient($guzzleClient);
        }

        try {
            $this->eventStore = new \EventStore\EventStore($url, $eventStoreClient);
            $this->available = true;
        } catch (\Exception $exception) {
            if (!$mute) {
                throw $exception;
            }
        }
    }

    /**
     * @param string $streamName
     * @param BaseEvent $event
     * @param string[] $categories
     * @return null|int
     * @todo The GetEventStoreAPI does not support categories, yet
     */
    public function attach(string $streamName, BaseEvent $event, array $categories = [])
    {
        if (!$this->available) {
            return false;
        }

        $UUID = GetEventStoreUUID::fromNative(
            $event->getEventId()
        );

        $data = ($event->exportData() ?? []);
        $metadata = ($event->getMetadata() ?? []);

        if ($event->getAggregateId() !== null) {
            $metadata['$aggregateId'] = $event->getAggregateId()->toString();
        }

        $writableEvent = new \EventStore\WritableEvent(
            $UUID,
            get_class($event),
            $data,
            $metadata
        );

        try {
            return $this->eventStore->writeToStream(rawurlencode($streamName), $writableEvent);
        } catch (\EventStore\Exception\WrongExpectedVersionException $exception) {
            return null;
        }
    }

    /**
     * @param string $streamName
     * @param array $categories
     * @return EventStream
     * @todo The GetEventStoreAPI does not support categories, yet
     */
    public function stream(string $streamName, array $categories = [])
    {
        if (empty($streamName) /* && empty($categories) */) {
            throw new \RuntimeException('No selection criteria given', 1471441756);
        }

        if (!$this->available) {
            return new \ArrayObject();
        }

        $comparableStreamName = EventSelector::getComparablePart($streamName);

        if ($comparableStreamName === $streamName) {
            $comparableStreamName = rawurlencode($comparableStreamName);
        } else {
            $comparableStreamName = rawurlencode('$all');
        }

        $iterator = GetEventStoreIterator::create(
            $this->eventStore->forwardStreamFeedIterator($comparableStreamName)
        );

        return EventStream::create($iterator, $streamName);
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }
}
