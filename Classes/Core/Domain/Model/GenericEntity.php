<?php
namespace TYPO3\CMS\DataHandling\Domain\Model;

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

class GenericEntity
{
    public static function create()
    {
        return GeneralUtility::makeInstance(GenericEntity::class);
    }

    /**
     * @var string
     */
    protected $uid;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var array
     */
    protected $values;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var GenericEntity
     */
    protected $baseEntity;

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): GenericEntity
    {
        $this->uid = $uid;
        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): GenericEntity
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getValue(): array
    {
        return $this->values;
    }

    public function setValues(array $values): GenericEntity
    {
        $this->values = $values;
        return $this;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): GenericEntity
    {
        $this->entityName = $entityName;
        return $this;
    }

    public function setBaseEntity(GenericEntity $baseEntity): GenericEntity
    {
        $this->baseEntity = $baseEntity;
        return $this;
    }

    public function getBaseEntity(): GenericEntity
    {
        return $this->baseEntity;
    }
}
