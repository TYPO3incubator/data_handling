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

use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\Domain\Model\ProjectableEntity;
use TYPO3\CMS\DataHandling\Extbase\Persistence\RepositoryInterface;

trait ProjectingTrait
{
    /**
     * @var string
     */
    protected $subjectName;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var EventApplicable
     */
    protected $eventHandler;

    /**
     * @var \Closure[]|callable[]
     */
    protected $streamListeners;

    /**
     * @var \Closure[]|callable[]
     */
    protected $eventListeners;

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
     * @param RepositoryInterface $repository
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @param EventApplicable $eventHandler
     * @return $this
     */
    public function setEventHandler(EventApplicable $eventHandler)
    {
        $this->eventHandler = $eventHandler;
        return $this;
    }

    /**
     * @param array $streamListeners
     * @return $this
     */
    public function setStreamListeners(array $streamListeners)
    {
        $this->streamListeners = $streamListeners;
        return $this;
    }

    /**
     * @param array $eventListeners
     * @return $this
     */
    public function setEventListeners(array $eventListeners)
    {
        $this->eventListeners = $eventListeners;
        return $this;
    }

    /**
     * @param \Closure[]|callable[] $listeners
     * @param AbstractEvent $event
     */
    protected function handleListeners(array $listeners, AbstractEvent $event)
    {
        foreach ($this->findListeners($listeners, $event) as $eventListener) {
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
     * @param \Closure[]|callable[] $listeners
     * @param AbstractEvent $event
     * @return \Closure[]|callable[]
     */
    protected function findListeners(array $listeners, AbstractEvent $event)
    {
        return array_filter(
            $listeners,
            /**
             * @param string $eventName
             * @param AbstractEvent $event
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
