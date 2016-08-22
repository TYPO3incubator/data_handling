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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\EventStream;
use TYPO3\CMS\DataHandling\Extbase\Persistence\RepositoryInterface;

interface Projecting
{
    public function setSubjectName(string $subject);

    public function setRepository(RepositoryInterface $repository);

    public function setEventHandler(EventApplicable $eventHandler);

    public function setEventListeners(array $listeners);

    /**
     * @param EventStream $stream
     */
    public function projectStream(EventStream $stream);

    /**
     * @param AbstractEvent $event
     */
    public function projectEvent(AbstractEvent $event);
}
