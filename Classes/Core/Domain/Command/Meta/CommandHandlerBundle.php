<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Command\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta as GenericEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Bundle;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\CommandHandler;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\CommandHandlerBundlableTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CommandHandlerBundle implements Instantiable, CommandHandler
{
    use CommandHandlerBundlableTrait;

    /**
     * @return CommandHandlerBundle
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param CreateEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleCreateEntityBundleCommand(CreateEntityBundleCommand $command)
    {
        $genericEntity = GenericEntity::createdEntity(
            $command->getAggregateReference(),
            $command->getNodeReference(),
            $command->getWorkspaceId(),
            $command->getLocale()
        );

        $this->handleBundle($command, $genericEntity);

        GenericEntityEventRepository::instance()->add($genericEntity);
    }

    /**
     * @param BranchEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleBranchEntityBundleCommand(BranchEntityBundleCommand $command)
    {
        $sourceEntity = $this->fetchGenericEntity($command);
        $targetEntity = $sourceEntity->branchedEntityTo($command->getWorkspaceId());

        $this->handleBundle($command, $targetEntity);

        GenericEntityEventRepository::instance()->add($sourceEntity);
        GenericEntityEventRepository::instance()->add($targetEntity);
    }

    /**
     * @param BranchAndTranslateEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleBranchAndTranslateEntityBundleCommand(BranchAndTranslateEntityBundleCommand $command)
    {
        $sourceEntity = $this->fetchGenericEntity($command);
        $targetEntity = $sourceEntity->branchedEntityTo($command->getWorkspaceId());

        $this->handleBundle($command, $targetEntity);

        GenericEntityEventRepository::instance()->add($sourceEntity);
        GenericEntityEventRepository::instance()->add($targetEntity);
    }

    /**
     * @param TranslateEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleTranslateEntityBundleCommand(TranslateEntityBundleCommand $command)
    {
        $sourceEntity = $this->fetchGenericEntity($command);
        $targetEntity = $sourceEntity->translatedEntityTo($command->getLocale());

        $this->handleBundle($command, $targetEntity);

        GenericEntityEventRepository::instance()->add($sourceEntity);
        GenericEntityEventRepository::instance()->add($targetEntity);
    }

    /**
     * @param ModifyEntityBundleCommand $command
     * @return GenericEntity
     */
    protected function handleModifyEntityBundleCommand(ModifyEntityBundleCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);

        $this->handleBundle($command, $genericEntity);

        GenericEntityEventRepository::instance()->add($genericEntity);
    }

    /**
     * @param ChangeEntityCommand $command
     * @return GenericEntity
     */
    protected function handleChangeEntityCommand(ChangeEntityCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);
        $genericEntity->changedEntity($command->getData());
        GenericEntityEventRepository::instance()->add($genericEntity);
    }

    /**
     * @param DeleteEntityCommand $command
     * @return GenericEntity
     */
    protected function handleDeleteEntityCommand(DeleteEntityCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);
        $genericEntity->deletedEntity();
        GenericEntityEventRepository::instance()->add($genericEntity);
    }

    /**
     * @param AttachRelationCommand $command
     * @return GenericEntity
     */
    protected function handleAttachRelationCommand(AttachRelationCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);
        $genericEntity->attachedRelation($command->getRelationReference());
        GenericEntityEventRepository::instance()->add($genericEntity);
    }

    /**
     * @param RemoveRelationCommand $command
     */
    protected function handleRemoveRelationCommand(RemoveRelationCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);
        $genericEntity->removedRelation($command->getRelationReference());
        GenericEntityEventRepository::instance()->add($genericEntity);
    }

    /**
     * @param OrderRelationsCommand $command
     */
    protected function handleOrderRelationsCommand(OrderRelationsCommand $command)
    {
        $genericEntity = $this->fetchGenericEntity($command);
        $genericEntity->orderedRelations($command->getSequence());
        GenericEntityEventRepository::instance()->add($genericEntity);
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
     * @param AbstractCommand $command
     * @return GenericEntity
     */
    private function fetchGenericEntity(AbstractCommand $command)
    {
        return GenericEntityEventRepository::instance()
            ->findByAggregateReference($command->getAggregateReference());
    }
}
