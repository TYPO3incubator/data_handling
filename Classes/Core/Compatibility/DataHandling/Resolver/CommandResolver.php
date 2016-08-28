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
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\RelationSequence;
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
     * @var Meta\AbstractCommand[]
     */
    protected $commands;

    /**
     * @var bool
     */
    protected $bundle = false;

    public function setChange(Change $change)
    {
        $this->change = $change;
        return $this;
    }
    /**
     * @return Meta\AbstractCommand[]
     */
    public function resolve(): array
    {
        if (!isset($this->commands)) {
            $this->commands = [];
            $this->processContext();
            $this->processValues();
            $this->processRelations();
            $this->bundleCommands();
        }
        return $this->commands;
    }

    protected function bundleCommands()
    {
        if ($this->bundle && !empty($this->commands)) {
            $this->commands =[
                Meta\BundleEntityCommand::create($this->commands)
            ];
        }
    }

    protected function processContext()
    {
        $aggregateReference = $this->change->getTargetState()->getSubject();
        $targetStateContext = $this->change->getTargetState()->getContext();

        if ($this->change->isNew()) {
            $this->bundle = true;
            $this->collectCommand(
                Meta\CreateEntityCommand::create(
                    $aggregateReference->getName(),
                    $targetStateContext->getWorkspaceId(),
                    $targetStateContext->getLanguageId()
                )
            );
        } elseif ($this->isDifferentContext()) {
            $this->bundle = true;
            $this->collectCommand(
                Meta\BranchEntityCommand::create(
                    $aggregateReference,
                    $targetStateContext->getWorkspaceId()
                )
            );
        }

        // @todo TranslationCommand is missing here
        // @todo Maybe use CommandFactory or AimResolver
    }

    protected function processValues()
    {
        $aggregateReference = $this->change->getTargetState()->getSubject();

        if ($this->change->isNew()) {
            $values = $this->change->getTargetState()->getValues();
        } else {
            $values = array_diff_assoc(
                $this->change->getTargetState()->getValues(),
                $this->change->getSourceState()->getValues()
            );
        }
        if (!empty($values)) {
            $this->collectCommand(
                Meta\ChangeEntityCommand::create($aggregateReference, $values)
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
        $aggregateReference = $this->change->getTargetState()->getSubject();

        $comparisonActions = SortingComparisonService::instance()->compare(
            $sourceRelations,
            $targetRelations
        );

        foreach ($comparisonActions as $comparisonAction) {
            if ($comparisonAction['action'] === SortingComparisonService::ACTION_REMOVE) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $this->collectCommand(
                    Meta\RemoveRelationCommand::create(
                        $aggregateReference,
                        $relationPropertyReference
                    )
                );
            } elseif ($comparisonAction['action'] === SortingComparisonService::ACTION_ADD) {
                /** @var PropertyReference $relationPropertyReference */
                $relationPropertyReference = $comparisonAction['item'];
                $this->collectCommand(
                    Meta\AttachRelationCommand::create(
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
                $this->collectCommand(
                    Meta\OrderRelationsCommand::create(
                        $aggregateReference,
                        $relationSequence
                    )
                );
            }
        }
    }

    protected function collectCommand(Meta\AbstractCommand $command)
    {
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
