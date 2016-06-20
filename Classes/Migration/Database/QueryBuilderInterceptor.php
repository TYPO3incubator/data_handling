<?php
namespace TYPO3\CMS\DataHandling\Migration\Database;

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

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueryBuilderInterceptor extends \TYPO3\CMS\Core\Database\Query\QueryBuilder
{
    public function execute()
    {
        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
            $from = $this->concreteQueryBuilder->getQueryPart('from');
            $tableName = $this->sanitizeReference($from['table']);

            if (!EventEmitter::isSystemInternal($tableName)) {
                $values = $this->determineValues();
                EventEmitter::getInstance()->emitCreateEvent($tableName, $values);
            }
        } elseif ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::UPDATE) {
            $from = $this->concreteQueryBuilder->getQueryPart('from');
            $where = $this->concreteQueryBuilder->getQueryPart('where');
            $tableName = $this->sanitizeReference($from['table']);

            if (!EventEmitter::isSystemInternal($tableName)) {
                if ($where instanceof CompositeExpression) {
                    $identifier = $this->determineIdentifier($tableName, $where);
                }
                if (!empty($identifier)) {
                    $values = $this->determineValues();
                    EventEmitter::getInstance()->emitChangedEvent($tableName, $values, $identifier);
                }
            }
        }

        return parent::execute();
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function sanitizeReference(string $tableName): string
    {
        return preg_replace('#^(`|\'|")([^`\'"]+)(\1)$#', '$2', $tableName);
    }

    /**
     * @param string $tableName
     * @param CompositeExpression $where
     * @return int|null
     */
    protected function determineIdentifier(string $tableName, CompositeExpression $where)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($where)
            ->execute()
            ->fetchAll();

        if (count($rows) !== 1 || empty($rows[0]['uid'])) {
            return null;
        }
        return (int)$rows[0]['uid'];
    }

    /**
     * @return array|null
     */
    protected function determineValues()
    {
        $values = $this->concreteQueryBuilder->getQueryPart('values');

        if (!empty($values)) {
            return $values;
        }

        $set = $this->concreteQueryBuilder->getQueryPart('set');

        if (!empty($set)) {
            $values = [];
            foreach ($set as $value) {
                if (preg_match('#^([^\s]+)\s*=\s*:(.+)$#', $value, $matches)) {
                    $values[$this->sanitizeReference($matches[1])]
                        = $this->concreteQueryBuilder->getParameter($matches[2]);
                }
            }
            return $values;
        }

        return null;
    }
}
