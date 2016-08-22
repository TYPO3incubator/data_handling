<?php
namespace TYPO3\CMS\DataHandling\Extbase\DomainObject;

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

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\ProjectableEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

abstract class AbstractProjectableEntity extends AbstractEntity implements ProjectableEntity
{
    /**
     * @return UuidInterface
     * @deprecated
     */
    protected static function createUuid()
    {
        return Uuid::uuid4();
    }

    /**
     * @var string
     * @todo Use real Uuid here, first rewrite Extbase's magic reflection
     */
    protected $uuid;

    /**
     * @var int
     */
    protected $revision;

    /**
     * @var AbstractEvent[]
     */
    protected $events = [];

    /**
     * @param string $uuid
     */
    public function _setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Increments the revision - anytime an event is applied.
     */
    public function _incrementRevision()
    {
        if ($this->revision === null) {
            $this->revision = 0;
        }
        $this->revision++;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return UuidInterface
     */
    public function getUuidInterface()
    {
        return Uuid::fromString($this->uuid);
    }

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @param AbstractProjectableEntity $entity
     * @deprecated
     */
    public function _mergeProperties(AbstractProjectableEntity $entity)
    {
        foreach ($this->_getProperties() as $propertyName => $sourcePropertyValue) {
            if (in_array($propertyName, ['uid', 'pid'])) {
                continue;
            }
            $targetPropertyValue = $entity->_getProperty($propertyName);
            if (empty($sourcePropertyValue) || empty($targetPropertyValue)) {
                $this->_setProperty($propertyName, $targetPropertyValue);
            } elseif ($sourcePropertyValue instanceof AbstractProjectableEntity && $targetPropertyValue instanceof AbstractProjectableEntity) {
                $sourcePropertyValue->_mergeProperties($targetPropertyValue);
            } elseif (
                (is_array($sourcePropertyValue) || $sourcePropertyValue instanceof \Traversable && $sourcePropertyValue instanceof \ArrayAccess)
                && (is_array($targetPropertyValue) || $targetPropertyValue instanceof \Traversable && $targetPropertyValue instanceof \ArrayAccess)
            ) {
                $this->_setProperty(
                    $propertyName, $this->mergeTraversable($sourcePropertyValue, $targetPropertyValue)
                );
            } else {
                $this->_setProperty($propertyName, $targetPropertyValue);
            }
        }
    }

    /**
     * @param array|\Traversable|\ArrayAccess $source
     * @param array|\Traversable|\ArrayAccess $target
     * @return array|\Traversable|\ArrayAccess
     * @deprecated
     */
    protected function mergeTraversable(\Traversable $source, \Traversable $target)
    {
        $collection = [];
        foreach ($target as $targetItem) {
            if (!($targetItem instanceof AbstractProjectableEntity)) {
                continue;
            }
            $sourceItem = $this->findInTraversable(
                $source,
                $targetItem
            );
            if ($sourceItem === null) {
                $collection[] = $targetItem;
            } else {
                $sourceItem->_mergeProperties($targetItem);
                $collection[] = $sourceItem;
            }
        }
        if ($source instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
            $source = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
            foreach ($collection as $item) {
                $source->attach($item);
            }
        } else {
            foreach ($source as $sourceIndex => $sourceItem) {
                if (isset($source[$sourceIndex])) {
                    unset($source[$sourceIndex]);
                }
                if (isset($source[$sourceItem])) {
                    unset($source[$sourceItem]);
                }
            }
            foreach ($collection as $item) {
                $source[] = $item;
            }
        }
        return $source;
    }

    /**
     * @param \Traversable $traversable
     * @param AbstractProjectableEntity $needle
     * @return null|AbstractProjectableEntity
     * @deprecated
     */
    protected function findInTraversable(\Traversable $traversable, AbstractProjectableEntity $needle)
    {
        foreach ($traversable as $item) {
            if (!($item instanceof AbstractProjectableEntity)) {
                continue;
            }
            if ($item->getUuid() === $needle->getUuid()) {
                return $item;
            }
        }
        return null;
    }
}
