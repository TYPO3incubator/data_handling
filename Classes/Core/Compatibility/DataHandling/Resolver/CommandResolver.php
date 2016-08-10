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
use TYPO3\CMS\DataHandling\Core\Domain\Command\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Identifiable;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Generic as GenericCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Service\SortingComparisonService;

class CommandResolver
{
    /**
     * @return CommandResolver
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CommandResolver::class);
    }

    /**
     * @var Change
     */
    protected $change;

    /**
     * @var null
     */
    protected $context;

    /**
     * @var AbstractCommand[]
     */
    protected $commands;

    public function setChange(Change $change): CommandResolver
    {
        $this->change = $change;
        return $this;
    }

    public function setContext($context = null): CommandResolver
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return AbstractCommand[]
     */
    public function resolve(): array
    {
        if (!isset($this->commands)) {
            $this->commands = [];
            $this->processContext();
            $this->processValues();
            $this->processRelations();
        }
        return $this->commands;
    }

    protected function processContext()
    {
        $reference = $this->change->getTargetState()->getReference();

        if ($this->change->isNew()) {
            $this->addCommand(
                GenericCommand\CreateCommand::instance($reference)
            );
        } elseif ($this->isDifferentContext()) {
            $this->addCommand(
                GenericCommand\BranchCommand::instance($reference)
            );
        }
    }

    protected function processValues()
    {
        $reference = $this->change->getTargetState()->getReference();

        if ($this->change->isNew()) {
            $values = $this->change->getTargetState()->getValues();
        } else {
            $values = array_diff_assoc(
                $this->change->getTargetState()->getValues(),
                $this->change->getSourceState()->getValues()
            );
        }
        if (!empty($values)) {
            $this->addCommand(
                GenericCommand\ChangeCommand::instance($reference, $values)
            );
        }
    }

    protected function processRelations()
    {
        /** @var PropertyReference[][] $sourceRelationsByProperty */
        $sourceRelationsByProperty = [];
        /** @var PropertyReference[][] $targetRelationsByProperty */
        $targetRelationsByProperty = $this->change->getTargetState()->getRelationsByProperty();

        if (!$this->change->isNew()) {
            $sourceRelationsByProperty = $this->change->getSourceState()->getRelationsByProperty();
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
            $this->comparePropertyRelations($sourceRelationsByProperty[$removedPropertyName], array());
        }
        // process all relations for properties that did not
        // not exist in the source relations before
        foreach ($addedPropertyNames as $addedPropertyName) {
            $this->comparePropertyRelations(array(), $targetRelationsByProperty[$addedPropertyName]);
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
            $this->comparePropertyRelations($targetRelations, $sourceRelations);
        }
    }

    /**
     * @param PropertyReference[] $sourceRelations
     * @param PropertyReference[] $targetRelations
     */
    protected function comparePropertyRelations(array $sourceRelations, array $targetRelations)
    {
        $reference = $this->change->getTargetState()->getReference();

        $comparisonActions = SortingComparisonService::instance()->compare(
            $sourceRelations,
            $targetRelations
        );

        foreach ($comparisonActions as $comparisonAction) {
            if ($comparisonAction['action'] === SortingComparisonService::ACTION_REMOVE) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $relationReference = $relationPropertyReference->getEntityReference();
                $this->addCommand(
                    GenericCommand\RemoveRelationCommand::instance($reference, [
                        'name' => $relationReference->getName(),
                        'uuid' => $relationReference->getUuid(),
                        'uid' => $relationReference->getUid(),
                    ])
                );
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ADD) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $relationReference = $relationPropertyReference->getEntityReference();
                $this->addCommand(
                    GenericCommand\AttachRelationCommand::instance($reference, [
                        'name' => $relationReference->getName(),
                        'uuid' => $relationReference->getUuid(),
                        'uid' => $relationReference->getUid(),
                    ])
                );
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ORDER) {
                $orderCommandData = [];
                /** @var PropertyReference $relationPropertyReference */
                foreach ($comparisonAction['items'] as $relationPropertyReference) {
                    $relationReference = $relationPropertyReference->getEntityReference();
                    $orderCommandData[] = [
                        'name' => $relationReference->getName(),
                        'uuid' => $relationReference->getUuid(),
                        'uid' => $relationReference->getUid(),
                    ];
                }
                $this->addCommand(
                    GenericCommand\OrderRelationsCommand::instance($reference, [
                        'order' => $orderCommandData,
                    ])
                );
            }
        }
    }

    protected function addCommand(AbstractCommand $command)
    {
        if ($command instanceof Identifiable) {
            // @todo Still think about, whether this is good - alternatively shift it to projection
            $this->change->getTargetState()->getReference()->setUuid(
                $command->getIdentity()->getUuid()
            );
        }
        $this->commands[] = $command;
    }

    /**
     * @return bool
     * @todo Implement context switch
     */
    protected function isDifferentContext(): bool
    {
        return false;
    }
}
