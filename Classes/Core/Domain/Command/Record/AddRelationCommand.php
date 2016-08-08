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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class AddRelationCommand extends AbstractCommand
{
    /**
     * @param string $tableName
     * @param string $subject
     * @param array $data
     * @return AddRelationCommand
     */
    public static function instance(string $tableName, string $subject, array $data)
    {
        $command = GeneralUtility::makeInstance(AddRelationCommand::class);
        $command->setTableName($tableName);
        $command->setSubject($subject);
        $command->setData($data);
        return $command;
    }
}
