<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;

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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\AbstractSequence;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\SequenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Instantiable;

class OrderRelationsCommand extends AbstractCommand implements Instantiable, AggregateReference, Sequence
{
    use AggregateReferenceTrait;
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
     * @param EntityReference $aggregateReference
     * @param AbstractSequence $sequence
     * @return OrderRelationsCommand
     */
    public static function create(Context $context, EntityReference $aggregateReference, AbstractSequence $sequence)
    {
        $command = static::instance();
        $command->context = $context;
        $command->aggregateReference = $aggregateReference;
        $command->sequence = $sequence;
        return $command;
    }
}
