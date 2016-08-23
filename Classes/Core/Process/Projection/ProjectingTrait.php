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
use TYPO3\CMS\DataHandling\Core\Domain\Handler\EventHandlerInterface;
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
     * @param RepositoryInterface $repository
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
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
     * @param AbstractEvent $event
     */
    protected function handleListeners(AbstractEvent $event)
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
     * @param AbstractEvent $event
     * @return \Closure[]|callable[]
     */
    protected function findListeners(AbstractEvent $event)
    {
        return array_filter(
            $this->listeners,
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
