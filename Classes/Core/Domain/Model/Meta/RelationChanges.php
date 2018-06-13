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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\RelationSequence;

class RelationChanges
{
    /**
     * @var PropertyReference[]
     */
    private $add = [];

    /**
     * @var PropertyReference[]
     */
    private $remove = [];

    /**
     * @var RelationSequence[]
     */
    private $order = [];

    public function isEmpty(): bool
    {
        return empty($this->add) && empty($this->remove) && empty($this->order);
    }

    public function add(PropertyReference $reference)
    {
        $this->add[] = $reference;
    }

    public function remove(PropertyReference $reference)
    {
        $this->remove[] = $reference;
    }

    public function order(RelationSequence $sequence)
    {
        $this->order[] = $sequence;
    }

    /**
     * @return PropertyReference[]
     */
    public function getAdd(): array
    {
        return $this->add;
    }

    /**
     * @return PropertyReference[]
     */
    public function getRemove(): array
    {
        return $this->remove;
    }

    /**
     * @return RelationSequence[]
     */
    public function getOrder(): array
    {
        return $this->order;
    }
}
