<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Command\Generic;

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

class RemoveRelationCommand extends AbstractCommand implements Relational
{
    use RelationalTrait;

    /**
     * @param EntityReference $subject
     * @param PropertyReference $relation
     * @return RemoveRelationCommand
     */
    public static function instance(EntityReference $subject, PropertyReference $relation)
    {
        $command = GeneralUtility::makeInstance(RemoveRelationCommand::class);
        $command->setSubject($subject);
        $command->setRelation($relation);
        return $command;
    }
}
