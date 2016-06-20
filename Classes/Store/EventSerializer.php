<?php
namespace TYPO3\CMS\DataHandling\Store;

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

use TYPO3\CMS\DataHandling\Event\AbstractEvent;

class EventSerializer
{
    public function normalize(AbstractEvent $event): array
    {
        return [
            'type' => '',
            'source' => '',
            'timestamp' => new \DateTime(),
            'data' => [],
            'metadata' => [],
        ];
    }
}