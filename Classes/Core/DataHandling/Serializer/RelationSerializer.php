<?php
namespace TYPO3\CMS\DataHandling\Core\DataHandling\Serializer;

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
use TYPO3\CMS\DataHandling\Core\Compatibility\Database\LegacyRelationHandler;
use TYPO3\CMS\DataHandling\Core\DataHandling\RelationHandlerBundle;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class RelationSerializer
{
    /**
     * @param Connection $connection
     * @return RelationSerializer
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
     * @param PropertyReference $relationReference
     * @param string $propertyValue
     * @return null|string
     */
    public function attachRelation(EntityReference $reference, PropertyReference $relationReference, $propertyValue = null)
    {
        $bundle = $this->buildRelationHandler(
            $reference,
            $relationReference->getName(),
            $propertyValue
        );

        $bundle->attach($relationReference->getEntityReference());
        return $bundle->commit();
    }

    /**
     * @param EntityReference $reference
     * @param PropertyReference $relationReference
     * @param string $propertyValue
     * @return null|string
     */
    public function removeRelation(EntityReference $reference, PropertyReference $relationReference, string $propertyValue)
    {
        $bundle = $this->buildRelationHandler(
            $reference,
            $relationReference->getName(),
            $propertyValue
        );

        $bundle->remove($relationReference->getEntityReference());
        return $bundle->commit();
    }

    /**
     * @param EntityReference $reference
     * @param RelationSequence $sequence
     * @param string $propertyValue
     * @return null|string
     */
    public function orderRelations(EntityReference $reference, RelationSequence $sequence, string $propertyValue)
    {
        if ($sequence->getName() === null) {
            return null;
        }

        $bundle = $this->buildRelationHandler(
            $reference,
            $sequence->getName(),
            $propertyValue
        );

        $bundle->order($sequence);
        return $bundle->commit();
    }

    /**
     * @param EntityReference $reference
     * @param string $propertyName
     * @return RelationHandlerBundle
     */
    private function buildRelationHandler(EntityReference $reference, string $propertyName, string $propertyValue)
    {
        if (!MetaModelService::instance()->isRelationProperty($reference->getName(), $propertyName)) {
            return null;
        }

        $identifier = $reference->getUid();
        if (empty($identifier)) {
            throw new \RuntimeException(
                'No entity identifier defined',
                1474476463
            );
        }

        $relationHandler = $this->createRelationHandler();
        $configuration = MetaModelService::instance()
            ->getColumnConfiguration($reference->getName(), $propertyName);

        if (($configuration['config']['special'] ?? null) === 'languages') {
            $specialTableName = 'sys_language';
        }

        $type = $configuration['config']['type'];
        if ($type === 'group' && $configuration['config']['internal_type'] === 'db') {
            $tableNames = ($configuration['config']['allowed'] ?? '');
            $manyToManyTable = ($configuration['config']['MM'] ?? '');
            $itemValues = (empty($manyToManyTable) ? $propertyValue : '');
            $prependTableNames = !empty($configuration['config']['prepend_tname']);
        } elseif ($configuration['config']['type'] === 'select') {
            $tableNames = ($configuration['foreign_table'] ?? '');
            $manyToManyTable = ($configuration['config']['MM'] ?? '');
            $itemValues = (empty($manyToManyTable) ? $propertyValue : '');
            $prependTableNames = false;
        } elseif ($configuration['config']['type'] === 'inline') {
            $tableNames = ($specialTableName ?? $configuration['foreign_table'] ?? '');
            $manyToManyTable = ($configuration['config']['MM'] ?? '');
            $foreignField = ($configuration['config']['foreign_field'] ?? '');
            $itemValues = (empty($manyToManyTable) && empty($foreignField) ? $propertyValue : '');
            $prependTableNames = false;
        } else {
            throw new \RuntimeException('Unknown TCA relation configuration "' . $type . '"', 1470001541);
        }

        if (!empty($manyToManyTable)) {
            $commitClosure =
                function(LegacyRelationHandler $relationHandler)
                use ($identifier, $manyToManyTable, $prependTableNames)
                {
                    $relationHandler->writeMM(
                        $manyToManyTable,
                        $identifier,
                        $prependTableNames
                    );
                };
        } elseif (!empty($foreignField)) {
            $commitClosure =
                function(LegacyRelationHandler $relationHandler)
                use ($identifier, $configuration)
                {
                    $relationHandler->writeForeignField(
                        $configuration['config'],
                        $identifier
                    );
                };
        } else {
            $commitClosure =
                function(LegacyRelationHandler $relationHandler)
                use ($prependTableNames)
                {
                    return implode(
                        ',',
                        $relationHandler->getValueArray($prependTableNames)
                    );
                };
        }

        $relationHandler->start(
            $itemValues,
            $tableNames,
            $manyToManyTable,
            $reference->getUid(),
            $reference->getName(),
            $configuration['config']
        );

        return RelationHandlerBundle::create(
            $relationHandler,
            $commitClosure
        );
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
