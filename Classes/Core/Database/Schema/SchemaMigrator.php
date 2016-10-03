<?php
namespace TYPO3\CMS\DataHandling\Core\Database\Schema;

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

use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;

/**
 * Overrides core's SchemaMigrator to ensure to migrate to
 * origin connection instead of any SQLite LocalStorage.
 */
class SchemaMigrator extends \TYPO3\CMS\Core\Database\Schema\SchemaMigrator
{
    public function getUpdateSuggestions(array $statements, bool $remove = false): array
    {
        ConnectionPool::originAsDefault(true);
        $result = parent::getUpdateSuggestions($statements, $remove);
        ConnectionPool::originAsDefault(false);
        return $result;
    }

    public function getSchemaDiffs(array $statements): array
    {
        ConnectionPool::originAsDefault(true);
        $result = parent::getSchemaDiffs($statements);
        ConnectionPool::originAsDefault(false);
        return $result;
    }

    public function migrate(array $statements, array $selectedStatements): array
    {
        ConnectionPool::originAsDefault(true);
        $result = parent::migrate($statements, $selectedStatements);
        ConnectionPool::originAsDefault(false);
        return $result;
    }

    public function install(array $statements, bool $createOnly = false): array
    {
        ConnectionPool::originAsDefault(true);
        $result = parent::install($statements, $createOnly);
        ConnectionPool::originAsDefault(false);
        return $result;
    }

    public function importStaticData(array $statements, bool $truncate = false): array
    {
        ConnectionPool::originAsDefault(true);
        $result = parent::importStaticData($statements, $truncate);
        ConnectionPool::originAsDefault(false);
        return $result;
    }
}
