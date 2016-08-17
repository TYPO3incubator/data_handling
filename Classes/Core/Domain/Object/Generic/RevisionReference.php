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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Object\RepresentableAsString;

class RevisionReference implements RepresentableAsString
{
    /**
     * @return RevisionReference
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RevisionReference::class);
    }

    /**
     * @param array $array
     * @return RevisionReference
     */
    public static function fromArray(array $array)
    {
        return static::instance()
            ->setRevision($array['revision'])
            ->setEntityReference(EntityReference::fromArray($array['entity']));
    }

    /**
     * @param string $name
     * @param array $record
     * @return RevisionReference
     */
    public static function fromRecord(string $name, array $record)
    {
        if (empty($record[Common::FIELD_REVISION])) {
            throw new \RuntimeException('Value for revision is required', 1471468749);
        }

        $reference = static::instance()
            ->setRevision($record[Common::FIELD_REVISION])
            ->setEntityReference(EntityReference::fromRecord($name, $record));

        return $reference;
    }

    /**
     * @var EntityReference
     */
    protected $entityReference;

    /**
     * @var int
     */
    protected $revision;

    public function __toString(): string
    {
        return $this->entityReference->__toString() . '@' . $this->revision;
    }

    public function __toArray(): array
    {
        return [
            'entity' => $this->entityReference->__toArray(),
            'revision' => $this->revision,
        ];
    }

    public function getEntityReference(): EntityReference
    {
        return $this->entityReference;
    }

    public function setEntityReference(EntityReference $entityReference): RevisionReference
    {
        $this->entityReference = $entityReference;
        return $this;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function setRevision(int $revision): RevisionReference
    {
        $this->revision = $revision;
        return $this;
    }

    public function import(RevisionReference $reference): RevisionReference
    {
        if ($this->entityReference === null) {
            $this->entityReference = EntityReference::instance();
        }

        $this->entityReference->import($reference->getEntityReference());
        $this->revision = $reference->getRevision();

        return $this;
    }
}
