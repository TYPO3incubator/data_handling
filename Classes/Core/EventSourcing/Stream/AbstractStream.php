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

abstract class AbstractStream
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
     * @param AbstractEvent $event
     * @return AbstractStream
     */
    abstract public function publish(AbstractEvent $event);

    /**
     * @param callable $handler
     * @return AbstractStream
     */
    abstract public function subscribe(callable $handler);
}
