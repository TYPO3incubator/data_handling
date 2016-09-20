<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Command;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\RelationReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\RelationReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\AbstractSequence;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence;
use TYPO3\CMS\DataHandling\Core\Domain\Object\SequenceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class OrderRelationsCommand extends AbstractCommand implements Instantiable, RelationReference, Sequence
{
    use RelationReferenceTrait;
    use SequenceTrait;

    /**
     * @return OrderRelationsCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param Context $context
     * @param PropertyReference $relationReference
     * @param AbstractSequence $sequence
     * @return OrderRelationsCommand
     */
    public static function create(Context $context, PropertyReference $relationReference, AbstractSequence $sequence)
    {
        $command = static::instance();
        $command->context = $context;
        $command->relationReference = $relationReference;
        $command->sequence = $sequence;
        return $command;
    }
}
