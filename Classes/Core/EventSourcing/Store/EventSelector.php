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
    /**
     * @param string $selector
     * @return EventSelector
     */
    public static function create(string $selector)
    {
        // (?P<categories>\[[^\]]+\])?
        if (!preg_match('#(?:\$(?P<streamName>[^\[]+)?)?(?:\[(?P<categories>[^\]]+)\])?#', $selector, $matches)) {
            throw new \RuntimeException('Invalid event selector "' . $selector . '"', 1471435329);
        }

        if (
            empty($matches['streamName'])
            && (empty($matches['categories']) || trim($matches['categories'], ' ,') === '')
        ) {
            throw new \RuntimeException('Event selector without stream name and categories', 1471435330);
        }

        $eventSelector = static::instance();

        if (!empty($matches['streamName'])) {
            $eventSelector->setStreamName($matches['streamName']);
        }
        if (!empty($matches['categories'])) {
            $eventSelector->setCategories(
                GeneralUtility::trimExplode(',', $matches['categories'], true)
            );
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
     * @var string
     */
    protected $streamName = '';

    /**
     * @var array
     */
    protected $categories = [];

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
}
