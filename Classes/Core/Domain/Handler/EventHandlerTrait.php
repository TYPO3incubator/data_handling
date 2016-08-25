<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Handler;

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
use TYPO3\CMS\DataHandling\Core\Utility\ClassNamingUtility;

trait EventHandlerTrait
{
    /**
     * @var int
     */
    protected $revision;

    /**
     * @var string[]
     */
    protected $appliedEventIds = [];

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @param AbstractEvent $event
     */
    public function apply(AbstractEvent $event)
    {
        // this just checks for applied events in the current processing, the
        // ids of previously applied events might not be available in this scope
        if (in_array($event->getEventId(), $this->appliedEventIds)) {
            throw new \RuntimeException('Event "' . $event->getEventId() . '" was already applied', 1472041262);
        }
        // validate and assign event version to subject
        if ($event->getEventVersion() !== null
            && $this->revision + 1 !== $event->getEventVersion()
            && ($this->revision !== null || $event->getEventVersion() !== 0)
        ) {
            throw new \RuntimeException('Unexpected event in sequence', 1472044588);
        }
        $this->revision = $event->getEventVersion();
        // determine method name, that is used to apply the event
        $methodName = $this->getEventHandlerMethodName($event);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
        }
    }

    /**
     * @param AbstractEvent $event
     * @return string
     */
    protected function getEventHandlerMethodName(AbstractEvent $event)
    {
        $eventName = ClassNamingUtility::getLastPart($event);
        return 'on' . $eventName;
    }
}
