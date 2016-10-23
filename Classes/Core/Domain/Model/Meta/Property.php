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

use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class Property
{
    /**
     * @return Property
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var ActiveRelation[]
     */
    protected $activeRelations = [];

    /**
     * @var PassiveRelation[]
     */
    protected $passiveRelations = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Property {
        $this->name = $name;
        return $this;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function setSchema(Schema $schema): Property
    {
        if ($this->schema !== null) {
            throw new \RuntimeException('Schema is already defined', 1470497240);
        }
        $this->schema = $schema;
        return $this;
    }

    /**
     * @return Relational|ActiveRelation[]|PassiveRelation[]
     */
    public function getRelations(): array
    {
        return $this->activeRelations + $this->passiveRelations;
    }

    /**
     * @return ActiveRelation[]
     */
    public function getActiveRelations(): array
    {
        return $this->activeRelations;
    }

    public function hasActiveRelationTo(string $toSchemaName): bool
    {
        foreach ($this->activeRelations as $activeRelation) {
            if ($activeRelation->getTo()->getName() === $toSchemaName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return PassiveRelation[]
     */
    public function getPassiveRelations(): array
    {
        return $this->passiveRelations;
    }

    public function hasPassiveRelationFrom(string $fromSchemaName, string $fromPropertyName): bool
    {
        foreach ($this->passiveRelations as $passiveRelation) {
            if (
                $passiveRelation->getFrom()->getSchema() === $fromSchemaName
                && $passiveRelation->getFrom()->getName() === $fromPropertyName
            ) {
                return true;
            }
        }
        return false;
    }

    public function addRelation($relation): Property
    {
        if ($relation instanceof ActiveRelation) {
            $relation->setProperty($this);
            $this->activeRelations[] = $relation;
        }
        if ($relation instanceof PassiveRelation) {
            $relation->setProperty($this);
            $this->passiveRelations[] = $relation;
        }
        return $this;
    }

    /*
     * MetaModelService invocation
     */

    public function isRelationProperty(): bool
    {
        return MetaModelService::instance()->isRelationProperty(
            $this->schema->getName(),
            $this->getName()
        );
    }

    public function getConfiguration(): array
    {
        return MetaModelService::instance()->getColumnConfiguration(
            $this->schema->getName(),
            $this->getName()
        );
    }
}
