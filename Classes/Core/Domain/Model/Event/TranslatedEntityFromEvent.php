<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Event;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\FromReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\FromReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Locale;
use TYPO3\CMS\DataHandling\Core\Domain\Object\LocaleTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EventReference;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\EntityEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class TranslatedEntityFromEvent extends AbstractEvent implements EntityEvent, Instantiable, FromReference, Locale, Derivable
{
    use FromReferenceTrait;
    use LocaleTrait;

    /**
     * @return TranslatedEntityFromEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(TranslatedEntityFromEvent::class);
    }

    /**
     * @param EntityReference $aggregateReference
     * @param EventReference $fromReference
     * @param string $locale
     * @return TranslatedEntityFromEvent
     */
    public static function create(EntityReference $aggregateReference, EventReference $fromReference, string $locale)
    {
        $event = static::instance();
        $event->aggregateReference = $aggregateReference;
        $event->fromReference = $fromReference;
        $event->locale = $locale;
        return $event;
    }
}
