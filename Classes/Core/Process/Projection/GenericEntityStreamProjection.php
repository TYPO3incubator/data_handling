<?php
namespace TYPO3\CMS\DataHandling\Core\Process\Projection;

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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\EventStream;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\StreamProjecting;

class GenericEntityStreamProjection extends AbstractGenericEntityProjection  implements StreamProjecting
{
    /**
     * @return GenericEntityStreamProjection
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(GenericEntityStreamProjection::class);
    }

    /**
     * @param EventStream $stream
     */
    public function project(EventStream $stream) {
        // TODO: Implement project() method.
    }
}
