<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object\Meta;

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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Framework\Object\RepresentableAsString;

class EventReference implements RepresentableAsString
{
    /**
     * @return EventReference
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventReference::class);
    }

    /**
     * @param array $array
     * @return EventReference
     */
    public static function fromArray(array $array)
    {
        return static::instance()
            ->setEventId($array['eventId'])
            ->setEntityReference(EntityReference::fromArray($array['entity']));
    }

    /**
     * @var EntityReference
     */
    protected $entityReference;

    /**
     * @var string
     */
    protected $eventId;

    public function __toString(): string
    {
        return $this->entityReference->__toString() . '@' . $this->eventId;
    }

    public function __toArray(): array
    {
        return [
            'entity' => $this->entityReference->__toArray(),
            'eventId' => $this->eventId,
        ];
    }

    public function getEntityReference(): EntityReference
    {
        return $this->entityReference;
    }

    public function setEntityReference(EntityReference $entityReference): EventReference
    {
        $this->entityReference = $entityReference;
        return $this;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function setEventId(string $eventId): EventReference
    {
        $this->eventId = $eventId;
        return $this;
    }

    public function import(EventReference $reference): EventReference
    {
        if ($this->entityReference === null) {
            $this->entityReference = EntityReference::instance();
        }

        $this->entityReference->import($reference->getEntityReference());
        $this->eventId = $reference->getEventId();

        return $this;
    }
}
