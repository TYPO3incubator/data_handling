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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\TargetReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\TargetReferenceTrait;

class BranchedEntityToEvent extends AbstractEvent implements TargetReference
{
    use TargetReferenceTrait;

    /**
     * @param Context $context
     * @param EntityReference $aggregateReference
     * @param EntityReference $targetReference
     * @return BranchedEntityToEvent
     */
    public static function create(Context $context, EntityReference $aggregateReference, EntityReference $targetReference)
    {
        $event = new static();
        $event->context = $context;
        $event->aggregateReference = $aggregateReference;
        $event->targetReference = $targetReference;
        return $event;
    }
}
