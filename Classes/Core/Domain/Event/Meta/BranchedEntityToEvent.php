<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\TargetReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\TargetReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Workspace;
use TYPO3\CMS\DataHandling\Core\Domain\Object\WorkspaceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class BranchedEntityToEvent extends AbstractEvent implements Instantiable, TargetReference, Workspace, Derivable
{
    use TargetReferenceTrait;
    use WorkspaceTrait;

    /**
     * @return BranchedEntityToEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(BranchedEntityToEvent::class);
    }

    /**
     * @param EntityReference $aggregateReference
     * @param EntityReference $targetReference
     * @param int $workspaceId
     * @return BranchedEntityToEvent
     */
    public static function create(EntityReference $aggregateReference, EntityReference $targetReference, int $workspaceId = null)
    {
        $event = static::instance();
        $event->aggregateReference = $aggregateReference;
        $event->targetReference = $targetReference;
        $event->workspaceId = $workspaceId;
        return $event;
    }
}
