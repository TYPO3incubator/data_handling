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

use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventHandlerInterface;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;
use TYPO3\CMS\DataHandling\Extbase\Persistence\ProjectionRepository;

interface Projecting
{
    /**
     * @param string $subject
     */
    public function setSubjectName(string $subject);

    /**
     * @param EventRepository $repository
     */
    public function setEventRepository(EventRepository $repository);

    /**
     * @param ProjectionRepository $repository
     */
    public function setProjectionRepository(ProjectionRepository $repository);

    /**
     * @param EventHandlerInterface $eventHandler
     * @internal
     */
    public function setEventHandler(EventHandlerInterface $eventHandler);

    /**
     * @param array $listeners
     */
    public function setListeners(array $listeners);
}
