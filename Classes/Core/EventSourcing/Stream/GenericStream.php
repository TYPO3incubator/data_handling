<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Stream;

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
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class GenericStream extends AbstractStream implements Instantiable
{
    /**
     * @var string
     */
    protected $prefix = 'generic';

    /**
     * @return GenericStream
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(GenericStream::class);
    }

    /**
     * @param BaseEvent|Meta\AbstractEvent $event
     * @return string
     */
    protected function determineStreamNameByEvent(BaseEvent $event): string
    {
        $name = '';

        // event has assigned subject
        // (bind to whole subject identity the event is emmited for)
        if ($event->getSubject() !== null) {
            $name = (string)$event->getSubject();
        // event is identifiable, but does not have a subject
        // (most probably used for CreatedEntityEvent and others providing a new identity)
        } elseif ($event instanceof AggregateReference) {
            $name = (string)$event->getAggregateReference();
        }

        return $this->prefix($name);
    }
}
