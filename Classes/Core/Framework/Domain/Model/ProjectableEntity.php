<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Domain\Model;

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

use Ramsey\Uuid\UuidInterface;

interface ProjectableEntity
{
    /**
     * @param string $uuid
     */
    public function _setUuid(string $uuid);

    /**
     * @return string
     */
    public function getUuid();

    /**
     * @return UuidInterface
     */
    public function getUuidInterface();

    /**
     * @return int
     */
    public function getRevision();
}