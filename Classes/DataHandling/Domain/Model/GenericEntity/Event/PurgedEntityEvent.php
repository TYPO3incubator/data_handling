<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Instantiable;

class PurgedEntityEvent extends AbstractEvent implements Instantiable
{
    /**
     * @return PurgedEntityEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(PurgedEntityEvent::class);
    }

    /**
     * @param Context $context
     * @param EntityReference $aggregateReference
     * @return PurgedEntityEvent
     */
    public static function create(Context $context, EntityReference $aggregateReference)
    {
        $event = static::instance();
        $event->context = $context;
        $event->aggregateReference = $aggregateReference;
        return $event;
    }
}
