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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Locale;
use TYPO3\CMS\DataHandling\Core\Domain\Object\LocaleTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Workspace;
use TYPO3\CMS\DataHandling\Core\Domain\Object\WorkspaceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CreatedEntityEvent extends AbstractEvent implements Instantiable, Workspace, Locale
{
    use WorkspaceTrait;
    use LocaleTrait;

    /**
     * @return CreatedEntityEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CreatedEntityEvent::class);
    }

    /**
     * @param EntityReference $aggregateReference
     * @param int $workspaceId
     * @param string $locale
     * @return CreatedEntityEvent
     */
    public static function create(EntityReference $aggregateReference, int $workspaceId, string $locale)
    {
        $event = static::instance();
        $event->aggregateReference = $aggregateReference;
        $event->workspaceId = $workspaceId;
        $event->locale = $locale;
        return $event;
    }
}
