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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Relational;
use TYPO3\CMS\DataHandling\Core\Domain\Object\RelationalTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class RemovedRelationEvent extends AbstractEvent implements Instantiable, Relational
{
    use RelationalTrait;

    /**
     * @return RemovedRelationEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RemovedRelationEvent::class);
    }

    /**
     * @param EntityReference $subject
     * @param PropertyReference $relation
     * @return RemovedRelationEvent
     */
    public static function create(EntityReference $subject, PropertyReference $relation)
    {
        $event = static::instance();
        $event->setSubject($subject);
        $event->setRelation($relation);
        return $event;
    }
}
