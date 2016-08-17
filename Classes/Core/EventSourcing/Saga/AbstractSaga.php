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

use TYPO3\CMS\DataHandling\Core\EventSourcing\Applicable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStore;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\AbstractStream;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\StreamProvider;

abstract class AbstractSaga
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param Applicable $state
     * @param EventSelector $epic
     */
    public function tell(Applicable $state, EventSelector $epic)
    {
        $applicableState = array($state, 'apply');

        StreamProvider::create($this->name)
            ->setStore($this->getStore())
            ->setStream($this->getStream())
            ->subscribe($applicableState)
            ->replay($epic);
    }

    /**
     * @return EventStore
     */
    protected function getStore()
    {
        return EventStorePool::provide()->getDefault();
    }

    /**
     * @return AbstractStream
     */
    abstract protected function getStream();
}
