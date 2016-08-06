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

class Schema
{
    /**
     * @return Schema
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Schema::class);
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Property[]
     */
    protected $properties;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Schema{
        $this->name = $name;
        return $this;
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(Property $property): Schema
    {
        if ($this->hasProperty($property->getName())) {
            throw new \RuntimeException('Property "' . $property->getName() . '" is already defined', 1470496497);
        }
        $this->properties[$property->getName()] = $property->setSchema($this);
        return $this;
    }

    public function hasProperty($propertyName): bool
    {
        return isset($this->properties[$propertyName]);
    }

    public function getProperty(string $propertyName): Property
    {
        return ($this->properties[$propertyName] ?? null);
    }
}
