<?php
namespace TYPO3\CMS\DataHandling\Core\Service;

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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;

class ContextService implements SingletonInterface
{
    /**
     * @return ContextService
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ContextService::class);
    }

    /**
     * @return int[]
     */
    public function getLanguageIds(): array
    {
        $languages = [0];
        $statement = $this->getQueryBuilder()
            ->select('uid')
            ->from('sys_language')
            ->execute();
        $languages = array_merge($languages, array_column($statement->fetchAll(), 'uid'));
        return $languages;
    }

    /**
     * @return int[]
     */
    public function getWorkspaceIds(): array
    {
        $workspaces = [0];

        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            $statement = $this->getQueryBuilder()
                ->select('uid')
                ->from('sys_workspace')
                ->execute();
            $workspaces = array_merge($workspaces, array_column($statement->fetchAll(), 'uid'));
        }

        return $workspaces;
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }
}
