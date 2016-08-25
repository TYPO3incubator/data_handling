<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Domain\Event;

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
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Type\MicroDateTime;

abstract class BaseEvent implements DomainEvent
{
    /**
     * @param string $eventType
     * @param string $eventId
     * @param int $eventVersion
     * @param \DateTime $date
     * @param null|array $data
     * @param null|array $metadata
     * @return BaseEvent
     */
    static function reconstitute(string $eventType, string $eventId, int $eventVersion, \DateTime $date, $data, $metadata)
    {
        if (!in_array(Instantiable::class, class_implements($eventType))) {
            throw new \RuntimeException('Cannot instantiate "' . $eventType . '"', 1470935798);
        }

        /** @var BaseEvent $event */
        $event = call_user_func($eventType . '::instance');
        $event->eventId = $eventId;
        $event->eventVersion = $eventVersion;
        $event->date = $date;
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
    protected $date;

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
        $this->date = MicroDateTime::create('now');
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * @return $this
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
     * @return $this;
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
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param array|null $data
     * @return BaseEvent
     */
    public function setData(array $data = null)
    {
        $this->data = $data;
        return $this;
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
     * @param null|array $data
     * @return BaseEvent
     */
    abstract public function importData($data);

    /**
     * @param array|null $metadata
     * @return BaseEvent
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
}
