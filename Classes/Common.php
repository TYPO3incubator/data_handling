<?php
namespace TYPO3\CMS\DataHandling;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Backend\Form\FormDataProvider\TcaCommandModifier;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Driver\GetEventStoreDriver;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Driver\SqlDriver;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\EventStore;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\EventStorePool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Command\CommandBus;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Projection\ProjectionManager;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class Common
{
    const FIELD_UUID = 't3uuid';
    const FIELD_REVISION = 't3rev';
    const STREAM_PREFIX_META = 'TYPO3/Meta3';
    const STREAM_PREFIX_META_ORIGIN = 'TYPO3/Meta3/Origin3';

    /**
     * @var bool
     * @internal
     * @todo Remove this development flag
     */
    protected static $enable = true;

    /**
     * @var bool
     * @internal
     * @todo Remove this development flag
     */
    protected static $local = true;

    /**
     * @return Dispatcher
     */
    public static function getSignalSlotDispatcher()
    {
        return GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * Overrides global configuration.
     */
    public static function overrideConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][TcaCommandModifier::class] = [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class,
            ]
        ];

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::ORIGIN_CONNECTION_NAME] =
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];

        if (!static::$enable) {
            return;
        }

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['wrapperClass'] =
            \TYPO3\CMS\DataHandling\Core\Compatibility\Database\ConnectionInterceptor::class;
    }

    /**
     * Defines XCLASSES & alternative implementations.
     *
     * @internal
     */
    public static function registerAlternativeImplementations()
    {
        if (!static::$enable) {
            return;
        }

        // provides alternative ConnectionPool to switch to LocalStorage
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\ConnectionPool::class]['className']
            = ConnectionPool::class;
        // intercepts $GLOBALS['TYPO3_DB']
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class]['className']
            = \TYPO3\CMS\DataHandling\Core\Compatibility\Database\DatabaseConnectionInterceptor::class;
        // provides information whether pages have workspace changes
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Workspaces\Service\WorkspaceService::class]['className']
            = \TYPO3\CMS\DataHandling\Workspaces\Service\WorkspaceService::class;

        if (!static::$local) {
            return;
        }

        // provides SchemaMigrator for origin connection (instead of any LocalStorage)
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class]['className']
            = \TYPO3\CMS\DataHandling\Core\Database\Schema\SchemaMigrator::class;
        // provides ProjectionContext, once workspace information is available
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class]['className']
            = \TYPO3\CMS\DataHandling\Core\Authentication\BackendUserAuthentication::class;
    }

    public static function registerUpdates()
    {
        if (!static::$enable) {
            return;
        }

        // create initial uuid and revision values
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate::class]
            = \TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate::class;
    }

    public static function registerHooks()
    {
        if (!static::$enable) {
            return;
        }

        // intercepts DataHandler
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['ac3c06f089776446875c4957a7f35a56']
            = DataHandling\Interceptor\Hook\Backend\DataHandlerHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['ac3c06f089776446875c4957a7f35a56']
            = DataHandling\Interceptor\Hook\Backend\DataHandlerHook::class;

        if (!static::$local) {
            return;
        }

        // frontend enforcement on LocalStorage
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']['ac3c06f089776446875c4957a7f35a56']
            = \TYPO3\CMS\DataHandling\DataHandling\Interceptor\Hook\Frontend\PostBackendUserAuthenticationHook::class . '->execute';
    }

    public static function registerSlots()
    {
        // provides new database fields
        \TYPO3\CMS\DataHandling\Common::getSignalSlotDispatcher()->connect(
            \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class, 'tablesDefinitionIsBeingBuilt',
            \TYPO3\CMS\DataHandling\DataHandling\Interceptor\Slot\Infrastructure\SchemaModificationSlot::class, 'generate'
        );

        if (!static::$enable) {
            return;
        }
    }

    public static function registerEventSources()
    {
        CommandBus::provide()->addHandlerBundle(
            Command\CommandHandlerBundle::instance(), [
                Command\CreateEntityBundleCommand::class,
                Command\BranchEntityBundleCommand::class,
                Command\BranchAndTranslateEntityBundleCommand::class,
                Command\TranslateEntityBundleCommand::class,
                Command\ModifyEntityBundleCommand::class,
                Command\DeleteEntityCommand::class,
                // @todo: enable, disable, move
            ]
        );

        ProjectionManager::provide()->registerProjections([
            new \TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Projection\GenericEntityProjection(),
            new \TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Projection\TableVersionProjection(),
        ]);

        // initialize default EventStore using SqlDriver
        EventStorePool::provide()
            ->enrolStore('sql')
            ->concerning('*')
            ->setStore(
                EventStore::create(
                    SqlDriver::instance()
                )
            );
//        EventStorePool::provide()
//            ->enrolStore('geteventstore.com')
//            ->concerning('*')
//            ->setStore(
//                EventStore::create(
//                    GetEventStoreDriver::create('http://127.0.0.1:2113', 'admin', 'changeit', true)
//                )
//            );
    }

    /**
     * @return Container
     */
    public static function getObjectContainer()
    {
        return GeneralUtility::makeInstance(Container::class);
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
