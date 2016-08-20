<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event;

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
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Type\MicroDateTime;

abstract class AbstractEvent
{
    /**
     * @param string $eventType
     * @param string $eventId
     * @param \DateTime $date
     * @param null|array $data
     * @param null|array $metadata
     * @return AbstractEvent
     */
    static function reconstitute(string $eventType, string $eventId, \DateTime $date, $data, $metadata)
    {
        if (!in_array(Instantiable::class, class_implements($eventType))) {
            throw new \RuntimeException('Cannot instantiate "' . $eventType . '"', 1470935798);
        }

        /** @var AbstractEvent $event */
        $event = call_user_func($eventType . '::instance');
        $event->eventId = $eventId;
        $event->date = $date;
        $event->metadata = $metadata;
        $event->importData($data);
        return $event;
    }

    /**
     * @var string
     */
    protected $eventId;

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
     * @return string
     */
    public function getEventId(): string
    {
        return $this->eventId;
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
     * @return AbstractEvent
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
     * @return AbstractEvent
     */
    abstract public function importData($data);

    /**
     * @param array|null $metadata
     * @return AbstractEvent
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
