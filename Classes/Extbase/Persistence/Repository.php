<?php
namespace TYPO3\CMS\DataHandling\Extbase\Persistence;

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

use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;

abstract class Repository extends \TYPO3\CMS\Extbase\Persistence\Repository implements ProjectionRepository
{
    /**
     * @param AbstractProjectableEntity $subject
     * @return AbstractProjectableEntity
     */
    public function makeProjectable(AbstractProjectableEntity $subject)
    {
        $projection = $this->findByUuid($subject->getUuidInterface());
        if ($projection !== null) {
            $subject->_setProperty('uid', $projection->getUid());
            $subject->setPid($projection->getPid());
        }
        return $subject;
    }
}
