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

use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\RepresentableAsArray;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\RepresentableAsString;

class PropertyReference implements RepresentableAsString, RepresentableAsArray
{
    /**
     * @return PropertyReference
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param array $array
     * @return PropertyReference
     */
    public static function fromArray(array $array)
    {
        return static::instance()
            ->setName($array['name'])
            ->setEntityReference(EntityReference::fromArray($array['entity']));
    }

    /**
     * @var EntityReference
     */
    private $entityReference;

    /**
     * @var string
     */
    private $name;

    public function __toString(): string
    {
        return $this->entityReference->__toString() . '->' . $this->name;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'entity' => $this->entityReference->toArray(),
            'name' => $this->name
        ];
    }

    /**
     * @return EntityReference
     */
    public function getEntityReference(): EntityReference
    {
        return $this->entityReference;
    }

    /**
     * @param EntityReference $entityReference
     * @return static
     */
    public function setEntityReference(EntityReference $entityReference)
    {
        $this->entityReference = $entityReference;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param PropertyReference $reference
     * @return static
     */
    public function import(PropertyReference $reference)
    {
        if ($this->entityReference === null) {
            $this->entityReference = EntityReference::instance();
        }

        $this->entityReference->import($reference->getEntityReference());
        $this->name = $reference->getName();

        return $this;
    }

    /**
     * @param PropertyReference $reference
     * @return bool
     */
    public function equals(PropertyReference $reference): bool {
        return (
            $this->name === $reference->getName()
            && $this->entityReference->equals($reference->getEntityReference())
        );
    }
}
