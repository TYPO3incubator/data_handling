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
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Driver\EventTraversable;

class EventStream extends \IteratorIterator
{
    /**
     * @param EventTraversable $traversable
     * @param string $streamName
     * @return EventStream
     */
    public static function create(EventTraversable $traversable, string $streamName)
    {
        return GeneralUtility::makeInstance(EventStream::class, $traversable, $streamName);
    }

    /**
     * @var EventTraversable
     */
    protected $traversable;

    /**
     * @var string
     */
    protected $streamName;

    public function __construct(EventTraversable $traversable, string $streamName)
    {
        $this->traversable = $traversable;
        $this->streamName = $streamName;

        parent::__construct($traversable);
    }

    /**
     * @return string
     */
    public function getStreamName()
    {
        return $this->streamName;
    }
}
