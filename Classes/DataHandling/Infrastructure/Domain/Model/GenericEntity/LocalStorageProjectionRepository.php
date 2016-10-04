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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;

class LocalStorageProjectionRepository extends AbstractProjectionRepository
{
    /**
     * @param Connection $connection
     * @param string $tableName
     * @return LocalStorageProjectionRepository
     */
    public static function create(Connection $connection, string $tableName)
    {
        return new static($connection, $tableName);
    }

    /**
     * @param Connection $connection
     * @param string $tableName
     */
    private function __construct(Connection $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sanitizeData(array $data)
    {
        $data = parent::sanitizeData($data);

        if (isset($data['t3ver_wsid'])) {
            unset($data['t3ver_wsid']);
        }

        return $data;
    }
}
