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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\RelationChanges;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Service\SortingComparisonService;

class ChangeCommandResolver
{
    /**
     * @param Change[] $changes
     * @return ChangeCommandResolver
     */
    public static function create(array $changes)
    {
        return new static($changes);
    }

    /**
     * @param Change[] $changes
     */
    private function __construct(array $changes)
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
            $values = $this->resolveValues($change);
            $relationChanges = $this->resolveRelations($change);

            if (empty($values) && $relationChanges->isEmpty()) {
                continue;
            }

            $targetState = $change->getTargetState();

            if ($change->isNew()) {
                $this->commands[] = Command\NewEntityCommand::create(
                    $targetState->getContext(),
                    $targetState->getSubject(),
                    $targetState->getNode(),
                    $values,
                    $relationChanges
                );
            } else {
                $this->commands[] = Command\ChangeEntityCommand::create(
                    $targetState->getContext(),
                    $targetState->getSubject(),
                    $values,
                    $relationChanges
                );
            }
        }
    }

    private function resolveValues(Change $change): array
    {
        if ($change->isNew()) {
            $values = $change->getTargetState()->getValues();
        } else {
            $values = array_diff_assoc(
                $change->getTargetState()->getValues(),
                $change->getSourceState()->getValues()
            );
        }
        return $values;
    }

    private function resolveRelations(Change $change): RelationChanges
    {
        $relationChanges = new RelationChanges();

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
                $relationChanges,
                $sourceRelationsByProperty[$removedPropertyName],
                []
            );
        }
        // process all relations for properties that did not
        // not exist in the source relations before
        foreach ($addedPropertyNames as $addedPropertyName) {
            $this->comparePropertyRelations(
                $relationChanges,
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
                $relationChanges,
                $sourceRelations,
                $targetRelations
            );
        }

        return $relationChanges;
    }

    /**
     * @param RelationChanges $relationChanges
     * @param PropertyReference[] $sourceRelations
     * @param PropertyReference[] $targetRelations
     */
    private function comparePropertyRelations(
        RelationChanges $relationChanges,
        array $sourceRelations,
        array $targetRelations
    ) {
        $comparisonActions = SortingComparisonService::instance()->compare(
            $sourceRelations,
            $targetRelations
        );

        foreach ($comparisonActions as $comparisonAction) {
            if ($comparisonAction['action'] === SortingComparisonService::ACTION_REMOVE) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $relationChanges->remove($relationPropertyReference);
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ADD) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $relationChanges->add($relationPropertyReference);
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ORDER) {
                $relationSequence = RelationSequence::instance();
                /** @var PropertyReference $relationPropertyReference */
                foreach ($comparisonAction['items'] as $relationPropertyReference) {
                    $relationSequence->attach($relationPropertyReference);
                }
                $relationChanges->order($relationSequence);
            }
        }
    }
}
