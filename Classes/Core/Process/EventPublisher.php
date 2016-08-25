<?php
namespace TYPO3\CMS\DataHandling\Core\Process;

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
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class EventPublisher implements Instantiable
{
    const STRATEGY_IMMEDIATE = 'immediate';
    const STRATEGY_QUEUED = 'queued';

    /**
     * @return EventPublisher
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(EventPublisher::class);
    }

    /**
     * @var string
     */
    protected $strategy = self::STRATEGY_IMMEDIATE;

    /**
     * @param BaseEvent $event
     */
    public function publish(BaseEvent $event)
    {
        if ($this->strategy === static::STRATEGY_IMMEDIATE) {
            EventManager::instance()->manage($event);
        }

        if ($this->strategy === static::STRATEGY_QUEUED) {
            throw new \RuntimeException('Queued strategy is not implemented');
        }
    }
}
