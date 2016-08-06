<?php
namespace TYPO3\CMS\DataHandling\Core\MetaModel;

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
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class Property
{
    /**
     * @return Property
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Property::class);
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
     * @var array ActiveRelation[]
     */
    protected $activeRelations = [];

    /**
     * @var array PassiveRelation[]
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
    }

    public function getRelations(): array
    {
        return $this->activeRelations + $this->passiveRelations;
    }

    public function getActiveRelations(): array
    {
        return $this->activeRelations;
    }

    public function getPassiveRelations(): array
    {
        return $this->passiveRelations;
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
