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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Derivable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Workspace;
use TYPO3\CMS\DataHandling\Core\Domain\Object\WorkspaceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class BranchEntityCommand extends AbstractCommand implements Instantiable, Derivable, AggregateReference, Workspace
{
    use AggregateReferenceTrait;
    use WorkspaceTrait;

    /**
     * @return BranchEntityCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(BranchEntityCommand::class);
    }

    /**
     * @param EntityReference $aggregateReference
     * @param int $workspaceId
     * @return BranchEntityCommand
     */
    public static function create(EntityReference $aggregateReference, int $workspaceId)
    {
        $command = static::instance();
        $command->aggregateReference = $aggregateReference;
        $command->workspaceId = $workspaceId;
        return $command;
    }
}
