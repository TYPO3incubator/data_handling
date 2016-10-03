<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\Database;

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
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;

class LegacyRelationHandler extends RelationHandler
{
    /**
     * @param Connection $connection
     * @return LegacyRelationHandler
     */
    public static function create(Connection $connection)
    {
        return new static($connection);
    }

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    private function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Disables generation of reference index.
     *
     * @param string $table
     * @param int $id
     * @return array
     * @todo Create dedicated projection
     */
    public function updateRefIndex($table, $id)
    {
        return [];
    }

    /**
     * @param string $tableName
     * @return Connection
     */
    protected function getConnectionForTableName(string $tableName)
    {
        return $this->connection;
    }
}
