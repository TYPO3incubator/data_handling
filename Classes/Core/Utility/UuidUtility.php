<?php
namespace TYPO3\CMS\DataHandling\Core\Utility;

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

use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;

class UuidUtility
{
    /**
     * @param EntityReference $reference
     * @return string
     */
    public static function fetchUuid(EntityReference $reference): string
    {
        // @todo Handle the special scenarios differently
        if ((string)$reference->getUid() === '0') {
            if ($reference->getName() === 'sys_language') {
                return EntityReference::DEFAULT_UUID;
            }
        }

        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select(Common::FIELD_UUID)
            ->from($reference->getName())
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $reference->getUid()
                )
            )
            ->execute();
        return $statement->fetchColumn();
    }

    /**
     * @param EntityReference $reference
     * @return int
     */
    public static function fetchUid(EntityReference $reference): int
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('uid')
            ->from($reference->getName())
            ->where(
                $queryBuilder->expr()->eq(
                    Common::FIELD_UUID,
                    $queryBuilder->createNamedParameter($reference->getUuid())
                )
            )
            ->execute();
        return (int)$statement->fetchColumn();
    }
}
