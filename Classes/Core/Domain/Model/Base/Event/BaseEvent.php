<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event;

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

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Instantiable;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\MicroDateTime;

abstract class BaseEvent implements DomainEvent
{
    /**
     * @param string $eventType
     * @param string $eventId
     * @param int $eventVersion
     * @param \DateTime $date
     * @param UuidInterface $aggregateId
     * @param null|array $data
     * @param null|array $metadata
     * @return BaseEvent
     */
    public static function reconstitute(
        string $eventType,
        string $eventId,
        int $eventVersion,
        \DateTime $date,
        UuidInterface $aggregateId = null,
        $data,
        $metadata
    )
    {
        if (!in_array(Instantiable::class, class_implements($eventType))) {
            throw new \RuntimeException('Cannot instantiate "' . $eventType . '"', 1470935798);
        }

        /** @var BaseEvent $event */
        $event = call_user_func($eventType . '::instance');
        $event->eventId = $eventId;
        $event->eventVersion = $eventVersion;
        $event->eventDate = $date;
        $event->aggregateId = $aggregateId;
        $event->metadata = $metadata;
        $event->importData($data);
        return $event;
    }

    /**
     * @var bool
     */
    protected $cancelled = false;

    /**
     * @var string
     */
    protected $eventId;

    /**
     * @var int
     */
    protected $eventVersion;

    /**
     * @var \DateTime
     */
    protected $eventDate;

    /**
     * @var UuidInterface
     */
    protected $aggregateId;

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var array|null
     */
    protected $metadata;

    public function __construct()
    {
        $this->eventId = Uuid::uuid4()->toString();
        $this->eventDate = MicroDateTime::create('now');
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * @return static
     */
    public function cancel()
    {
        $this->cancelled = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * @return int
     */
    public function getEventVersion()
    {
        return $this->eventVersion;
    }

    /**
     * @param int $eventVersion
     * @return static
     */
    public function setEventVersion(int $eventVersion)
    {
        if ($this->eventVersion !== null && $this->eventVersion !== $eventVersion) {
            throw new \RuntimeException('Modifying existing version is denied', 1472045059);
        }
        $this->eventVersion = $eventVersion;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEventDate(): \DateTime
    {
        return $this->eventDate;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    abstract public function exportData();

    /**
     * @param array $data
     */
    abstract public function importData($data);

    /**
     * @param array|null $metadata
     * @return static
     */
    public function setMetadata(array $metadata = null)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return UuidInterface
     */
    public function getAggregateId()
    {
        return $this->aggregateId;
    }
}
