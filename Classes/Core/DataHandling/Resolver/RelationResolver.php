<?php
namespace TYPO3\CMS\DataHandling\Core\DataHandling\Resolver;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\Database\LegacyRelationHandler;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class RelationResolver extends AbstractResolver
{
    /**
     * @param Connection $connection
     * @return static
     */
    public static function create(Connection $connection)
    {
        return new static($connection);
    }

    /**
     * @var Connection
     */
    private $connection;

    private function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param EntityReference $reference
     * @param array $rawValues
     * @return PropertyReference[]
     */
    public function resolve(EntityReference $reference, array $rawValues): array
    {
        $relations = [];

        foreach ($rawValues as $propertyName => $rawValue) {
            if (!MetaModelService::instance()->isRelationProperty($reference->getName(), $propertyName)) {
                continue;
            }

            $relationHandler = $this->createRelationHandler();
            $configuration = MetaModelService::instance()->getColumnConfiguration($reference->getName(), $propertyName);

            if (($configuration['config']['special'] ?? null) === 'languages') {
                $specialTableName = 'sys_language';
            }

            $type = $configuration['config']['type'];
            if ($type === 'group' && $configuration['config']['internal_type'] === 'db') {
                $tableNames = ($configuration['config']['allowed'] ?? '');
                $manyToManyTable = ($configuration['config']['MM'] ?? '');
                $itemValues = (empty($manyToManyTable) ? $rawValue : '');
            } elseif ($configuration['config']['type'] === 'select') {
                $tableNames = ($configuration['foreign_table'] ?? '');
                $manyToManyTable = ($configuration['config']['MM'] ?? '');
                $itemValues = (empty($manyToManyTable) ? $rawValue : '');
            } elseif ($configuration['config']['type'] === 'inline') {
                $tableNames = ($specialTableName ?? $configuration['foreign_table'] ?? '');
                $manyToManyTable = ($configuration['config']['MM'] ?? '');
                $foreignField = ($configuration['config']['foreign_field'] ?? '');
                $itemValues = (empty($manyToManyTable) && empty($foreignField) ? $rawValue : '');
            } else {
                throw new \RuntimeException('Unknown TCA relation configuration "' . $type . '"', 1470001541);
            }

            $relationHandler->start(
                $itemValues,
                $tableNames,
                $manyToManyTable,
                $reference->getUid(),
                $reference->getName(),
                $configuration['config']
            );

            foreach ($relationHandler->itemArray as $item) {
                $entityReference = EntityReference::instance()
                    ->setName($item['table'])
                    ->setUid($item['id']);
                $entityReference->setUuid(
                    $this->fetchUuid($entityReference)
                );
                $relations[] = PropertyReference::instance()
                    ->setEntityReference($entityReference)
                    ->setName($propertyName);
            }
        }

        return $relations;
    }

    /**
     * @return LegacyRelationHandler
     */
    protected function createRelationHandler()
    {
        $relationHandler = LegacyRelationHandler::create($this->connection);
        return $relationHandler;
    }
}
