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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelectorBundle;

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
    protected $providerName;

    /**
     * @var array
     */
    protected $providerOptions = [];

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
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * @param string $providerName
     * @return $this
     */
    public function setProviderName(string $providerName)
    {
        $this->providerName = $providerName;
        return $this;
    }

    /**
     * @return array
     */
    public function getProviderOptions()
    {
        return $this->providerOptions;
    }

    /**
     * @param array $providerOptions
     * @return ProjectionEnrolment
     */
    public function setProviderOptions(array $providerOptions)
    {
        $this->providerOptions = $providerOptions;
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
     * @return ProjectionProvidable
     */
    public function provide()
    {
        return GeneralUtility::makeInstance(
            $this->providerName,
            $this->providerOptions,
            $this->streamListeners,
            $this->eventListeners
        );
    }
}
