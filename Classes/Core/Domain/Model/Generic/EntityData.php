<?php
namespace TYPO3\CMS\DataHandling\Domain\Model\Generic;

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
use TYPO3\CMS\DataHandling\Domain\Model\Generic\Relation\Changeable;
use TYPO3\CMS\DataHandling\Domain\Model\Generic\Relation\RelationCollection;

/**
 * @deprecated Not required anymore
 */
class EntityData
{
    /**
     * @return EntityData
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EntityData::class);
    }

    /**
     * @var
     */
    protected $values = [];

    /**
     * @var AbstractEntity[]
     */
    protected $singleRelations = [];

    /**
     * @var RelationCollection[]
     */
    protected $multipleRelations = [];

    /**
     * @param string $propertyName
     * @return null|bool|int|float|string
     */
    public function getValue(string $propertyName)
    {
        if (!isset($this->values[$propertyName])) {
            return null;
        }

        return $this->values[$propertyName];
    }

    public function setValue(string $propertyName, $value)
    {
        if (!is_scalar($value) && $value === null) {
            throw new \RuntimeException('Values must be scalar or null "' . $propertyName . '"', 1469918451);
        }

        $this->values[$propertyName] = $value;
    }

    /**
     * @return null|Changeable
     */
    public function getSingleRelation(string $propertyName)
    {
        if (!isset($this->singleRelations[$propertyName])) {
            return null;
        }

        return $this->singleRelations[$propertyName];
    }

    public function setSingleRelation(string $propertyName, AbstractEntity $entity)
    {
        $this->singleRelations[$propertyName] = $entity;
    }

    public function unsetSingleRelation(string $propertyName, AbstractEntity $entity)
    {
        if (!isset($this->singleRelations[$propertyName])) {
            throw new \RuntimeException('Relation to be removed is not set in "' . $propertyName . '"', 1469918189);
        }
        if ($this->singleRelations[$propertyName] !== $entity) {
            throw new \RuntimeException('Relation to be unset is not the expected one in "' . $propertyName . '"', 1469918190);
        }

        unset($this->singleRelations[$propertyName]);
    }

    public function addMultipleRelation(string $propertyName, AbstractEntity $entity)
    {
        if (!isset($this->multipleRelations[$propertyName])) {
            $this->multipleRelations[$propertyName] = RelationCollection::instance();
        }

        $this->multipleRelations[$propertyName]->add($entity);
    }

    public function removeMultipleRelation(string $propertyName, AbstractEntity $entity)
    {
        if (!isset($this->multipleRelations[$propertyName])) {
            throw new \RuntimeException('Relation to be removed is not set in "' . $propertyName . '"', 1469918232);
        }

        $this->multipleRelations[$propertyName]->remove($entity);
    }
}
