<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;

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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event as GenericEvent;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Bundle;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\RelationReference;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Command\CommandHandler;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Command\CommandHandlerTrait;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\Instantiable;

class CommandHandlerBundle implements Instantiable, CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @return CommandHandlerBundle
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param CreateEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleCreateEntityBundleCommand(CreateEntityBundleCommand $command)
    {
        $genericEntity = GenericEntity::createEntity(
            $command->getContext(),
            $command->getAggregateReference(),
            $command->getNodeReference()
        );

        $this->handleBundle($command, $genericEntity);

        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param BranchEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleBranchEntityBundleCommand(BranchEntityBundleCommand $command)
    {
        $sourceEntity = $this->fetchGenericEntity($command);
        $targetEntity = $sourceEntity->branchEntityTo($command->getContext());

        $this->handleBundle($command, $targetEntity);

        GenericEntityEventRepository::instance()->commit($sourceEntity);
        GenericEntityEventRepository::instance()->commit($targetEntity);
    }

    /**
     * @param BranchAndTranslateEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleBranchAndTranslateEntityBundleCommand(BranchAndTranslateEntityBundleCommand $command)
    {
        $sourceEntity = $this->fetchGenericEntity($command);
        $branchedEntity = $sourceEntity->branchEntityTo($command->getContext());
        $targetEntity = $sourceEntity->translateEntityTo($command->getContext());

        $this->handleBundle($command, $targetEntity);

        GenericEntityEventRepository::instance()->commit($sourceEntity);
        GenericEntityEventRepository::instance()->commit($branchedEntity);
        GenericEntityEventRepository::instance()->commit($targetEntity);
    }

    /**
     * @param TranslateEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleTranslateEntityBundleCommand(TranslateEntityBundleCommand $command)
    {
        $sourceEntity = $this->fetchGenericEntity($command);
        $targetEntity = $sourceEntity->translateEntityTo($command->getContext());

        $this->handleBundle($command, $targetEntity);

        GenericEntityEventRepository::instance()->commit($sourceEntity);
        GenericEntityEventRepository::instance()->commit($targetEntity);
    }

    /**
     * @param ModifyEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleModifyEntityBundleCommand(ModifyEntityBundleCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);

        $this->handleBundle($command, $genericEntity);

        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param ModifyEntityCommand $command
     * @param GenericEntity $genericEntity
     * @return GenericEntity
     */
    protected function handleModifyEntityCommand(ModifyEntityCommand $command, GenericEntity $genericEntity = null)
    {
        $genericEntity = ($genericEntity ?? $this->fetchGenericEntity($command));
        $genericEntity->modifyEntity(
            $command->getContext(),
            $command->getData()
        );
        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param DeleteEntityCommand $command
     * @param GenericEntity $genericEntity
     * @return GenericEntity
     */
    protected function handleDeleteEntityCommand(DeleteEntityCommand $command, GenericEntity $genericEntity = null)
    {
        $genericEntity = ($genericEntity ?? $this->fetchGenericEntity($command));
        $genericEntity->deleteEntity(
            $command->getContext()
        );
        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param AttachRelationCommand $command
     * @param GenericEntity $genericEntity
     * @return GenericEntity
     */
    protected function handleAttachRelationCommand(AttachRelationCommand $command, GenericEntity $genericEntity = null)
    {
        $genericEntity = ($genericEntity ?? $this->fetchGenericEntity($command));
        $genericEntity->attachRelation(
            $command->getContext(),
            $command->getRelationReference()
        );
        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param RemoveRelationCommand $command
     * @param GenericEntity $genericEntity
     */
    protected function handleRemoveRelationCommand(RemoveRelationCommand $command, GenericEntity $genericEntity = null)
    {
        $genericEntity = ($genericEntity ?? $this->fetchGenericEntity($command));
        $genericEntity->removeRelation(
            $command->getContext(),
            $command->getRelationReference()
        );
        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param OrderRelationsCommand $command
     * @param GenericEntity $genericEntity
     */
    protected function handleOrderRelationsCommand(OrderRelationsCommand $command, GenericEntity $genericEntity = null)
    {
        $genericEntity = ($genericEntity ?? $this->fetchGenericEntity($command));
        $genericEntity->orderRelations(
            $command->getContext(),
            $command->getSequence()
        );
        GenericEntityEventRepository::instance()->commit($genericEntity);
    }

    /**
     * @param Bundle $bundleCommand
     * @param GenericEntity $genericEntity
     */
    private function handleBundle(Bundle $bundleCommand, GenericEntity $genericEntity)
    {
        foreach ($bundleCommand->getCommands() as $command) {
            // determine method name, that is used to execute the command
            $methodName = $this->getCommandHandlerMethodName($command);
            if (method_exists($this, $methodName)) {
                $this->{$methodName}($command, $genericEntity);
            }
        }
    }

    /**
     * @param AbstractCommand|AggregateReference|RelationReference $command
     * @return GenericEntity
     */
    private function fetchGenericEntity(AbstractCommand $command)
    {
        $aggregateReference = $command
            ->getAggregateReference();

        return GenericEntityEventRepository::instance()
            ->findByAggregateReference($aggregateReference);
    }
}
