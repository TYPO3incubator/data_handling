<?php
namespace TYPO3\CMS\DataHandling\Extbase\Persistence;

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

use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventHandlerInterface;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\ProjectionProvidable;

class EntityProjectionProvider implements ProjectionProvidable
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Closure[]|callable
     */
    protected $streamListeners = [];

    /**
     * @var \Closure[]|callable
     */
    protected $eventListeners = [];

    /**
     * @param array $options
     * @param array $streamListeners
     * @param array $eventListeners
     */
    public function __construct(array $options, array $streamListeners, array $eventListeners)
    {
        $this->options = $options;
        $this->streamListeners = $streamListeners;
        $this->eventListeners = $eventListeners;
    }

    /**
     * @return EntityStreamProjection
     */
    public function forStream()
    {
        $objectManager = Common::getObjectManager();

        $projection = EntityStreamProjection::instance();

        if (!empty($this->options['subjectName'])) {
            $projection->setSubjectName($this->options['subjectName']);
        }

        if (!empty($this->options['eventRepositoryName'])) {
            /** @var EventRepository $eventRepository */
            $eventRepository = $objectManager->get($this->options['eventRepositoryName']);
            $projection->setEventRepository($eventRepository);
        }

        if (!empty($this->options['projectionRepositoryName'])) {
            /** @var ProjectionRepository $projectionRepository */
            $projectionRepository = $objectManager->get($this->options['projectionRepositoryName']);
            $projection->setProjectionRepository($projectionRepository);
        }

        if (!empty($this->options['eventHandlerName'])) {
            /** @var EventHandlerInterface $eventHandler */
            $eventHandler = $objectManager->get($this->options['eventHandlerName']);
            $projection->setEventHandler($eventHandler);
        }

        $projection->setListeners($this->streamListeners);

        return $projection;
    }

    /**
     * @return EntityEventProjection
     */
    public function forEvent()
    {
        $objectManager = Common::getObjectManager();

        $projection = EntityEventProjection::instance();

        if (!empty($this->options['subjectName'])) {
            $projection->setSubjectName($this->options['subjectName']);
        }

        if (!empty($this->options['eventRepositoryName'])) {
            /** @var EventRepository $eventRepository */
            $eventRepository = $objectManager->get($this->options['eventRepositoryName']);
            $projection->setEventRepository($eventRepository);
        }

        if (!empty($this->options['projectionRepositoryName'])) {
            /** @var ProjectionRepository $projectionRepository */
            $projectionRepository = $objectManager->get($this->options['projectionRepositoryName']);
            $projection->setProjectionRepository($projectionRepository);
        }

        if (!empty($this->options['eventHandlerName'])) {
            /** @var EventHandlerInterface $eventHandler */
            $eventHandler = $objectManager->get($this->options['eventHandlerName']);
            $projection->setEventHandler($eventHandler);
        }

        $projection->setListeners($this->eventListeners);

        return $projection;
    }
}
