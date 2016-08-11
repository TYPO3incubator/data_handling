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
use TYPO3\CMS\DataHandling\Core\Domain\Event\Record;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Storable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStore;
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;

class RecordStream extends AbstractStream implements Instantiable
{
    /**
     * @var RecordStream[]
     */
    static protected $streams = [];

    /**
     * @return RecordStream
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(RecordStream::class);
    }

    /**
     * @param string $name
     * @return RecordStream
     */
    public function setName(string $name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @param AbstractEvent|Record\AbstractEvent $event
     * @return RecordStream
     */
    public function publish(AbstractEvent $event)
    {
        if ($event instanceof Storable) {
            EventStore::instance()->append($this->name, $event);
        }
        foreach ($this->consumers as $consumer) {
            call_user_func($consumer, $event);
        }
        return $this;
    }

    /**
     * @param callable $consumer
     * @return RecordStream
     */
    public function subscribe(callable $consumer)
    {
        if (!is_callable($consumer)) {
            throw new \RuntimeException('Consumer is not callable', 1470853146);
        }
        if (!in_array($consumer, $this->consumers, true)) {
            $this->consumers[] = $consumer;
        }
        return $this;
    }
}
