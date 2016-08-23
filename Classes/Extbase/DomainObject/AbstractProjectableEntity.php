<?php
namespace TYPO3\CMS\DataHandling\Extbase\DomainObject;

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
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Domain\Model\ProjectableEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

abstract class AbstractProjectableEntity extends AbstractEntity implements ProjectableEntity
{
    /**
     * @var string
     * @todo Use real Uuid here, first rewrite Extbase's magic reflection
     */
    protected $uuid;

    /**
     * @var int
     */
    protected $revision;

    /**
     * @param string $uuid
     * @deprecated
     */
    public function _setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return UuidInterface
     */
    public function getUuidInterface()
    {
        return Uuid::fromString($this->uuid);
    }

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }
}
