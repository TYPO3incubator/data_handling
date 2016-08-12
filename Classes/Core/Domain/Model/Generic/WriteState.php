<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Generic;

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
use TYPO3\CMS\DataHandling\Core\Domain\Command\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Generic as GenericCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic as GenericEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\State;
use TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager;

class WriteState extends State
{
    /**
     * @param EntityReference $reference
     * @return WriteState
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(WriteState::class, EntityReference::instance());
    }

    /**
     * @param EntityReference $reference
     * @return WriteState
     */
    public static function reference(EntityReference $reference) {
        return GeneralUtility::makeInstance(WriteState::class, $reference);
    }

    /**
     * @param EntityReference $reference
     */
    public function __construct(EntityReference $reference)
    {
        parent::__construct();
        $this->reference = $reference;
    }

    /**
     * @param AbstractCommand $command
     * @return WriteState
     */
    public function handleCommand(AbstractCommand $command)
    {
        $classNameParts = GeneralUtility::trimExplode('\\', get_class($command), true);
        $commandName = $classNameParts[count($classNameParts) - 1];
        $callable = array($this, 'handle' . ucfirst($commandName));

        if (is_callable($callable)) {
            call_user_func($callable, $command);
        }

        return $this;
    }

    /**
     * @param GenericCommand\CreateCommand $command
     * @return WriteState
     */
    public function handleCreateCommand(GenericCommand\CreateCommand $command)
    {
        $this->reference = $command->getIdentity();
        EventManager::provide()->manage(
            GenericEvent\CreatedEvent::fromCommand($command)
        );
        return $this;
    }

    /**
     * @param GenericCommand\ChangeCommand $command
     * @return WriteState
     */
    public function handleChangeCommand(GenericCommand\ChangeCommand $command)
    {
        $this->values = $command->getData();
        EventManager::provide()->manage(
            GenericEvent\ChangedEvent::fromCommand($command)
        );
        return $this;
    }
}
