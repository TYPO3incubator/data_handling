<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event\Generic;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Derivable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Identifiable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\IdentifiableTrait;
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;

class BranchedEvent extends AbstractEvent implements Instantiable, Identifiable, Derivable
{
    use IdentifiableTrait;

    /**
     * @return BranchedEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(BranchedEvent::class);
    }

    /**
     * @param EntityReference $subject
     * @param EntityReference $identity
     * @return BranchedEvent
     */
    public static function create(EntityReference $subject, EntityReference $identity)
    {
        $event = static::instance();
        $event->setSubject($subject);
        $event->setIdentity($identity);
        return $event;
    }
}
