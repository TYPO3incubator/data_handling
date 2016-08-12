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

use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Context;
use TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\GenericStream;
use TYPO3\CMS\DataHandling\Install\Service\EventInitializationService;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Initializes events for existing records.
 */
class EventInitializationUpdate extends AbstractUpdate
{
    const INSTRUCTION_CREATE =
        EventInitializationService::INSTRUCTION_ENTITY | EventInitializationService::INSTRUCTION_VALUES;
    const INSTRUCTION_RELATIONS =
        EventInitializationService::INSTRUCTION_RELATIONS;

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
            if ($this->countEmptyRevisionColumns($tableName) > 0) {
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
        EventManager::provide()->on(
            EventManager::LISTEN_BEFORE,
            array($this, 'handleCreatedEvent')
        );

        $tableNames = array_keys($GLOBALS['TCA']);
        $recordTableNames = array_diff($tableNames, ['pages']);

        foreach ($this->getWorkspaces() as $workspace) {
            foreach ($this->getLanguages() as $language) {
                $context = Context::instance()->setWorkspace($workspace)->setLanguage($language);
                $service = EventInitializationService::instance()->setContext($context);

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

                // then process all records, just relations, ignore values
                foreach ($tableNames as $tableName) {
                    $service
                        ->setInstruction(static::INSTRUCTION_RELATIONS)
                        ->process($tableName);
                }
            }
        }

        return true;
    }

    /**
     * @param Generic\AbstractEvent $event
     */
    public function handleCreatedEvent(Generic\AbstractEvent $event) {
        if (!($event instanceof Generic\CreatedEvent)) {
            return;
        }

        $metadata = $event->getMetadata();
        if (empty($metadata[EventInitializationService::KEY_UPGRADE]['uid'])) {
            throw new \RuntimeException('The uid value is required to process the event', 1470857564);
        }

        ConnectionPool::instance()->getOriginConnection()
            ->update(
                $event->getIdentity()->getName(),
                [
                    Common::FIELD_UUID => $event->getIdentity()->getUuid(),
                    Common::FIELD_REVISION => 0,
                ],
                [
                    'uid' => $metadata[EventInitializationService::KEY_UPGRADE]['uid'],
                ]
            );
    }

    protected function getLanguages(): array
    {
        $languages = [0];
        $statement = $this->getQueryBuilder()
            ->select('uid')
            ->from('sys_language')
            ->execute();
        $languages = $languages + array_column($statement->fetchAll(), 'uid');
        return $languages;
    }

    protected function getWorkspaces(): array
    {
        $workspaces = [0];

        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            $statement = $this->getQueryBuilder()
                ->select('uid')
                ->from('sys_language')
                ->execute();
            $workspaces = $workspaces + array_column($statement->fetchAll(), 'uid');
        }

        return $workspaces;
    }

    protected function countEmptyRevisionColumns($tableName): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $statement = $queryBuilder
            ->from($tableName)
            ->count(Common::FIELD_UUID)
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
