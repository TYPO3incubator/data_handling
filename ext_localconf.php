<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\Query\QueryBuilder::class]['className']
    = \TYPO3\CMS\DataHandling\Migration\Database\QueryBuilderInterceptor::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class]['className']
    = \TYPO3\CMS\DataHandling\Migration\Database\ConnectionInterceptor::class;