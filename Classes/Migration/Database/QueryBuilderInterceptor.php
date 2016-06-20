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

class QueryBuilderInterceptor extends \TYPO3\CMS\Core\Database\Query\QueryBuilder
{
    public function execute()
    {
        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
            $from = $this->concreteQueryBuilder->getQueryPart('from');
            $values = $this->concreteQueryBuilder->getQueryPart('values');
            $tableName = $this->sanitizeTableName($from['table']);

            if (!EventEmitter::isSystemInternal($tableName)) {
                var_dump($tableName);
                EventEmitter::getInstance()->emitCreateEvent($tableName, $values);
            }
        }

        return parent::execute();
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function sanitizeTableName(string $tableName): string
    {
        return preg_replace('#^(`|\'|")([^`\'"]+)(\1)$#', '$2', $tableName);
    }
}
