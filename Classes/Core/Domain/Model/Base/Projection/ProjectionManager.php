<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Projection;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Providable;

class ProjectionManager implements Providable
{
    /**
     * @var ProjectionManager
     */
    private static $instance;

    /**
     * @param bool $force
     * @return ProjectionManager
     */
    public static function provide(bool $force = false) {
        if ($force || !isset(self::$instance)) {
            self::$instance = static::instance();
        }
        return self::$instance;
    }

    /**
     * @return ProjectionManager
     */
    private static function instance()
    {
        return GeneralUtility::makeInstance(ProjectionManager::class);
    }

    /**
     * @var Projection[][]
     */
    private $projections = [];

    /**
     * @param Projection $projection
     * @return static
     */
    public function registerProjection(Projection $projection)
    {
        foreach ($projection->listensTo() as $eventType) {
            if (!isset($this->projections[$eventType])) {
                $this->projections[$eventType] = [];
            }
            $this->projections[$eventType][] = $projection;
        }
        return $this;
    }

    /**
     * @param Projection[] $projections
     * @return static
     */
    public function registerProjections(array $projections)
    {
        foreach ($projections as $projection) {
            $this->registerProjection($projection);
        }
        return $this;
    }

    /**
     * @param BaseEvent $event
     */
    public function projectEvent(BaseEvent $event)
    {
        $eventType = get_class($event);
        if (!isset($this->projections[$eventType])) {
            return;
        }

        foreach ($this->projections[$eventType] as $projection) {
            $projection->project($event);
        }
    }

    /**
     * @param BaseEvent[] $events
     */
    public function projectEvents(array $events)
    {
        foreach ($events as $event) {
            $this->projectEvent($event);
        }
    }
}
