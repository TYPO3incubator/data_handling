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

use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;

trait IdentifiableTrait
{
    /**
     * Identity to be used for further processing as subject,
     * e.g. used for adding relations to a new entity that did
     * not have an existing subject before.
     *
     * @var EntityReference
     */
    protected $identity;

    /**
     * @return null|EntityReference
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    public function setIdentity(EntityReference $identity)
    {
        $this->identity = $identity;
    }
}
