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
    const DELIMITER_STREAM_NAME = '/';
    const LITERAL_PATTERN = '#[~$.\[,\]]#';
    const SELECTOR_PATTERN = '#'
        . '^(?P<all>\*)$|'
        . '^((?P<streamType>\$|~)(?P<streamName>[^.\[]+)?)?'
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
        if (!preg_match(static::SELECTOR_PATTERN, $selector, $matches)) {
            throw new \RuntimeException('Invalid event selector "' . $selector . '"', 1471435329);
        }

        $eventSelector = static::instance();

        if (!empty($matches['all'])) {
            $eventSelector->all = true;
            return $eventSelector;
        }

        if ($matches['streamType'] === '~') {
            $eventSelector->relative = true;
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
     * @param string $string
     * @return string
     */
    public static function sanitizePrefixPart(string $string)
    {
        return static::cleanPrefixPart($string) . static::DELIMITER_STREAM_NAME;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function cleanPrefixPart(string $string)
    {
        return rtrim($string, static::DELIMITER_STREAM_NAME);
    }

    /**
     * Removes the wildcard literal and sanitizes the value
     * by sanitizing for the stream delimiter at the end.
     *
     * @param string $string
     * @return string
     */
    public static function getComparablePart(string $string)
    {
        $wildcardPosition = strpos($string, '*');
        if ($wildcardPosition === false) {
            return $string;
        }

        $comparablePart = static::sanitizePrefixPart(
            substr($string, 0, $wildcardPosition)
        );

        return $comparablePart;
    }

    /**
     * @var bool
     */
    protected $all = false;

    /**
     * @var bool
     */
    protected $relative = false;

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
     * @return bool
     */
    public function isRelative()
    {
        return $this->relative;
    }

    /**
     * @param bool $relative
     * @return EventSelector
     */
    public function setRelative(bool $relative)
    {
        $this->relative = $relative;
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
        $this->validateLiterals($streamName);
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
        array_map(
            $this->validateLiteralsClosure(),
            $categories
        );
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
        array_map(
            $this->validateLiteralsClosure(),
            $events
        );
        $this->events = $events;
        return $this;
    }

    /**
     * Determines whether this current selector is a
     * super-set of a given selector to be compared to.
     *
     * @param EventSelector $selector
     * @return bool
     */
    public function fulfills(EventSelector $selector)
    {
        if ($this->all) {
            return true;
        }

        if (
            !empty($this->streamName) && !empty($selector->getStreamName())
            && !$this->compareWildcards($this->streamName, $selector->getStreamName())
        ) {
            // stream names are not matching (including wildcard comparison)
            return false;
        }

        if (
            !empty($this->categories) && !empty($selector->getCategories())
            && count(array_intersect($this->categories, $selector->getCategories())) === 0
        ) {
            // if not a single category was matching
            return false;
        }

        if (
            !empty($this->events) && !empty($selector->getEvents())
            && !$this->compareClassInheritances($this->events, $selector->getEvents())
        ) {
            // if none of the given events equals or inherits one of our events
            return false;
        }

        return true;
    }

    /**
     * @param string $value
     */
    protected function validateLiterals(string $value)
    {
        if (preg_match(static::LITERAL_PATTERN, $value)) {
            throw new \RuntimeException('Value "' . $value . '" contains invalid literal patterns', 1471690906);
        }
    }

    /**
     * @return \Closure
     */
    protected function validateLiteralsClosure()
    {
        return function(string $value) {
            $this->validateLiterals($value);
        };
    }

    /**
     * Creates absolute event stream selector by adding
     * prefix if the current selector is relative.
     *
     * @param string $streamNamePrefix
     * @return EventSelector
     */
    public function toAbsolute(string $streamNamePrefix)
    {
        if (!$this->relative || $this->all) {
            return $this;
        }
        if (empty($streamNamePrefix)) {
            throw new \RuntimeException('Stream name prefix must not be empty', 1471681523);
        }

        $eventSelector = static::instance()
            ->setStreamName(static::sanitizePrefixPart($streamNamePrefix) . $this->streamName)
            ->setCategories($this->categories)
            ->setEvents($this->events);

        return $eventSelector;
    }

    protected function compareWildcards(string $requirement, string $needle)
    {
        return (
            strpos(
                static::getComparablePart($needle),
                static::getComparablePart($requirement)
            ) === 0
        );
    }

    /**
     * @param string[] $requirements
     * @param string[] $needles
     * @return bool
     */
    protected function compareClassInheritances(array $requirements, array $needles)
    {
        foreach ($requirements as $requirement) {
            foreach ($needles as $needle) {
                // is $needle is or is a sub-class of $requirement
                if (is_a($needle, $requirement, true)) {
                    return true;
                }
            }
        }
        return false;
    }
}
