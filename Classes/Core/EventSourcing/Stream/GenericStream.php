<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Stream;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Identifiable;
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;

class GenericStream extends AbstractStream implements Instantiable
{
    /**
     * @var GenericStream[]
     */
    static protected $streams = [];

    /**
     * @return GenericStream
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(GenericStream::class);
    }

    /**
     * @param string $name
     * @return GenericStream
     */
    public function setName(string $name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $streamName
     * @return string
     */
    public function prefix(string $streamName): string
    {
        return $this->name . '-' . $streamName;
    }

    /**
     * @param AbstractEvent|Generic\AbstractEvent $event
     * @return GenericStream
     */
    public function publish(AbstractEvent $event)
    {
        foreach ($this->consumers as $consumer) {
            call_user_func($consumer, $event);
        }
        return $this;
    }

    /**
     * @param callable $consumer
     * @return GenericStream
     */
    public function subscribe(callable $consumer)
    {
        if (!in_array($consumer, $this->consumers, true)) {
            $this->consumers[] = $consumer;
        }
        return $this;
    }

    /**
     * @param AbstractEvent|Generic\AbstractEvent $event
     * @return string
     */
    public function determineNameByEvent(AbstractEvent $event): string
    {
        $name = $this->prefix('$any');

        // event has assigned subject
        // (bind to whole subject identity the event is emmited for)
        if ($event->getSubject() !== null) {
            $name = $this->determineNameByReference($event->getSubject());
        // event is identifiable, but does not have a subject
        // (most probably used for CreatedEvent and others providing a new identity)
        } elseif ($event instanceof Identifiable) {
            $name = $this->determineNameByReference($event->getIdentity());
        }

        return $name;
    }

    /**
     * @param EntityReference $reference
     * @return string
     */
    public function determineNameByReference(EntityReference $reference): string
    {
        return $this->prefix($reference->__toString());
    }
}
