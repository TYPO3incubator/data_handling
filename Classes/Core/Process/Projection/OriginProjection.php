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
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\EventProjecting;

class OriginProjection extends AbstractGenericEntityProjection implements EventProjecting
{
    /**
     * @return OriginProjection
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(OriginProjection::class);
    }

    /**
     * @param BaseEvent|AbstractEvent $event
     */
    public function project(BaseEvent $event) {
        $subject = $this->provideSubject($event);
        $subject->apply($event);
        $this->persist($subject);
    }

    protected function persist(GenericEntity $subject)
    {
        var_dump($subject);
    }
}
