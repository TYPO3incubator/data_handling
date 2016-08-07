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

class BranchCommand extends AbstractCommand implements Identifiable
{
    /**
     * @param string $tableName
     * @param string $subject
     * @param mixed $context
     * @return BranchCommand
     */
    public static function instance(string $tableName, string $subject, $context = null)
    {
        $command = GeneralUtility::makeInstance(BranchCommand::class);
        $command->setSubject($subject);
        $command->setIdentifier(Uuid::uuid4()->toString());
        $command->setTableName($tableName);
        return $command;
    }
}
