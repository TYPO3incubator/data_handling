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
    protected $offline;

    /**
     * @param string $url
     * @param string $username
     * @param string $password
     * @param bool $mute
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
        } catch (\Exception $exception) {
            if (!$mute) {
                throw $exception;
            }
            $this->offline = true;
        }
    }

    /**
     * @param string $streamName
     * @param AbstractEvent $event
     * @param string[] $categories
     * @return bool
     * @todo The GetEventStoreAPI does not support categories, yet
     */
    public function attach(string $streamName, AbstractEvent $event, array $categories = []): bool
    {
        if ($this->offline) {
            return false;
        }

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
    public function stream(string $streamName, array $categories = [])
    {
        if (empty($streamName) /* && empty($categories) */) {
            throw new \RuntimeException('No selection criteria given', 1471441756);
        }

        if ($this->offline) {
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

        return $iterator;
    }
}
