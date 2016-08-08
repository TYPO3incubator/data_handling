<?php
namespace TYPO3\CMS\DataHandling\Tests\Unit\Core\Service;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Record\Reference;
use TYPO3\CMS\DataHandling\Core\Service\SortingComparisonService;

class SortingComparisonServiceTest extends UnitTestCase
{
    /**
     * @var SortingComparisonService
     */
    protected $subject;

    protected function setup()
    {
        parent::setUp();
        $this->subject = SortingComparisonService::instance();
    }

    protected function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @param array $source
     * @param array $target
     * @param array $expectedActions
     * @test
     * @dataProvider sortingActionsAreResolvedDataProvider
     */
    public function sortingActionsAreResolved(array $source, array $target, array $expectedActions)
    {
        $actions = $this->subject->compare($source, $target);
        $this->assertEquals($expectedActions, $actions);
    }

    public function sortingActionsAreResolvedDataProvider(): array
    {
        return [
            'empty source, empty target' => [
                [],
                [],
                []
            ],
            'same source & same target' => [
                ['A', 'B'],
                ['A', 'B'],
                []
            ],
            'filled source, empty target' => [
                ['A', 'B'],
                [],
                [
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'A'],
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'B'],
                ]
            ],
            'empty source, filled target' => [
                [],
                ['A', 'B'],
                [
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'A'],
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'B'],
                ]
            ],
            'same items re-ordering' => [
                ['A', 'B', 'C'],
                ['C', 'A', 'B'],
                [
                    ['action' => SortingComparisonService::ACTION_ORDER, 'items' => ['C', 'A', 'B']],
                ]
            ],
            'removing source, add target' => [
                ['A', 'B'],
                ['C', 'D'],
                [
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'A'],
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'B'],
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'C'],
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'D'],
                ]
            ],
            'adding item to bottom' => [
                ['A', 'B', 'C', 'D'],
                ['A', 'B', 'C', 'D', 'E'],
                [
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'E'],
                ]
            ],
            'adding item to top' => [
                ['A', 'B', 'C', 'D'],
                ['E', 'A', 'B', 'C', 'D'],
                [
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'E'],
                    ['action' => SortingComparisonService::ACTION_ORDER, 'items' => ['E', 'A', 'B', 'C', 'D']],
                ]
            ],
            'adding item between' => [
                ['A', 'B', 'C', 'D'],
                ['A', 'B', 'E', 'C', 'D'],
                [
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'E'],
                    ['action' => SortingComparisonService::ACTION_ORDER, 'items' => ['A', 'B', 'E', 'C', 'D']],
                ]
            ],
            'removing item from bottom' => [
                ['A', 'B', 'C', 'D'],
                ['A', 'B', 'C'],
                [
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'D'],
                ]
            ],
            'removing item from top' => [
                ['A', 'B', 'C', 'D'],
                ['B', 'C', 'D'],
                [
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'A'],
                ]
            ],
            'removing item between' => [
                ['A', 'B', 'C', 'D'],
                ['A', 'B', 'D'],
                [
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'C'],
                ]
            ],
            'removing, adding, re-sorting' => [
                ['A', 'B', 'C', 'D'],
                ['A', 'E', 'D', 'C'],
                [
                    ['action' => SortingComparisonService::ACTION_REMOVE, 'item' => 'B'],
                    ['action' => SortingComparisonService::ACTION_ADD, 'item' => 'E'],
                    ['action' => SortingComparisonService::ACTION_ORDER, 'items' => ['A', 'E', 'D', 'C']],
                ]
            ],
        ];
    }
}