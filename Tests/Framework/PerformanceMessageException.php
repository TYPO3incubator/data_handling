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
 * Work-around for passing messaged as test-result.
 */
class PerformanceMessageException extends \PHPUnit_Framework_RiskyTestError
{
    /**
     * @var array
     */
    protected $times = [];

    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->times;
    }

    /**
     * @param array $times
     */
    public function setTimes(array $times)
    {
        $this->times = $times;
    }
}
