<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Saga;

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

use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Applicable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\AbstractStream;

/**
 * @deprecated
 */
abstract class AbstractSaga
{
    /**
     * @var AbstractStream
     */
    protected $stream;

    /**
     * @param $stream
     * @return AbstractStream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * @param Applicable $state
     * @param EventSelector $desire
     */
    public function tell(Applicable $state, EventSelector $desire)
    {
        $applicableState = function(AbstractEvent $event) use ($state) {
            $state->apply($event);
        };

        $this->getStream()
            ->subscribe($applicableState)
            ->replay($desire);
    }

    /**
     * @return AbstractStream
     */
    protected function getStream()
    {
        return $this->stream;
    }
}
