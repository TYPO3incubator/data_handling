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

use H4ck3r31\BankAccountExample\Domain\Event\AbstractEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver\EventTraversable;

class EventStream
{
    /**
     * @param string $streamName
     * @param EventTraversable $traversable
     * @return EventStream
     */
    static public function create(string $streamName, EventTraversable $traversable)
    {
        return GeneralUtility::makeInstance(EventStream::class, $streamName, $traversable);
    }

    /**
     * @var EventTraversable
     */
    protected $traversable;

    /**
     * @var string
     */
    protected $streamName;

    public function __construct(string $streamName, EventTraversable $traversable)
    {
        $this->streamName = $streamName;
        $this->traversable = $traversable;
    }

    /**
     * @return string
     */
    public function getStreamName()
    {
        return $this->streamName;
    }

    /**
     * @return \IteratorIterator|AbstractEvent[]
     */
    public function forAll()
    {
        return new \IteratorIterator($this->traversable);
    }
}
