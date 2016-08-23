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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Handler\EventHandlerInterface;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelectorBundle;
use TYPO3\CMS\DataHandling\Extbase\Persistence\RepositoryInterface;

class ProjectionEnrolment
{
    /**
     * @return ProjectionEnrolment
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ProjectionEnrolment::class);
    }

    /**
     * @var EventSelectorBundle
     */
    protected $concerning;

    /**
     * @var string
     */
    protected $subjectName;

    /**
     * @var string
     */
    protected $repositoryName;

    /**
     * @var string
     * @internal
     */
    protected $eventHandlerName;

    /**
     * @var string
     */
    protected $streamProjectionName;

    /**
     * @var string
     */
    protected $eventProjectionName;

    /**
     * @var array
     */
    protected $projectionOptions = [];

    /**
     * @var \Closure[]|callable
     */
    protected $streamListeners = [];

    /**
     * @var \Closure[]|callable
     */
    protected $eventListeners = [];

    /**
     * @return EventSelectorBundle
     */
    public function getConcerning()
    {
        return $this->concerning;
    }

    /**
     * @param EventSelectorBundle $concerning
     * @return ProjectionEnrolment
     */
    public function setConcerning(EventSelectorBundle $concerning)
    {
        $this->concerning = $concerning;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubjectName()
    {
        return $this->subjectName;
    }

    /**
     * @param string $subjectName
     * @return ProjectionEnrolment
     */
    public function setSubjectName(string $subjectName)
    {
        $this->subjectName = $subjectName;
        return $this;
    }

    /**
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->repositoryName;
    }

    /**
     * @param string $repositoryName
     * @return ProjectionEnrolment
     */
    public function setRepositoryName(string $repositoryName)
    {
        $this->repositoryName = $repositoryName;
        return $this;
    }

    /**
     * @return string
     * @internal
     */
    public function getEventHandlerName()
    {
        return $this->eventHandlerName;
    }

    /**
     * @param string $eventHandlerName
     * @return ProjectionEnrolment
     * @internal
     */
    public function setEventHandlerName(string $eventHandlerName)
    {
        $this->eventHandlerName = $eventHandlerName;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreamProjectionName()
    {
        return $this->streamProjectionName;
    }

    /**
     * @param string $streamProjectionName
     * @return ProjectionEnrolment
     */
    public function setStreamProjectionName(string $streamProjectionName)
    {
        $this->streamProjectionName = $streamProjectionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventProjectionName()
    {
        return $this->eventProjectionName;
    }

    /**
     * @param string $eventProjectionName
     * @return ProjectionEnrolment
     */
    public function setEventProjectionName(string $eventProjectionName)
    {
        $this->eventProjectionName = $eventProjectionName;
        return $this;
    }

    /**
     * @return array
     */
    public function getProjectionOptions()
    {
        return $this->projectionOptions;
    }

    /**
     * @param array $projectionOptions
     * @return ProjectionEnrolment
     */
    public function setProjectionOptions(array $projectionOptions)
    {
        $this->projectionOptions = $projectionOptions;
        return $this;
    }

    /**
     * @return callable|\Closure[]
     */
    public function getStreamListeners()
    {
        return $this->streamListeners;
    }

    /**
     * @param string $eventName
     * @param \Closure|callable $listener
     * @return ProjectionEnrolment
     */
    public function onStream($eventName, $listener)
    {
        $this->streamListeners[$eventName] = $listener;
        return $this;
    }

    /**
     * @return callable|\Closure[]
     */
    public function getEventListeners()
    {
        return $this->eventListeners;
    }

    /**
     * @param string $eventName
     * @param \Closure|callable $listener
     * @return ProjectionEnrolment
     */
    public function onEvent($eventName, $listener)
    {
        $this->eventListeners[$eventName] = $listener;
        return $this;
    }

    /**
     * @return StreamProjecting
     */
    public function provideStreamProjection()
    {
        $objectManager = Common::getObjectManager();

        /** @var StreamProjecting $projection */
        $projection = $objectManager->get($this->streamProjectionName);

        if (!empty($this->subjectName)) {
            $projection->setSubjectName($this->subjectName);
        }

        if (!empty($this->repositoryName)) {
            /** @var RepositoryInterface $repository */
            $repository = $objectManager->get($this->repositoryName);
            $projection->setRepository($repository);
        }

        if (!empty($this->eventHandlerName)) {
            /** @var EventApplicable $eventHandler */
            $eventHandler = $objectManager->get($this->eventHandlerName);
            $projection->setEventHandler($eventHandler);
        }

        $projection->setListeners($this->streamListeners);

        return $projection;
    }

    /**
     * @return EventProjecting
     */
    public function provideEventProjection()
    {
        $objectManager = Common::getObjectManager();

        /** @var EventProjecting $projection */
        $projection = $objectManager->get($this->eventProjectionName);

        if (!empty($this->subjectName)) {
            $projection->setSubjectName($this->subjectName);
        }

        if (!empty($this->repositoryName)) {
            /** @var RepositoryInterface $repository */
            $repository = $objectManager->get($this->repositoryName);
            $projection->setRepository($repository);
        }

        if (!empty($this->eventHandlerName)) {
            /** @var EventApplicable $eventHandler */
            $eventHandler = $objectManager->get($this->eventHandlerName);
            $projection->setEventHandler($eventHandler);
        }

        $projection->setListeners($this->eventListeners);

        return $projection;
    }
}
