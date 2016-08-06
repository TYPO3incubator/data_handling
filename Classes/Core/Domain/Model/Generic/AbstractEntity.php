<?php
namespace TYPO3\CMS\DataHandling\Domain\Model\Generic;

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

use TYPO3\CMS\DataHandling\Domain\Object\Record\Reference;

abstract class AbstractEntity
{
    /**
     * @var Reference
     */
    protected $reference;

    /**
     * @var EntityData
     */
    protected $data;

    public function __construct()
    {
        $this->data = EntityData::instance();
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference(Reference $reference)
    {
        $this->reference = $reference;
    }
}
