<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object;

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

use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;

trait RelationalTrait
{
    /**
     * @var PropertyReference
     */
    protected $relation;

    /**
     * @return null|PropertyReference
     */
    public function getRelation()
    {
        return $this->relation;
    }

    public function setRelation(PropertyReference $relation)
    {
        $this->relation = $relation;
    }
}
