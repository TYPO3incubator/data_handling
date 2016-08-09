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

use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class RelationResolver extends AbstractResolver
{
    /**
     * @return RelationResolver
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RelationResolver::class);
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
            } elseif ($configuration['config']['type'] === 'inline') {
                $tableNames = ($configuration['foreign_table'] ?? '');
                $manyToManyTable = ($configuration['config']['MM'] ?? '');
                $itemValues = (empty($manyToManyTable) ? $rawValue : '');
            } elseif ($configuration['config']['type'] === 'select') {
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
                $relations[] = PropertyReference::instance()
                    ->setEntityReference($entityReference)
                    ->setName($propertyName);
            }
        }

        return $relations;
    }

    /**
     * @return RelationHandler
     */
    protected function createRelationHandler()
    {
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->setWorkspaceId(0);
        return $relationHandler;
    }
}
