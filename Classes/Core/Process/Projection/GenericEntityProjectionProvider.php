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

use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\ProjectionProvidable;

class GenericEntityProjectionProvider implements ProjectionProvidable
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
     * @return \TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\StreamProjecting
     */
    public function forStream()
    {
        $projection = GenericEntityStreamProjection::instance();
        $projection->setListeners($this->streamListeners);
        return $projection;
    }

    /**
     * @return \TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\EventProjecting
     */
    public function forEvent()
    {
        $projection = GenericEntityEventProjection::instance();
        $projection->setListeners($this->eventListeners);
        return $projection;
    }
}
