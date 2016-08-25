<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler;

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

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Command\DomainCommand;
use TYPO3\CMS\DataHandling\Core\Process\EventPublisher;
use TYPO3\CMS\DataHandling\Core\Utility\ClassNamingUtility;

trait CommandHandlerTrait
{
    /**
     * @return UuidInterface
     */
    protected static function createUuid()
    {
        return Uuid::uuid4();
    }

    /**
     * @param EventRepository $repository
     * @param AbstractEvent $event
     */
    protected static function emitEvent(EventRepository $repository, AbstractEvent $event)
    {
        $repository->addEvent($event);
        EventPublisher::instance()->publish($event);
    }

    /**
     * @param DomainCommand $command
     */
    public function execute(DomainCommand $command)
    {
        // determine method name, that is used to execute the command
        $methodName = $this->getCommandHandlerMethodName($command);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($command);
        }
    }

    /**
     * @param DomainCommand $command
     * @return string
     */
    protected function getCommandHandlerMethodName(DomainCommand $command)
    {
        $commandName = ClassNamingUtility::getLastPart($command);
        return 'on' . $commandName;
    }
}
