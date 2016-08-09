<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object\Generic;

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

class State
{
    /**
     * @return State
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(State::class);
    }

    /**
     * @var EntityReference
     */
    protected $nodeReference;

    /**
     * @var EntityReference
     */
    protected $reference;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var PropertyReference[]
     */
    protected $relations = [];

    public function __construct()
    {
        $this->nodeReference = EntityReference::instance();
        $this->reference = EntityReference::instance();
    }

    public function getNodeReference(): EntityReference
    {
        return $this->nodeReference;
    }

    public function setNodeReference(EntityReference $nodeReference): State
    {
        $this->nodeReference = $nodeReference;
        return $this;
    }

    public function getReference(): EntityReference
    {
        return $this->reference;
    }

    public function setReference(EntityReference $reference): State
    {
        $this->reference = $reference;
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): State
    {
        $this->values = $values;
        return $this;
    }

    public function hasValue(string $propertyName): bool
    {
        // consider null values
        return array_key_exists($propertyName, $this->values);
    }

    public function getValue(string $propertyName)
    {
        return ($this->values[$propertyName] ?? null);
    }

    /**
     * @return PropertyReference[]
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param PropertyReference[] $relations
     * @return State
     */
    public function setRelations(array $relations): State
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * @return PropertyReference[][]
     */
    public function getRelationsByProperty(): array
    {
        $relationsByProperty = [];
        foreach ($this->relations as $relation) {
            $relationsByProperty[$relation->getName()][] = $relation;
        }
        return $relationsByProperty;
    }

    /**
     * @param string $propertyName
     * @return PropertyReference[]
     */
    public function getPropertyRelations(string $propertyName): array
    {
        $relations = [];
        foreach ($this->relations as $relation) {
            if ($relation->getName() === $propertyName) {
                $relations[] = $relation;
            }
        }
        return $relations;
    }
}
