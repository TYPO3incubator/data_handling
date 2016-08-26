<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Install\Updates;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta;
use TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate;

/**
 * Tests initialization of events for existing records.
 */
class EventInitializationUpdateTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'version',
        'workspaces',
    ];

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/data_handling'
    ];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/DataSet/';

    /**
     * @var EventInitializationUpdate
     */
    protected $update;

    protected function setUp()
    {
        parent::setUp();

        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->streamName = uniqid('test');
        $this->update = EventInitializationUpdate::instance();
    }

    protected function tearDown()
    {
        unset($this->update);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function recordTablesAreUpdated()
    {
        $databaseQueries = [];
        $customMessages = [];

        $this->update->performUpdate($databaseQueries, $customMessages);
    }
}
