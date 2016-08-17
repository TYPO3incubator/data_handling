<?php
namespace TYPO3\CMS\DataHandling\Tests\Unit\Core\EventSourcing\Store;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Tests\Framework\AssertionUtility;

class EventSelectorTest extends UnitTestCase
{
    /**
     * @param string $selector
     * @param array $expectations
     * @test
     * @dataProvider eventSelectorIsCreatedDataProvider
     */
    public function eventSelectorIsCreated(string $selector, array $expectations)
    {
        $eventSelector = EventSelector::create($selector);

        $this->assertTrue(
            AssertionUtility::matchesExpectations($expectations, $eventSelector),
            'Selector "' . $selector . '" did not match expectations'
        );
    }

    /**
     * @return array
     */
    public function eventSelectorIsCreatedDataProvider(): array
    {
        return [
            'stream name' => [
                '$stream',
                [
                    'streamName' => 'stream',
                    'categories' => [],
                ]
            ],
            'stream name with empty category' => [
                '$stream[]',
                [
                    'streamName' => 'stream',
                    'categories' => [],
                ]
            ],
            'one category' => [
                '[category]',
                [
                    'streamName' => '',
                    'categories' => ['category'],
                ]
            ],
            'many categories' => [
                '[first, second]',
                [
                    'streamName' => '',
                    'categories' => ['first', 'second'],
                ]
            ],
            'stream name with one category' => [
                '$stream[category]',
                [
                    'streamName' => 'stream',
                    'categories' => ['category'],
                ]
            ],
            'stream name with many categories' => [
                '$stream[first, second]',
                [
                    'streamName' => 'stream',
                    'categories' => ['first', 'second'],
                ]
            ],
        ];
    }

    /**
     * @param string $selector
     * @test
     * @dataProvider invalidEventSelectorIsDeterminedDataProvider
     * @expectedException \RuntimeException
     */
    public function invalidEventSelectorIsDetermined(string $selector)
    {
        EventSelector::create($selector);
    }

    /**
     * @return array
     */
    public function invalidEventSelectorIsDeterminedDataProvider()
    {
        return [
            'noting' => [
                '',
            ],
            'without stream prefix' => [
                'stream',
            ],
            'empty category' => [
                '[]',
            ],
            'empty category with comma' => [
                '[,]',
            ],
        ];
    }
}