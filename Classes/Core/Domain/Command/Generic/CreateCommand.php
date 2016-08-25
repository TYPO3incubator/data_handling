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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Identifiable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\IdentifiableTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CreateCommand extends AbstractCommand implements Instantiable, Identifiable
{
    use IdentifiableTrait;

    /**
     * @return CreateCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CreateCommand::class);
    }

    /**
     * @param EntityReference $identity
     * @param mixed $context
     * @return CreateCommand
     */
    public static function create(EntityReference $identity, $context = null)
    {
        $command = static::instance();
        $command->setIdentity(EntityReference::create($identity->getName()));
        return $command;
    }
}
