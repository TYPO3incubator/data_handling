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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;

abstract class State
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var EntityReference
     */
    protected $node;

    /**
     * @var EntityReference
     */
    protected $subject;

    /**
     * @var Position
     */
    protected $position;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var PropertyReference[]
     */
    protected $relations = [];

    protected function __construct()
    {
        $this->context = Context::create();
        $this->node = EntityReference::instance();
        $this->subject = EntityReference::instance();
        $this->position = Position::createBottom();
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Context $context
     * @return static
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
        return $this;
    }

    public function getNode(): EntityReference
    {
        return $this->node;
    }

    /**
     * @param EntityReference $node
     * @return static
     */
    public function setNode(EntityReference $node)
    {
        $this->node = $node;
        return $this;
    }

    /**
     * @return EntityReference
     */
    public function getSubject(): EntityReference
    {
        return $this->subject;
    }

    /**
     * @param EntityReference $subject
     * @return static
     */
    public function setSubject(EntityReference $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return Position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param Position $position
     * @return static
     */
    public function setPosition(Position $position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     * @return static
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasValue(string $propertyName): bool
    {
        // consider null values
        return array_key_exists($propertyName, $this->values);
    }

    /**
     * @param string $propertyName
     * @return int|string|null
     */
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
     * @return static
     */
    public function setRelations(array $relations)
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
