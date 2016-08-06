<?php
namespace TYPO3\CMS\DataHandling\Domain\Model\Generic\Relation;

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
use TYPO3\CMS\DataHandling\Domain\Model\Generic\AbstractEntity;

class RelationCollection
{
    /**
     * @return RelationCollection
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RelationCollection::class);
    }

    /**
     * @var AbstractEntity[]
     */
    protected $relations = [];

    public function add(AbstractEntity $entity)
    {
        $this->relations[] = $entity;
    }

    public function remove(AbstractEntity $entity)
    {
        foreach ($this->relations as $index => $relation) {
            if ($entity === $relation) {
                unset($this->relations[$index]);
            }
        }
    }
}
