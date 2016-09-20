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
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Bundle;
use TYPO3\CMS\DataHandling\Core\Domain\Object\BundleTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CreateEntityBundleCommand extends AbstractCommand implements Instantiable, Bundle, AggregateReference, NodeReference
{
    use BundleTrait;
    use AggregateReferenceTrait;
    use NodeReferenceTrait;

    /**
     * @return CreateEntityBundleCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param Context $context
     * @param EntityReference $aggregateReference
     * @param EntityReference $nodeReference
     * @param AbstractCommand[] $commands
     * @return CreateEntityBundleCommand
     */
    public static function create(Context $context, EntityReference $aggregateReference, EntityReference $nodeReference, array $commands)
    {
        $command = static::instance();
        $command->context = $context;
        $command->aggregateReference = $aggregateReference;
        $command->nodeReference = $nodeReference;
        $command->commands = $commands;
        return $command;
    }
}
