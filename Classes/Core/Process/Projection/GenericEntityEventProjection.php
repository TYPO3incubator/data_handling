<?php
namespace TYPO3\CMS\DataHandling\Core\Process\Projection;

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
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\EventProjecting;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\Projecting;

class GenericEntityEventProjection extends AbstractGenericEntityProjection implements Projecting, EventProjecting
{
    /**
     * @return GenericEntityEventProjection
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(GenericEntityEventProjection::class);
    }

    /**
     * @param BaseEvent $event
     */
    public function project(BaseEvent $event) {
        $this->handleListeners($event);

        if ($event->isCancelled()) {
            return;
        }

        OriginProjection::instance()->project($event);
    }
}
