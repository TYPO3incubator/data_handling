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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;
use TYPO3\CMS\DataHandling\Core\EventSourcing\SourceManager;
use TYPO3\CMS\DataHandling\Core\Service\ContextService;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;
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
    static public function instance()
    {
        return GeneralUtility::makeInstance(EventInitializationUpdate::class);
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

        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            if (SourceManager::provide()->hasSourcedTableName($tableName)) {
                continue;
            }
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
        $allTableNames = array_filter(
            array_keys($GLOBALS['TCA']),
            function(string $tableName) {
                return (!GenericService::instance()->isSystemInternal($tableName));
            }
        );
        // filter out tables that are registered as source tables already
        $tableNames = array_diff($allTableNames, SourceManager::provide()->getSourcedTableNames());
        $recordTableNames = array_diff($tableNames, ['pages']);

        foreach ($allTableNames as $tableName) {
            $this->assignUuid($tableName);
        }

        $contextService = ContextService::instance();

        // remove existing local storage for workspaces
        foreach ($contextService->getWorkspaceIds() as $workspaceId) {
            ConnectionPool::instance()->purgeLocalStorage(
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
     */
    protected function assignUuid(string $tableName)
    {
        if ($this->countEmptyUuidColumns($tableName) === 0) {
            return;
        }

        while ($uid = $this->getEmptyUuidColumnsStatement($tableName)->fetchColumn(0)) {
            $data[Common::FIELD_UUID] = Uuid::uuid4()->toString();
            ConnectionPool::instance()->getOriginConnection()
                ->update($tableName, $data, ['uid' => $uid]);
        }
    }

    /**
     * @param string $tableName
     * @return Statement
     */
    protected function getEmptyUuidColumnsStatement(string $tableName)
    {
        $queryBuilder = $this->getQueryBuilder();
        $statement = $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where($queryBuilder->expr()->isNull(Common::FIELD_UUID))
            ->execute();
        return $statement;
    }

    protected function countEmptyUuidColumns(string $tableName): int
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

    protected function countEmptyRevisionColumns(string $tableName): int
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

    protected function getQueryBuilder()
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }
}
