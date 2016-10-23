<?php
namespace TYPO3\CMS\DataHandling\Tests\Framework;

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

/**
 * Test-case listener to visualize performance measurement.
 */
class PerformanceListener extends \PHPUnit_Framework_BaseTestListener
{
    public function addRiskyTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        if (
            !($test instanceof PerformanceTest)
            || !($e instanceof PerformanceMessageException)
        ) {
            return;
        }

        $content = '';
        foreach ($e->getTimes() as $timeItem) {
            $content .= sprintf(
                "* %s: %.4fs\n",
                $timeItem['name'],
                $timeItem['end'] - $timeItem['start']
            );
        }

        if (!empty($content)) {
            printf(
                "\n%s:\n%s\n",
                'Performance measurements',
                $content
            );
        }
    }
}
