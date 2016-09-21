<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Command;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\SuggestedState;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Service\SortingComparisonService;

class CommandResolver
{
    /**
     * @param Change[] $changes
     * @return CommandResolver
     */
    public static function create(array $changes)
    {
        return GeneralUtility::makeInstance(static::class, $changes);
    }

    /**
     * @param Change[] $changes
     */
    public function __construct(array $changes)
    {
        $this->changes = $changes;
        $this->resolve();
    }

    /**
     * @var Change[]
     */
    private $changes;

    /**
     * @var Command\AbstractCommand[]
     */
    private $commands = [];

    /**
     * @var Command\CommandBuilder
     */
    private $commandBuilder;

    /**
     * @return Command\AbstractCommand[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    private function resolve()
    {
        // root aggregate change is processed first
        foreach ($this->changes as $change) {
            $this->commandBuilder = Command\CommandBuilder::instance();
            $this->processContext($change);
            $this->processValues($change);
            $this->processRelations($change);

            if (!is_null($command = $this->commandBuilder->build())) {
                $this->commands[] = $command;
            }
        }
    }

    private function processContext(Change $change)
    {
        if ($change->isNew()) {
            $this->commandBuilder->newCreateCommand(
                $change->getTargetState()->getContext(),
                $change->getTargetState()->getSubject(),
                $change->getTargetState()->getNode()
            );
        } elseif ($this->isDifferentContext()) {
            $this->commandBuilder->newBranchCommand(
                $change->getTargetState()->getContext(),
                $change->getTargetState()->getSubject()
            );
        } else {
            $this->commandBuilder->newModifyCommand(
                $change->getTargetState()->getContext(),
                $change->getTargetState()->getSubject()
            );
        }
    }

    private function processValues(Change $change)
    {
        $aggregateReference = $change->getTargetState()->getSubject();

        if ($change->isNew()) {
            $values = $change->getTargetState()->getValues();
        } else {
            $values = array_diff_assoc(
                $change->getTargetState()->getValues(),
                $change->getSourceState()->getValues()
            );
        }
        if (!empty($values)) {
            $this->commandBuilder->addCommand(
                Command\ModifyEntityCommand::create(
                    $change->getTargetState()->getContext(),
                    $aggregateReference,
                    $values
                )
            );
        }
    }

    private function processRelations(Change $change)
    {
        /** @var PropertyReference[][] $sourceRelationsByProperty */
        $sourceRelationsByProperty = [];
        /** @var PropertyReference[][] $targetRelationsByProperty */
        $targetRelationsByProperty = $change->getTargetState()->getRelationsByProperty();

        if (!$change->isNew()) {
            $sourceRelationsByProperty = $change->getSourceState()->getRelationsByProperty();
        }

        $removedPropertyNames = array_diff(
            array_keys($sourceRelationsByProperty),
            array_keys($targetRelationsByProperty)
        );
        $addedPropertyNames = array_diff(
            array_keys($targetRelationsByProperty),
            array_keys($sourceRelationsByProperty)
        );

        // process all relations for properties that does
        // not exist in the target relations anymore
        foreach ($removedPropertyNames as $removedPropertyName) {
            $this->comparePropertyRelations(
                $change->getTargetState(),
                $sourceRelationsByProperty[$removedPropertyName],
                []
            );
        }
        // process all relations for properties that did not
        // not exist in the source relations before
        foreach ($addedPropertyNames as $addedPropertyName) {
            $this->comparePropertyRelations(
                $change->getTargetState(),
                [],
                $targetRelationsByProperty[$addedPropertyName]
            );
        }
        // process all relations for properties that did exist in source
        // relations before and still does exists in target relations
        foreach ($targetRelationsByProperty as $propertyName => $targetRelations) {
            if (
                in_array($propertyName, $removedPropertyNames)
                || in_array($propertyName, $addedPropertyNames)
            ) {
                continue;
            }

            $sourceRelations = $sourceRelationsByProperty[$propertyName];
            $this->comparePropertyRelations(
                $change->getTargetState(),
                $sourceRelations,
                $targetRelations
            );
        }
    }

    /**
     * @param SuggestedState $targetState
     * @param PropertyReference[] $sourceRelations
     * @param PropertyReference[] $targetRelations
     */
    private function comparePropertyRelations(
        SuggestedState $targetState,
        array $sourceRelations,
        array $targetRelations
    ) {
        $aggregateReference = $targetState->getSubject();

        $comparisonActions = SortingComparisonService::instance()->compare(
            $sourceRelations,
            $targetRelations
        );

        foreach ($comparisonActions as $comparisonAction) {
            if ($comparisonAction['action'] === SortingComparisonService::ACTION_REMOVE) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $this->commandBuilder->addCommand(
                    Command\RemoveRelationCommand::create(
                        $targetState->getContext(),
                        $aggregateReference,
                        $relationPropertyReference
                    )
                );
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ADD) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $this->commandBuilder->addCommand(
                    Command\AttachRelationCommand::create(
                        $targetState->getContext(),
                        $aggregateReference,
                        $relationPropertyReference
                    )
                );
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ORDER) {
                $relationSequence = RelationSequence::instance();
                /** @var PropertyReference $relationPropertyReference */
                foreach ($comparisonAction['items'] as $relationPropertyReference) {
                    $relationSequence->attach($relationPropertyReference);
                }
                $this->commandBuilder->addCommand(
                    Command\OrderRelationsCommand::create(
                        $targetState->getContext(),
                        $aggregateReference,
                        $relationSequence
                    )
                );
            }
        }
    }

    /**
     * @return bool
     * @todo Implement context switch
     */
    private function isDifferentContext(): bool
    {
        return false;
    }
}
