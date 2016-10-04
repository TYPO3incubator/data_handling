<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Instantiable;

class EventStoreEnrolment implements Instantiable
{
    /**
     * @return EventStoreEnrolment
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventStoreEnrolment::class);
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @var EventStore
     */
    protected $store;

    /**
     * @var EventSelector
     */
    protected $concerning;

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getStore()
    {
        return $this->store;
    }

    /**
     * @param EventStore $store
     * @return EventStoreEnrolment
     */
    public function setStore(EventStore $store)
    {
        $this->store = $store;
        return $this;
    }

    public function getConcerning()
    {
        return $this->concerning;
    }

    public function setConcerning(EventSelector $concerning)
    {
        $this->concerning = $concerning;
        return $this;
    }

    public function concerning(string $concerning)
    {
        $this->concerning = EventSelector::create($concerning);
        return $this;
    }
}
