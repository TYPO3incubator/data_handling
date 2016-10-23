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

class Schema
{
    /**
     * @return Schema
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Property[]
     */
    protected $properties = [];

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

    /**
     * @param string $propertyName
     * @return null|Property
     */
    public function getProperty(string $propertyName)
    {
        return ($this->properties[$propertyName] ?? null);
    }
}
