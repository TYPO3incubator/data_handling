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

use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Framework\Object\RepresentableAsString;

class EntityReference implements RepresentableAsString
{
    /**
     * @return EntityReference
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EntityReference::class);
    }

    /**
     * @param array $array
     * @return EntityReference
     */
    public static function fromArray(array $array)
    {
        $reference = static::instance()
            ->setName($array['name'])
            ->setUuid($array['uuid']);
        if (isset($array['uid'])) {
            $reference->setUid($array['uid']);
        }
        return $reference;
    }

    /**
     * @param string $name
     * @param array $record
     * @return EntityReference
     */
    public static function fromRecord(string $name, array $record)
    {
        if (empty($record['uid']) && empty($record[Common::FIELD_UUID])) {
            throw new \RuntimeException('Both uid and uuid are required', 1470910287);
        }
        return static::instance()->setName($name)->setUid($record['uid'])->setUuid($record[Common::FIELD_UUID]);
    }

    /**
     * @param string $name
     * @return EntityReference
     */
    public static function create(string $name): EntityReference
    {
        return static::instance()->setName($name)->setUuid(Uuid::uuid4()->toString());
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
     * @var string
     */
    protected $name;

    public function __toString(): string
    {
        return $this->name . '/' . ($this->uuid ?? $this->uid ?? uniqid('none'));
    }

    public function __toArray(): array
    {
        return [
            'name' => $this->name,
            'uuid' => $this->uuid,
            'uid' => $this->uid,
        ];
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    public function setUid(string $uid): EntityReference
    {
        $this->uid = $uid;
        return $this;
    }

    public function unsetUid(): EntityReference
    {
        unset($this->uid);
        return $this;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): EntityReference
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function unsetUuid(): EntityReference
    {
        unset($this->uuid);
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): EntityReference
    {
        $this->name = $name;
        return $this;
    }

    public function import(EntityReference $reference): EntityReference
    {
        $this->uid = $reference->getUid();
        $this->uuid = $reference->getUuid();
        $this->name = $reference->getName();
        return $this;
    }

    /**
     * @param EntityReference $reference
     * @return bool
     */
    public function equals(EntityReference $reference): bool {
        return (
            $this->name === $reference->getName()
            && (
                $this->uuid === $reference->getUuid()
                || $this->uid = $reference->getUid()
            )
        );
    }
}
