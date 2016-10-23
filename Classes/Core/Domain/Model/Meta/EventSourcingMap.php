<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Meta;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Providable;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class EventSourcingMap implements Providable
{
    /**
     * @var EventSourcingMap
     */
    private static $instance;

    /**
     * @param bool $force
     * @return EventSourcingMap
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(self::$instance) || !self::$instance->isCurrent()) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @var RelationMap
     */
    private $relationMap;

    /**
     * @var string[]
     */
    private $listenTableNames = [];

    /**
     * @var string[]
     */
    private $recordTableNames = [];

    /**
     * @var string[]
     */
    private $projectTableNames = [];

    private function __construct()
    {
        $this->relationMap = RelationMap::provide();
        $this->build();
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        return ($this->relationMap !== null && $this->relationMap->isCurrent());
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function shallListen(string $tableName)
    {
        return in_array($tableName, $this->listenTableNames);
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function shallRecord(string $tableName)
    {
        return in_array($tableName, $this->recordTableNames);
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function shallProject(string $tableName)
    {
        return in_array($tableName, $this->projectTableNames);
    }

    /**
     * Builds table names that shall record events and/or
     * be used for projections. MM intermediate-tables are
     * considered among their active relationship parents.
     */
    private function build()
    {
        $metaModelService = MetaModelService::instance();

        foreach ($this->relationMap->getSchemas() as $schema) {
            $tableName = $schema->getName();

            if (!$metaModelService->shallListenEvents($tableName)) {
                continue;
            }
            $this->listenTableNames[] = $tableName;

            if (!$metaModelService->shallRecordEvents($tableName)) {
                continue;
            }
            $this->recordTableNames[] = $tableName;

            if (!$metaModelService->shallProjectEvents($tableName)) {
                continue;
            }
            $this->projectTableNames[] = $tableName;

            foreach ($schema->getProperties() as $property) {
                // only consider active relations
                if (count($property->getActiveRelations()) === 0) {
                    continue;
                }
                $configuration = $metaModelService->getColumnConfiguration(
                    $tableName,
                    $property->getName()
                );
                // consider MM intermediate-tables for projection
                if (!empty($configuration['config']['MM'])) {
                    $this->projectTableNames[] = $configuration['config']['MM'];
                }
            }
        }
    }
}