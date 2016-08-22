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
     * @param array $eventListeners
     * @return $this
     */
    public function setEventListeners(array $eventListeners)
    {
        $this->eventListeners = $eventListeners;
        return $this;
    }

    /**
     * @param AbstractEvent $event
     * @return \Closure[]|callable[]
     */
    protected function findEventListeners(AbstractEvent $event)
    {
        $validEventListeners = [];

        foreach ($this->eventListeners as $eventName => $eventListener) {
            if (is_a($event, $eventName)) {
                $validEventListeners[] = $eventListener;
            }
        }

        return $validEventListeners;
    }
}
