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

class EventSelectorBundle extends \ArrayObject implements Instantiable
{
    /**
     * @return EventSelectorBundle
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventSelectorBundle::class);
    }

    /**
     * @param array|object $input The input parameter accepts an array or an Object.
     * @param int $flags Flags to control the behaviour of the ArrayObject object.
     * @param string $iterator_class Specify the class that will be used for iteration of the ArrayObject object.
     */
    public function __construct($input = null, $flags = 0, $iterator_class = 'ArrayIterator')
    {
        parent::__construct([], $flags, $iterator_class);

        if (!empty($input)) {
            $this->attachAll($input);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $identifiers = [];
        /** @var EventSelector $item */
        foreach ($this as $item) {
            $identifiers[] = $item->__toString();
        }
        return implode('@', $identifiers);
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
        /** @var EventSelector $item */
        foreach ($this as $item) {
            if ($item->fulfills($selector)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string[]|EventSelector[] $concerning
     * @return EventSelectorBundle
     */
    public function attachAll(array $concerning)
    {
        foreach ($concerning as $index => $item) {
            $this->offsetSet($index, $item);
        }
        return $this;
    }

    /**
     * @param mixed $index
     * @param string|EventSelector $concerning
     */
    public function offsetSet($index, $concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        parent::offsetSet($index, $concerning);
    }
}
