<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event;

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

use TYPO3\CMS\DataHandling\Core\Type\MicroDateTime;

abstract class AbstractEvent
{
    /**
     * @var \DateTime
     */
    protected $eventDate;

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var array|null
     */
    protected $metadata;

    public function __construct()
    {
        $this->eventDate = MicroDateTime::create('now');
    }

    public function getEventDate()
    {
        return $this->eventDate;
    }

    public function setData(array $data = null)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setMetadata(array $metadata = null)
    {
        $this->metadata = $metadata;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    abstract public function toArray(): array;
}
