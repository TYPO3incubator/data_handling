<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Projection;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;

interface Projection
{
    /**
     * @return string[]
     */
    public function listensTo();

    /**
     * @param BaseEvent $event
     * @return void
     */
    public function project(BaseEvent $event);
}
