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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\AbstractSequence;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequenceable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\SequenceableTrait;

class OrderRelationsCommand extends AbstractCommand implements Sequenceable
{
    use SequenceableTrait;

    /**
     * @param EntityReference $subject
     * @param AbstractSequence $sequence
     * @return OrderRelationsCommand
     */
    public static function instance(EntityReference $subject, AbstractSequence $sequence)
    {
        $command = GeneralUtility::makeInstance(OrderRelationsCommand::class);
        $command->setSubject($subject);
        $command->setSequence($sequence);
        return $command;
    }
}
