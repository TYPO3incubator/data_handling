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

use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Publishable;

abstract class AbstractStream implements Publishable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var callable[]
     */
    protected $consumers = [];

    /**
     * @param string $name
     * @return AbstractStream
     */
    abstract public function setName(string $name);

    /**
     * @param string $streamName
     * @return string
     */
    abstract public function prefix(string $streamName): string;

    /**
     * @param AbstractEvent $event
     * @return AbstractStream
     */
    abstract public function publish(AbstractEvent $event);

    /**
     * @param callable $consumer
     * @return AbstractStream
     */
    abstract public function subscribe(callable $consumer);

    /**
     * @param AbstractEvent $event
     * @return string
     */
    abstract public function determineNameByEvent(AbstractEvent $event): string;

    /**
     * @param EntityReference $reference
     * @return string
     */
    abstract public function determineNameByReference(EntityReference $reference): string;
}
