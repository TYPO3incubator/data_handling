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
     * @param AbstractEvent $event
     */
    public function apply(AbstractEvent $event)
    {
        $methodName = $this->getEventHandlerMethodName($event);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
            $this->revision = ($this->revision ?? 0) + 1;
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
