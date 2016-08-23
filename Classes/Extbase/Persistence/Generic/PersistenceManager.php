<?php
namespace TYPO3\CMS\DataHandling\Extbase\Persistence\Generic;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\ProjectableEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PersistenceManager extends \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
{
    /**
     * @param AbstractEntity $object
     * @return bool
     */
    public function isNewObject($object)
    {
        if (!($object instanceof ProjectableEntity)) {
            return parent::isNewObject($object);
        }

        return $object->_isNew();
    }
}
