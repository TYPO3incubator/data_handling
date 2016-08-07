<?php
namespace TYPO3\CMS\DataHandling\Domain\Object\Property;

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
use TYPO3\CMS\DataHandling\Domain\Object\Record;

class Reference
{
    /**
     * @return Reference
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Reference::class);
    }

    /**
     * @var Record\Reference
     */
    protected $entityReference;

    /**
     * @var string
     */
    protected $name;

    public function getEntityReference(): Record\Reference
    {
        return $this->entityReference;
    }

    public function setEntityReference(Record\Reference $entityReference): Reference
    {
        $this->entityReference = $entityReference;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Reference
    {
        $this->name = $name;
        return $this;
    }

    public function import(Reference $reference): Reference
    {
        if ($this->entityReference === null) {
            $this->entityReference = Record\Reference::instance();
        }

        $this->entityReference->import($reference->getEntityReference());
        $this->name = $reference->getName();

        return $this;
    }
}
