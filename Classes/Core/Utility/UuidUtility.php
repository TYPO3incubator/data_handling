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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;

class UuidUtility
{
    /**
     * @param EntityReference $reference
     * @return string
     */
    public static function fetchUuid(EntityReference $reference): string
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select(Common::FIELD_UUID)
            ->from($reference->getName())
            ->where($queryBuilder->expr()->eq('uid', $reference->getUid()))
            ->execute();
        return $statement->fetchColumn();
    }
}
