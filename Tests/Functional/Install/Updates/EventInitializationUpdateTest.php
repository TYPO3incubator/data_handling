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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic;
use TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\GenericStream;
use TYPO3\CMS\DataHandling\Core\EventSourcing\StreamManager;
use TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate;
use TYPO3\CMS\DataHandling\Install\Service\EventInitializationService;
use TYPO3\CMS\DataHandling\Tests\Framework\AssertionUtility;

/**
 * Tests initialization of events for existing records.
 */
class EventInitializationUpdateTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/data-handling'
    ];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

    /**
     * @var EventInitializationUpdate
     */
    protected $update;

    /**
     * @var GenericStream
     */
    protected $stream;

    /**
     * @var array
     */
    protected $expectedEvents;

    protected function setUp()
    {
        parent::setUp();

        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->update = EventInitializationUpdate::instance();
        $this->stream = StreamManager::provide()->provideStream('generic', GenericStream::class);

        EventManager::provide()->bindStream($this->stream);
    }

    protected function tearDown()
    {
        unset($this->stream);
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

        $baseCreatedExpectation = [
            'uuid' => '@@UUID@@',
            'date' => '@@VALUE@@',
            'identity.uuid' => '@@UUID@@',
        ];
        $baseChangedExpectation = [
            'uuid' => '@@UUID@@',
            'date' => '@@VALUE@@',
            'subject.uuid' => '@@UUID@@',
        ];
        $metadataUpgradeKey = 'metadata.' . EventInitializationService::KEY_UPGRADE . '.uid';

        $this->expectedEvents = [
            Generic\CreatedEvent::class => [
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'pages',
                    $metadataUpgradeKey => 1,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'pages',
                    $metadataUpgradeKey => 88,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'pages',
                    $metadataUpgradeKey => 89,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'pages',
                    $metadataUpgradeKey => 90,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'tt_content',
                    $metadataUpgradeKey => 297,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'tt_content',
                    $metadataUpgradeKey => 298,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'tt_content',
                    $metadataUpgradeKey => 299,
                ]),
                array_merge($baseCreatedExpectation, [
                    'identity.name' => 'tt_content',
                    $metadataUpgradeKey => 300,
                ]),
            ],
            Generic\ChangedEvent::class => [
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'pages',
                    'data.title' => 'FunctionalTest',
                ]),
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'pages',
                    'data.title' => 'DataHandlerTest',
                ]),
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'pages',
                    'data.title' => 'Relations',
                ]),
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'pages',
                    'data.title' => 'Target',
                ]),
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'tt_content',
                    'data.header' => 'Regular Element #1',
                ]),
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'tt_content',
                    'data.header' => 'Regular Element #2',
                ]),
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'tt_content',
                    'data.header' => 'Regular Element #3',
                ]),
                // @todo Assert translation instead
                array_merge($baseChangedExpectation, [
                    'subject.name' => 'tt_content',
                    'data.header' => '[Translate to Dansk:] Regular Element #3',
                ]),
            ],
        ];

        $this->stream->subscribe(array($this, 'recordTablesAreUpdatedEventHandler'));
        $this->update->performUpdate($databaseQueries, $customMessages);

        $this->assertEquals(
            $this->provideComparableEmptyExpectations(),
            $this->expectedEvents, 'Not all expected events could be asserted'
        );
    }

    public function recordTablesAreUpdatedEventHandler(AbstractEvent $event)
    {
        $eventClassName = get_class($event);

        if (empty($this->expectedEvents[$eventClassName])) {
            return;
        }

        foreach ($this->expectedEvents[$eventClassName] as $index => $expectations) {
            if (AssertionUtility::matchesExpectations($expectations, $event)) {
                // purge found expectation
                $this->purgeEventExpectation($eventClassName, $index);
                // increment the amount of valid assertions
                $this->assertTrue(true);
            }
        }
    }

    protected function purgeEventExpectation(string $eventClassName, int $index)
    {
        if (!isset($this->expectedEvents[$eventClassName][$index])) {
            return;
        }

        unset($this->expectedEvents[$eventClassName][$index]);
        if (empty($this->expectedEvents[$eventClassName])) {
            unset($this->expectedEvents[$eventClassName]);
        }
    }

    protected function provideComparableEmptyExpectations(): array
    {
        $array = [];
        foreach ($this->expectedEvents as $eventClassName => $eventExpectations) {
            $array[$eventClassName] = [];
            foreach (array_keys($eventExpectations) as $index) {
                $array[$eventClassName][$index] = [];
            }
        }
        return $array;
    }
}
