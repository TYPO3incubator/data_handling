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
use TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\CommandApplicable;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\CommandHandlerTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CommandHandler implements Instantiable, CommandApplicable
{
    use CommandHandlerTrait;

    /**
     * @return CommandHandler
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CommandHandler::class);
    }

    /**
     * @param CreateEntityCommand $command
     */
    protected function onCreateEntityCommand(CreateEntityCommand $command)
    {
        GenericEntity::createdEntity(
            $command->getAggregateType(),
            $command->getWorkspaceId(),
            $command->getLocale()
        );
    }

    /**
     * @param BranchEntityCommand $command
     */
    protected function onBranchEntityCommand(BranchEntityCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->branchedEntityTo($command->getWorkspaceId());
    }

    /**
     * @param TranslateEntityCommand $command
     */
    protected function onTranslateEntityCommand(TranslateEntityCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->translatedEntityTo($command->getLocale());
    }

    /**
     * @param ChangeEntityCommand $command
     */
    protected function onChangeEntityCommand(ChangeEntityCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->changedEntity($command->getData());
    }

    /**
     * @param DeleteEntityCommand $command
     */
    protected function onDeleteEntityCommand(DeleteEntityCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->deletedEntity();
    }

    /**
     * @param AttachRelationCommand $command
     */
    protected function onAttachRelationCommand(AttachRelationCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->attachedRelation($command->getRelationReference());
    }

    /**
     * @param RemoveRelationCommand $command
     */
    protected function onRemoveRelationCommand(RemoveRelationCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->removedRelation($command->getRelationReference());
    }

    /**
     * @param OrderRelationsCommand $command
     */
    protected function onOrderRelationsCommand(OrderRelationsCommand $command)
    {
        $this->fetchGenericEntity($command)
            ->orderedRelations($command->getSequence());
    }

    /**
     * @param AbstractCommand $command
     * @return GenericEntity
     */
    protected function fetchGenericEntity(AbstractCommand $command)
    {
        $aggregateId = $command->getSubject()->getUuid();
        $aggregateType = $command->getSubject()->getName();
        return GenericEntityEventRepository::create($aggregateType)
            ->findByUuid(\Ramsey\Uuid\Uuid::fromString($aggregateId));
    }
}
