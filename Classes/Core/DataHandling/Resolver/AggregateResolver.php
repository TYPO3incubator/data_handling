<?php
namespace TYPO3\CMS\DataHandling\Core\DataHandling\Resolver;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\Aggregate;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\Change;
use TYPO3\CMS\DataHandling\Core\MetaModel\Map;
use TYPO3\CMS\DataHandling\Core\MetaModel\PassiveRelation;

class AggregateResolver
{
    /**
     * @param Change[] $subjects
     * @param null|mixed $proxy
     * @return AggregateResolver
     */
    public static function create(array $subjects, $proxy = null)
    {
        return GeneralUtility::makeInstance(AggregateResolver::class, $subjects, $proxy);
    }

    /**
     * AggregateResolver constructor.
     * @param Change[] $subjects
     * @param null|mixed $proxy
     */
    public function __construct(array $subjects, $proxy = null)
    {
        $this->subjects = $subjects;
        $this->proxy = $proxy;
        $this->build();
    }

    /**
     * @var Change[]
     */
    private $subjects;

    /**
     * @var mixed|null
     */
    private $proxy;

    /**
     * @var Aggregate[]
     */
    private $aggregates;

    /**
     * @var Aggregate[]
     */
    private $rootAggregates;

    /**
     * @return Aggregate[]
     */
    public function getRootAggregates()
    {
        return $this->rootAggregates;
    }

    /**
     * Get all changes for one root aggregate, thus it's change is the
     * first in the returned sequence.
     *
     * Useful if working with a top-down approach, e.g. on traversing
     * all (nested) child entities of a (relative) parent.
     *
     * @param Aggregate $rootAggregate
     * @return Change[]
     */
    public function getTopDownChanges(Aggregate $rootAggregate)
    {
        $sequence[] = $this->resolveSubject($rootAggregate);
        foreach ($rootAggregate->getDeepNestedAggregates() as $nestedAggregate) {
            $subject = $this->resolveSubject($nestedAggregate);
            if (!in_array($subject, $sequence)) {
                $sequence[] = $subject;
            }
        }
        return $sequence;
    }

    /**
     * Get all changes for one root aggregate in reverse order, thus it's
     * change thus is last in the returned sequence.
     *
     * Useful if working with a bottom-up approach, e.g. on actually creating
     * new child entities which can afterwards be referenced from an accordant
     * parent entity (entities need to exist for references, which would not be
     * the case in a top-down approach).
     *
     * @param Aggregate $rootAggregate
     * @return Change[]
     */
    public function getBottomUpChanges(Aggregate $rootAggregate)
    {
        return array_reverse($this->getTopDownChanges($rootAggregate));
    }

    /**
     * @param Aggregate $aggregate
     * @return Aggregate[]
     */
    private function resolveRootAggregates(Aggregate $aggregate): array
    {
        /** @var Aggregate[] $activeAggregates */
        $rootAggregates = [];
        /** @var Aggregate[] $activeAggregates */
        $activeAggregates = [];

        $metaModelProperties = Map::instance()->getSchema($aggregate->getState()->getSubject()->getName());

        foreach ($metaModelProperties->getProperties() as $metaModelProperty) {
            $passiveRelations = $metaModelProperty->getPassiveRelations();
            if (empty($passiveRelations)) {
                continue;
            }

            $stateValue = $aggregate->getState()->getValue($metaModelProperty->getName());
            $stateRelations = $aggregate->getState()->getPropertyRelations($metaModelProperty->getName());

            // e.g. inline-child having foreign_field value defined
            // -> fetch originator by using value (uid/uuid) as parent entity selector
            // -> most probably fetch originator from database
            if ($stateValue !== null) {
                // @todo Implement look-up
            // e.g. inline-child using foreign_field as e.g. select type
            // -> fetch originator by using reference as parent entity selector
            // -> most probably fetch originator from database
            } elseif (count($stateRelations) > 0) {
                // @todo Implement look-up
            // otherwise check root AggregateResolver for reference pointing to us
            } else {
                foreach ($passiveRelations as $passiveRelation) {
                    $activeAggregates = $activeAggregates + $this->findActiveRelationAggregates($passiveRelation, $aggregate);
                }
            }
        }

        foreach ($activeAggregates as $activeAggregate) {
            $activeAggregate->addNestedAggregate($aggregate);
            $rootAggregates = array_merge(
                $rootAggregates,
                $this->resolveRootAggregates($activeAggregate)
            );
        }

        if (empty($rootAggregates)) {
            $rootAggregates[] = $aggregate;
        }

        return $rootAggregates;
    }

    private function build()
    {
        $this->aggregates = [];
        $this->rootAggregates = [];

        foreach ($this->subjects as $change) {
            // @todo Proxy
            $this->aggregates[] = Aggregate::instance()->setState($change->getTargetState());
        }

        foreach ($this->aggregates as $aggregate) {
            foreach ($this->resolveRootAggregates($aggregate) as $rootAggregate) {
                if (!in_array($rootAggregate, $this->rootAggregates, true)) {
                    $this->rootAggregates[] = $rootAggregate;
                }
            }
        }
    }

    /**
     * @param Aggregate $aggregate
     * @return Change
     */
    private function resolveSubject(Aggregate $aggregate): Change
    {
        foreach ($this->subjects as $subject) {
            // @todo Proxy
            if ($subject->getTargetState() !== $aggregate->getState()) {
                continue;
            }
            return $subject;
        }
        throw new \RuntimeException('Subject cannot be resolved', 1470672893);
    }

    /**
     * @param PassiveRelation $passiveRelation
     * @param Aggregate $needle
     * @return Aggregate[]
     */
    private function findActiveRelationAggregates(PassiveRelation $passiveRelation, Aggregate $needle): array
    {
        $activeAggregates = [];
        $activeProperty = $passiveRelation->getFrom();

        foreach ($this->aggregates as $aggregate) {
            $state = $aggregate->getState();
            // skip aggregates that are do not issue the requested passive relation
            if ($state->getSubject()->getName() !== $activeProperty->getSchema()->getName()) {
                continue;
            }

            $aggregateRelations = $state->getPropertyRelations($activeProperty->getName());
            foreach ($aggregateRelations as $aggregateRelation) {
                // skip aggregate relations that do not issue the requested passive relation
                if (!$aggregateRelation->getEntityReference()->equals($needle->getState()->getSubject())) {
                    continue;
                }
                $activeAggregates[] = $aggregate;
            }
        }

        return $activeAggregates;
    }
}