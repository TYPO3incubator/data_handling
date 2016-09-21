<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity;

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

use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;

class OriginProjectionRepository extends AbstractProjectionRepository
{
    /**
     * @param string $tableName
     * @return OriginProjectionRepository
     */
    public static function create(string $tableName)
    {
        return new static($tableName);
    }

    /**
     * @param string $tableName
     */
    private function __construct(string $tableName)
    {
        $this->connection = ConnectionPool::instance()->getOriginConnection();
        $this->tableName = $tableName;
    }
}
