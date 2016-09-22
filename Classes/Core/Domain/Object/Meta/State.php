<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;

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
     * @var array
     */
    protected $values = [];

    /**
     * @var EventReference
     */
    protected $branchedFrom;

    /**
     * @var EventReference
     */
    protected $translatedFrom;

    /**
     * @var PropertyReference[]
     */
    protected $relations = [];

    public function __construct()
    {
        $this->node = EntityReference::instance();
        $this->subject = EntityReference::instance();
        $this->context = Context::create();
    }

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
