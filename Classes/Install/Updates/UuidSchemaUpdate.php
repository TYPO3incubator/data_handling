<?php
namespace TYPO3\CMS\DataHandling\Install\Updates;

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

use Rhumsaa\Uuid\Uuid;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Fills generated uuid columns in schema
 */
class UuidSchemaUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Fills generated uuid columns in schema';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $description = 'Fills generated uuid columns in schema';

        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            if ($this->countEmptyUuidColumns($tableName) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Performs the database update if old CTypes are available
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            if ($this->countEmptyUuidColumns($tableName) === 0) {
                continue;
            }

            $fetchQueryBuilder = $this->getQueryBuilderForTable($tableName);
            $fetchStatement = $fetchQueryBuilder
                ->select('uid')
                ->from($tableName)
                ->where($fetchQueryBuilder->expr()->eq('uuid', '""'))
                ->execute();


            foreach ($fetchStatement->fetchAll() as $row) {
                $updateQueryBuilder = $this->getQueryBuilderForTable($tableName);
                $updateQueryBuilder
                    ->update($tableName)
                    ->set('uuid', Uuid::uuid4())
                    ->where($updateQueryBuilder->expr()->eq('uid', $row['uid']))
                    ->execute();
                $databaseQueries[] = $updateQueryBuilder->getSQL();
            }
        }

        return true;
    }

    protected function countEmptyUuidColumns($tableName): int
    {
        $queryBuilder = $this->getQueryBuilderForTable($tableName);
        $statement = $queryBuilder
            ->from($tableName)
            ->count('uuid')
            ->where($queryBuilder->expr()->eq('uuid', '""'))
            ->execute();
        $count = $statement->fetchColumn(0);
        return $count;
    }

    protected function getQueryBuilderForTable($tableName)
    {
        $queryBuilder = ConnectionPool::create()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        /** @var DeletedRestriction $deletedRestriction */
        $deletedRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);
        if (MetaModelService::getInstance()->getDeletedFieldName($tableName) !== null) {
            $queryBuilder->getRestrictions()->add($deletedRestriction);
        }

        return $queryBuilder;
    }
}
