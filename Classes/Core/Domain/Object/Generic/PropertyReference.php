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
use TYPO3\CMS\DataHandling\Core\Object\RepresentableAsString;

class PropertyReference implements RepresentableAsString
{
    /**
     * @return PropertyReference
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(PropertyReference::class);
    }

    /**
     * @var EntityReference
     */
    protected $entityReference;

    /**
     * @var string
     */
    protected $name;

    public function __toString(): string
    {
        return $this->entityReference->__toString() . ':' . $this->name;
    }

    public function getEntityReference(): EntityReference
    {
        return $this->entityReference;
    }

    public function setEntityReference(EntityReference $entityReference): PropertyReference
    {
        $this->entityReference = $entityReference;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): PropertyReference
    {
        $this->name = $name;
        return $this;
    }

    public function import(PropertyReference $reference): PropertyReference
    {
        if ($this->entityReference === null) {
            $this->entityReference = EntityReference::instance();
        }

        $this->entityReference->import($reference->getEntityReference());
        $this->name = $reference->getName();

        return $this;
    }
}
