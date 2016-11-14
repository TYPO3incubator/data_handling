<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Projection;

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

use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\PageContext;
use TYPO3\CMS\EventSourcing\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\TcaCommand\TcaCommandManager;
use TYPO3\CMS\EventSourcing\Core\Service\MetaModelService;

final class TcaProjectionService
{
    public static function addCreateFieldValues(string $tableName, array $data)
    {
        $metaModelService = MetaModelService::instance();

        $pageId = PageContext::provide()->getPageId();
        if (!empty($pageId)) {
            $data['pid'] = $pageId;
        }

        $creationDateFieldName = $metaModelService->getCreationDateFieldName($tableName);
        if ($creationDateFieldName !== null) {
            $data[$creationDateFieldName] = $GLOBALS['EXEC_TIME'];
        }

        $data = static::addUpdateFieldValues($tableName, $data);

        return $data;
    }

    public static function addUpdateFieldValues(string $tableName, array $data)
    {
        $metaModelService = MetaModelService::instance();

        $timestampFieldName = $metaModelService->getTimestampFieldName($tableName);
        if ($timestampFieldName !== null) {
            $data[$timestampFieldName] = $GLOBALS['EXEC_TIME'];
        }

        return $data;
    }

    /**
     * @param UuidInterface $aggregateId
     * @param array $data
     * @return array
     */
    public static function addAggregateId(UuidInterface $aggregateId = null, array $data)
    {
        if ($aggregateId !== null) {
            $data[Common::FIELD_UUID] = $aggregateId->toString();
        }
        return $data;
    }

    /**
     * @param string $tableName
     * @param UuidInterface $uuid
     * @return array|bool
     */
    public static function findByUuid(string $tableName, UuidInterface $uuid)
    {
        $queryBuilder = ConnectionPool::instance()
            ->getOriginConnection()
            ->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    Common::FIELD_UUID,
                    $queryBuilder->createNamedParameter($uuid->toString())
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();
    }

    /**
     * @param string $tableName
     * @param array $data
     * @return array
     */
    public static function mapFieldNames(string $tableName, array $data)
    {
        if (!TcaCommandManager::provide()->has($tableName)) {
            throw new \RuntimeException('Table name ' . $tableName . 'not registered');
        }

        $substitution = [];
        $mapping = TcaCommandManager::provide()->for($tableName)->getMapping();
        $mapping[Common::FIELD_UUID] = true;

        foreach ($mapping as $tcaFieldName => $propertyName) {
            if (is_string($propertyName)) {
                $substitution[$propertyName] = $tcaFieldName;
            }
        }
        $allowedFieldNames = array_merge(
            array_keys($mapping),
            array_keys($substitution)
        );

        foreach ($data as $fieldName => $fieldValue) {
            if (!in_array($fieldName, $allowedFieldNames)) {
                unset($data[$fieldName]);
                continue;
            }
            if (is_bool($mapping[$fieldName])) {
                continue;
            }
            if (!isset($substitution[$fieldName])) {
                continue;
            }
            $substitutedFieldName = $substitution[$fieldName];
            $data[$substitutedFieldName] = $fieldValue;
            unset($data[$fieldName]);
        }

        return $data;
    }
}
