<?php
namespace TYPO3\CMS\DataHandling\Core\Process\Projection;

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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelectorBundle;
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class ProjectionPool implements Providable
{
    /**
     * @var ProjectionPool
     */
    protected static $projectionPool;

    /**
     * @param bool $force
     * @return ProjectionPool
     */
    public static function provide(bool $force = false) {
        if ($force || !isset(static::$projectionPool)) {
            static::$projectionPool = static::instance();
        }
        return static::$projectionPool;
    }

    /**
     * @return ProjectionPool
     */
    protected static function instance()
    {
        return GeneralUtility::makeInstance(ProjectionPool::class);
    }

    /**
     * @var ProjectionEnrolment[]
     */
    protected $enrolments = [];

    /**
     * @param string[]|EventSelector[] ...$concerning
     * @return ProjectionEnrolment
     */
    public function enrolProjection(...$concerning)
    {
        $concerning = EventSelectorBundle::instance()->attachAll($concerning);
        $identifier = $concerning->__toString();

        if (isset($this->enrolments[$identifier])) {
            throw new \RuntimeException('Enrolment for "' . $identifier . '" is already defined', 1471886578);
        }

        $this->enrolments[$identifier] = ProjectionEnrolment::instance()->setConcerning($concerning);
        return $this->enrolments[$identifier];
    }

    /**
     * @param string|EventSelector $concerning
     * @return ProjectionEnrolment
     */
    public function getFor($concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        foreach ($this->enrolments as $enrolment) {
            if ($enrolment->getConcerning()->fulfills($concerning)) {
                return $enrolment;
            }
        }

        throw new \RuntimeException('No enrolment found for "' . $concerning . '"', 1471890973);
    }
}
