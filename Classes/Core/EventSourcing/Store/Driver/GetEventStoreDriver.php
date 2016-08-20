<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;

class GetEventStoreDriver implements DriverInterface
{
    const FORMAT_DATETIME = 'Y-m-d H:i:s.u';

    /**
     * @param string $url
     * @param string $username
     * @param string $password
     * @return GetEventStoreDriver
     */
    public static function create(string $url, string $username = null, string $password = null)
    {
        return GeneralUtility::makeInstance(GetEventStoreDriver::class, $url, $username, $password);
    }

    /**
     * @var \EventStore\EventStore
     */
    protected $eventStore;

    /**
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct(string $url, string $username = null, string $password = null)
    {
        $eventStoreClient = null;

        if ($username !== null && $password !== null) {
            $guzzleClient = new \GuzzleHttp\Client([
                'auth' => [$username, $password],
                'handler' => new \GuzzleHttp\Handler\CurlMultiHandler(),
            ]);
            $eventStoreClient = new \EventStore\Http\GuzzleHttpClient($guzzleClient);
        }

        $this->eventStore = new \EventStore\EventStore($url, $eventStoreClient);
    }

    /**
     * @param string $streamName
     * @param AbstractEvent $event
     * @param string[] $categories
     * @return bool
     * @todo The GetEventStoreAPI does not support categories, yet
     */
    public function append(string $streamName, AbstractEvent $event, array $categories = []): bool
    {
        $UUID = GetEventStoreUUID::fromNative(
            $event->getEventId()
        );

        $writableEvent = new \EventStore\WritableEvent(
            $UUID,
            get_class($event),
            ($event->exportData() ?? []),
            ($event->getMetadata() ?? [])
        );

        try {
            $this->eventStore->writeToStream(rawurlencode($streamName), $writableEvent);
        } catch (\EventStore\Exception\WrongExpectedVersionException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param string $streamName
     * @param array $categories
     * @return SqlDriverIterator
     * @todo The GetEventStoreAPI does not support categories, yet
     */
    public function open(string $streamName, array $categories = [])
    {
        if (empty($streamName) /* && empty($categories) */) {
            throw new \RuntimeException('No selection criteria given', 1471441756);
        }

        $comparableStreamName = EventSelector::getComparablePart($streamName);

        if ($comparableStreamName === $streamName) {
            $comparableStreamName = rawurlencode($comparableStreamName);
        } else {
            $comparableStreamName = rawurlencode('$all');
        }

        try {
            $iterator = GetEventStoreIterator::create(
                $this->eventStore->forwardStreamFeedIterator($comparableStreamName)
            );
        } catch (\Exception $exception) {
            $iterator = new \ArrayObject();
        }

        return $iterator;
    }
}
