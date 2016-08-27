<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Process\Projection;

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

use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventHandlerInterface;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;
use TYPO3\CMS\DataHandling\Extbase\Persistence\ProjectionRepository;

trait ProjectingTrait
{
    /**
     * @var string
     */
    protected $subjectName;

    /**
     * @var ProjectionRepository
     */
    protected $projectionRepository;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var EventHandlerInterface
     * @internal
     */
    protected $eventHandler;

    /**
     * @var \Closure[]|callable[]
     */
    protected $listeners;

    /**
     * @param string $subjectName
     * @return $this
     */
    public function setSubjectName(string $subjectName)
    {
        $this->subjectName = $subjectName;
        return $this;
    }

    /**
     * @param ProjectionRepository $projectionRepository
     * @return $this
     */
    public function setProjectionRepository(ProjectionRepository $projectionRepository)
    {
        $this->projectionRepository = $projectionRepository;
        return $this;
    }

    /**
     * @param EventRepository $eventRepository
     * @return $this
     */
    public function setEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
        return $this;
    }

    /**
     * @param EventHandlerInterface $eventHandler
     * @return $this
     * @internal
     */
    public function setEventHandler(EventHandlerInterface $eventHandler)
    {
        $this->eventHandler = $eventHandler;
        return $this;
    }

    /**
     * @param array $streamListeners
     * @return $this
     */
    public function setListeners(array $streamListeners)
    {
        $this->listeners = $streamListeners;
        return $this;
    }

    /**
     * @param BaseEvent $event
     */
    protected function handleListeners(BaseEvent $event)
    {
        foreach ($this->findListeners($event) as $eventListener) {
            if ($event->isCancelled()) {
                break;
            }
            call_user_func(
                $eventListener,
                $event,
                $this
            );
        }
    }

    /**
     * @param BaseEvent $event
     * @return \Closure[]|callable[]
     */
    protected function findListeners(BaseEvent $event)
    {
        return array_filter(
            $this->listeners,
            /**
             * @param string $eventName
             * @param BaseEvent $event
             * @return bool
             */
            function(string $eventName) use ($event)
            {
                return is_a($event, $eventName);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
