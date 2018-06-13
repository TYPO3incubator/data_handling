<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\TcaCommand;

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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Bundle;

final class TcaCommandTranslator
{
    public static function create(array $commands)
    {
        return new static($commands);
    }

    private function __construct(array $commands)
    {
        $this->commands = $commands;
    }

    /**
     * @var Command\AbstractCommand[]
     */
    private $commands = [];

    /**
     * @var TcaCommandFactory[]
     */
    private $factories = [];

    /**
     * @return Command\AbstractCommand[]
     */
    public function translate()
    {
        foreach ($this->commands as $command) {
            $tcaCommand = $this->resolveTcaCommand($command);
            if ($tcaCommand === null) {
                continue;
            }

            $entityBehavior = $this->resolveEntityBehavior($command, $tcaCommand);
            if ($tcaCommand->isDeniedPerDefault()) {
                $this->unsetCommand($command);
            }
            if ($entityBehavior === null || !$this->isValid($command, $entityBehavior)) {
                continue;
            }

            $this->getFactory($entityBehavior->getFactoryName())
                ->process($command, $tcaCommand, $entityBehavior);
        }

        $this->finish();
        return $this->commands;
    }

    /**
     * @return Command\AbstractCommand[]
     */
    private function finish()
    {
        foreach ($this->factories as $factory) {
            foreach ($factory->getTranslatedCommands() as $command) {
                $this->unsetCommand($command);
            }
            foreach ($factory->getCreatedCommands() as $command) {
                $this->commands[] = $command;
            }
        }

        $this->commands = array_merge($this->commands);
    }

    /**
     * @param Command\AbstractCommand $command
     * @param TcaCommand $tcaCommand
     * @return null|TcaCommandEntityBehavior
     */
    private function resolveEntityBehavior(Command\AbstractCommand $command, TcaCommand $tcaCommand)
    {
        $entityBehavior = null;
        if ($command instanceof Command\NewEntityCommand) {
            $entityBehavior = $tcaCommand->onCreate();
        }
        if ($command instanceof Command\BranchEntityBundleCommand) {

        }
        if ($command instanceof Command\BranchAndTranslateEntityBundleCommand) {

        }
        if ($command instanceof Command\TranslateEntityBundleCommand) {

        }
        if ($command instanceof Command\ChangeEntityCommand) {
            $entityBehavior = $tcaCommand->onModify();
        }
        if ($command instanceof Command\DeleteEntityCommand) {
            $entityBehavior = $tcaCommand->onDelete();
        }

        if (
            $entityBehavior === null
            || !$entityBehavior->isAllowed()
            || $entityBehavior->getFactoryName() === null
        ) {
            return null;
        }

        return $entityBehavior;
    }

    /**
     * Checks whether whole command bundle can be applied.
     *
     * @param Command\AbstractCommand $command
     * @param TcaCommandEntityBehavior $entityBehavior
     * @return bool
     */
    private function isValid(
        Command\AbstractCommand $command,
        TcaCommandEntityBehavior $entityBehavior
    ) {
        if (!($command instanceof Bundle)) {
            return true;
        }

        foreach ($command->getCommands() as $bundledCommand) {
            if ($bundledCommand instanceof Command\ChangeEntityValuesCommand) {
                $propertyNameIntersections = array_intersect(
                    array_keys($entityBehavior->getProperties()),
                    array_keys($bundledCommand->getValues())
                );
                if (empty($propertyNameIntersections)) {
                    return false;
                }
                continue;
            }
            if ($bundledCommand instanceof Command\AttachRelationCommand) {
                $propertyName = $bundledCommand->getRelationReference()->getName();
                if (
                    !$entityBehavior->hasRelation($propertyName)
                    || !$entityBehavior->forRelation($propertyName)->isAttachAllowed()
                ) {
                    return false;
                }
                continue;
            }
            if ($bundledCommand instanceof Command\RemoveRelationCommand) {
                $propertyName = $bundledCommand->getRelationReference()->getName();
                if (
                    !$entityBehavior->hasRelation($propertyName)
                    || !$entityBehavior->forRelation($propertyName)->isRemoveAllowed()
                ) {
                    return false;
                }
                continue;
            }
            if ($bundledCommand instanceof Command\OrderRelationsCommand) {
                $propertyName = $bundledCommand->getAggregateReference()->getName();
                if (
                    !$entityBehavior->hasRelation($propertyName)
                    || !$entityBehavior->forRelation($propertyName)->isOrderAllowed()
                ) {
                    return false;
                }
                continue;
            }
        }

        return true;
    }

    /**
     * @param Command\AbstractCommand $command
     * @return null|TcaCommand
     */
    private function resolveTcaCommand(Command\AbstractCommand $command)
    {
        $tcaCommandManager = TcaCommandManager::provide();

        $tableName = null;
        if ($command instanceof AggregateReference) {
            $tableName = $command->getAggregateReference()->getName();
        }
        if ($tcaCommandManager->has($tableName)) {
            return $tcaCommandManager->for($tableName);
        }

        return null;
    }

    /**
     * @param string $factoryName
     * @return TcaCommandFactory
     */
    private function getFactory(string $factoryName)
    {
        if (!isset($this->factories[$factoryName])) {
            $this->factories[$factoryName] = new $factoryName();
        }
        return $this->factories[$factoryName];
    }

    /**
     * @param Command\AbstractCommand $command
     */
    private function unsetCommand(Command\AbstractCommand $command)
    {
        $index = array_search($command, $this->commands);
        if ($index !== false) {
            unset($this->commands[$index]);
        }
    }
}
