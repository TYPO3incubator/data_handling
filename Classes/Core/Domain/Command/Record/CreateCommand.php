<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Command\Record;

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

use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Identifiable;

class CreateCommand extends AbstractCommand implements Identifiable
{
    /**
     * @param string $tableName
     * @return CreateCommand
     */
    public static function instance(string $tableName)
    {
        $command = GeneralUtility::makeInstance(CreateCommand::class);
        $command->setIdentifier(Uuid::uuid4()->toString());
        $command->setTableName($tableName);
        return $command;
    }
}
