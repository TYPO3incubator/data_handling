<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Command\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Relational;
use TYPO3\CMS\DataHandling\Core\Domain\Object\RelationalTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class RemoveRelationCommand extends AbstractCommand implements Instantiable, Relational
{
    use RelationalTrait;

    /**
     * @return RemoveRelationCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RemoveRelationCommand::class);
    }

    /**
     * @param EntityReference $subject
     * @param PropertyReference $relation
     * @return RemoveRelationCommand
     */
    public static function create(EntityReference $subject, PropertyReference $relation)
    {
        $command = static::instance();
        $command->setSubject($subject);
        $command->setRelation($relation);
        return $command;
    }
}
