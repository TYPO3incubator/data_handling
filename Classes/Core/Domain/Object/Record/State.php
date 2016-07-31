<?php
namespace TYPO3\CMS\DataHandling\Domain\Object\Record;

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

class State
{
    /**
     * @return State
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(State::class);
    }

    /**
     * @var Reference
     */
    protected $nodeReference;

    /**
     * @var Reference
     */
    protected $reference;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var Reference[][]
     */
    protected $relations = [];

    public function __construct()
    {
        $this->nodeReference = Reference::instance();
        $this->reference = Reference::instance();
    }

    public function getNodeReference(): Reference
    {
        return $this->nodeReference;
    }

    public function setNodeReference(Reference $nodeReference)
    {
        $this->nodeReference = $nodeReference;
    }

    public function getReference(): Reference
    {
        return $this->reference;
    }

    public function setReference(Reference $reference)
    {
        $this->reference = $reference;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
    }

    public function hasValue(string $propertyName): bool
    {
        // consider null values
        return array_key_exists($propertyName, $this->values);
    }

    /**
     * @return Reference[][]
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    public function setRelations(array $relations)
    {
        $this->relations = $relations;
    }

    public function hasRelation(string $propertyName): bool
    {
        return isset($this->relations[$propertyName]);
    }
}
