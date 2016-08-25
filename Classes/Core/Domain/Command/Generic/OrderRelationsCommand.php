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
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class OrderRelationsCommand extends AbstractCommand implements Instantiable, Sequenceable
{
    use SequenceableTrait;

    /**
     * @return OrderRelationsCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(OrderRelationsCommand::class);
    }

    /**
     * @param EntityReference $subject
     * @param AbstractSequence $sequence
     * @return OrderRelationsCommand
     */
    public static function create(EntityReference $subject, AbstractSequence $sequence)
    {
        $command = static::instance();
        $command->setSubject($subject);
        $command->setSequence($sequence);
        return $command;
    }
}
