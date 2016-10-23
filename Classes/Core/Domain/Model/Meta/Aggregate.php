<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Meta;

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

use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver\AggregateResolver;

class Aggregate
{
    /**
     * @return Aggregate
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var AggregateResolver
     */
    protected $aggregateResolver;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Aggregate[]
     */
    protected $nestedAggregates = [];

    public function setAggregateResolver(AggregateResolver $aggregateResolver): Aggregate
    {
        $this->aggregateResolver = $aggregateResolver;
        return $this;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(State $state): Aggregate
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return Aggregate[]
     */
    public function getNestedAggregates(): array
    {
        return $this->nestedAggregates;
    }

    /**
     * @return Aggregate[]
     */
    public function getDeepNestedAggregates(): array
    {
        $deepNestedAggregates = [];
        foreach ($this->getNestedAggregates() as $nestedAggregate) {
            $deepNestedAggregates[] = $nestedAggregate;
            $deepNestedAggregates = $deepNestedAggregates + $nestedAggregate->getDeepNestedAggregates();
        }
        return $deepNestedAggregates;
    }

    public function addNestedAggregate(Aggregate $nestedAggregate): Aggregate
    {
        $this->nestedAggregates[] = $nestedAggregate;
        return $this;
    }
}
