<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Store;

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
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;

class EventSelector implements Instantiable
{
    const PATTERN = '#'
        . '^(?P<all>\*)$|'
        . '^(?:\$(?P<streamName>[^.\[]+)?)?'
            . '(?:(?P<categoryPart>(?:\.[^.\[]+)+))?'
            . '(?:\[(?P<eventList>[^\]]+)\])?$'
        . '#';

    /**
     * Syntax: "$stream.category[event]"
     *
     * + "$prefix/record/abcd" -> select exact stream name
     * + "$prefix/record/*" -> select matching wildcard stream name
     * + "*" -> select everything
     *
     * @param string $selector
     * @return EventSelector
     */
    public static function create(string $selector)
    {
        if (!preg_match(static::PATTERN, $selector, $matches)) {
            throw new \RuntimeException('Invalid event selector "' . $selector . '"', 1471435329);
        }

        $eventSelector = static::instance();

        if (!empty($matches['all'])) {
            $eventSelector->all = true;
            return $eventSelector;
        }

        $streamName = ($matches['streamName'] ?? null);
        $categoryPart = ($matches['categoryPart'] ?? null);
        $eventList = ($matches['eventList'] ?? null);

        $categories = [];
        $events = [];

        if ($streamName !== null) {
            trim($streamName);
        }
        if ($categoryPart !== null) {
            $categories = GeneralUtility::trimExplode('.', $categoryPart, true);
        }
        if ($eventList) {
            $events = GeneralUtility::trimExplode(',', $eventList, true);
        }

        if (empty($streamName) && empty($categories) && empty($events)) {
            throw new \RuntimeException('Event selector without stream name, categories and events', 1471435330);
        }

        if (!empty($streamName)) {
            $eventSelector->setStreamName($streamName);
        }
        if (!empty($categories)) {
            $eventSelector->setCategories($categories);
        }
        if (!empty($events)) {
            $eventSelector->setEvents($events);
        }

        return $eventSelector;
    }

    /**
     * @return EventSelector
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventSelector::class);
    }

    /**
     * @var bool
     */
    protected $all = false;

    /**
     * @var string
     */
    protected $streamName = '';

    /**
     * @var string[]
     */
    protected $categories = [];

    /**
     * @var string[]
     */
    protected $events = [];

    /**
     * @return bool
     */
    public function isAll()
    {
        return $this->all;
    }

    /**
     * @param bool $all
     * @return EventSelector
     */
    public function setAll(bool $all)
    {
        $this->all = $all;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreamName()
    {
        return $this->streamName;
    }

    /**
     * @param string $streamName
     * @return EventSelector
     */
    public function setStreamName(string $streamName)
    {
        $this->streamName = $streamName;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     * @return EventSelector
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param array $events
     * @return EventSelector
     */
    public function setEvents(array $events)
    {
        $this->events = $events;
        return $this;
    }
}
