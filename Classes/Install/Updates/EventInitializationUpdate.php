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

use Doctrine\DBAL\Driver\Statement;
use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EventSourcingMap;
use TYPO3\CMS\DataHandling\Core\Service\ContextService;
use TYPO3\CMS\DataHandling\Install\Service\EventInitializationService;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Initializes events for existing records.
 */
class EventInitializationUpdate extends AbstractUpdate
{
    const INSTRUCTION_CREATE =
        EventInitializationService::INSTRUCTION_ENTITY
        | EventInitializationService::INSTRUCTION_VALUES;
    const INSTRUCTION_ACTION =
        EventInitializationService::INSTRUCTION_RELATIONS
        | EventInitializationService::INSTRUCTION_ACTIONS;

    /**
     * @return EventInitializationUpdate
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var string
     */
    protected $title = 'Initializes events for existing records';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $description = 'Initializes events for existing records';

        foreach ($this->getTableNames() as $tableName) {
            if (
                $this->countEmptyUuidColumns($tableName) > 0
                || $this->countEmptyRevisionColumns($tableName) > 0
            ) {
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
        $tableNames = $this->getTableNames();
        $recordTableNames = array_diff($tableNames, ['pages']);

        foreach ($tableNames as $tableName) {
            $this->assignEventSourcingValues($tableName, true);
        }

        $contextService = ContextService::instance();

        // remove existing local storage for workspaces
        foreach ($contextService->getWorkspaceIds() as $workspaceId) {
            ConnectionPool::instance()->reinitializeLocalStorage(
                Context::create($workspaceId)->asLocalStorageName()
            );
        }

        foreach ($contextService->getWorkspaceIds() as $workspaceId) {
            foreach ($contextService->getLanguageIds() as $languageId) {
                $context = Context::create($workspaceId, $languageId);
                $service = EventInitializationService::create($context);

                // first process all pages (nodes)
                $service
                    ->setInstruction(static::INSTRUCTION_CREATE)
                    ->process('pages');

                // then process all other records, just values, ignore relations
                foreach ($recordTableNames as $recordTableName) {
                    $service
                        ->setInstruction(static::INSTRUCTION_CREATE)
                        ->process($recordTableName);
                }

                // then process all records, just relations (ignore values)
                // and apply found actions (e.g. deletion in workspace context)
                foreach ($tableNames as $tableName) {
                    $service
                        ->setInstruction(static::INSTRUCTION_ACTION)
                        ->process($tableName);
                }
            }
        }

        return true;
    }

    /**
     * @param string $tableName
     * @param bool $force
     */
    private function assignEventSourcingValues(string $tableName, bool $force = false)
    {
        if (!$force && $this->countEmptyUuidColumns($tableName) === 0) {
            return;
        }

        $statement = $this->getEmptyUuidColumnsStatement($tableName, $force);
        while ($uid = $statement->fetchColumn(0)) {
            $data = [
                Common::FIELD_UUID => Uuid::uuid4()->toString(),
                Common::FIELD_REVISION => null,
            ];
            ConnectionPool::instance()->getOriginConnection()
                ->update($tableName, $data, ['uid' => $uid]);
        }
    }

    /**
     * @param string $tableName
     * @param bool $force
     * @return Statement
     */
    private function getEmptyUuidColumnsStatement(string $tableName, bool $force = false)
    {
        $queryBuilder = $this->getQueryBuilder();

        if (!$force) {
            $queryBuilder->where(
                $queryBuilder->expr()->isNull(Common::FIELD_UUID)
            );
        }

        $statement = $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->execute();

        return $statement;
    }

    private function countEmptyUuidColumns(string $tableName): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $statement = $queryBuilder
            ->count('uid')
            ->from($tableName)
            ->where($queryBuilder->expr()->isNull(Common::FIELD_UUID))
            ->execute();
        $count = $statement->fetchColumn();
        return $count;
    }

    private function countEmptyRevisionColumns(string $tableName): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $statement = $queryBuilder
            ->from($tableName)
            ->count('uid')
            ->where($queryBuilder->expr()->isNull(Common::FIELD_REVISION))
            ->execute();
        $count = $statement->fetchColumn();
        return $count;
    }

    private function getQueryBuilder()
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }

    /**
     * @return string[]
     */
    private function getTableNames()
    {
        $tableNames = array_filter(
            array_keys($GLOBALS['TCA']),
            function(string $tableName) {
                return EventSourcingMap::provide()->shallRecord($tableName);
            }
        );
        return $tableNames;
    }
}
