<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\PropertyReference;

class RelationSequence extends AbstractSequence
{
    /**
     * @return RelationSequence
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RelationSequence::class);
    }

    /**
     * @param array $array
     * @return RelationSequence
     */
    public static function fromArray(array $array)
    {
        $relation = static::instance();
        foreach ($array as $item) {
            $relation->attach(PropertyReference::fromArray($item));
        }
        return $relation;
    }

    /**
     * @var PropertyReference[]
     */
    protected $sequence = [];

    /**
     * @return array
     */
    public function __toArray(): array
    {
        $array = [];
        foreach ($this->sequence as $item) {
            $array[] = $item->__toArray();
        }
        return $array;
    }

    /**
     * @return PropertyReference[]
     */
    public function get(): array
    {
        return $this->sequence;
    }

    /**
     * @param PropertyReference $item
     * @return $this
     */
    public function attach($item)
    {
        if ($item instanceof PropertyReference) {
            $this->sequence[] = $item;
        }
        return $this;
    }
}
