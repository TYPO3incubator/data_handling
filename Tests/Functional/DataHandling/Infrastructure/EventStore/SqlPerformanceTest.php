<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\DataHandling\Infrastructure\EventStore;

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

use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Driver\SqlDriver;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\EventStore;
use TYPO3\CMS\DataHandling\Tests\Framework\PerformanceMessageException;
use TYPO3\CMS\DataHandling\Tests\Framework\PerformanceTest;
use TYPO3\CMS\DataHandling\Tests\Functional\DataHandling\Infrastructure\EventStore\Fixtures\EventFixture;

/**
 * Performance test on using event store SqlDriver
 */
class SqlPerformanceTest extends FunctionalTestCase implements PerformanceTest
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/data_handling'
    ];

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var string
     */
    private $streamName;

    /**
     * @var array
     */
    private $times = [];

    protected function setUp()
    {
        parent::setUp();
        $this->eventStore = EventStore::create(SqlDriver::instance());
        $this->streamName = 'SqlPerformanceTest/' . Uuid::uuid4()->toString();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->eventStore);
        unset($this->streamName);
        unset($this->times);
    }

    /**
     * @test
     */
    public function commitAndStreamEvents()
    {
        $value = 5000;

        $timeItem = [
            'name' => 'Write time',
            'start' => microtime(true),
        ];

        for ($i = 0; $i <= $value; $i++) {
            $event = EventFixture::create($i);
            $this->eventStore->attach($this->streamName, $event);
        }

        $timeItem['end'] = microtime(true);
        $this->times[] = $timeItem;

        $lastEvent = null;
        $eventStream = $this->eventStore->stream($this->streamName);

        $timeItem = [
            'name' => 'Read time',
            'start' => microtime(true),
        ];

        /** @var EventFixture $event */
        foreach ($eventStream as $event) {
            $lastEvent = $event;
        }

        $timeItem['end'] = microtime(true);
        $this->times[] = $timeItem;

        $this->assertNotNull($lastEvent);
        $this->assertSame($value, $lastEvent->getValue());

        $message = new PerformanceMessageException();
        $message->setTimes($this->times);
        throw $message;
    }
}
