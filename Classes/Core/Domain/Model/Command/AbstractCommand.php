<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Command;

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

use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Command\DomainCommand;

abstract class AbstractCommand implements DomainCommand, AggregateReference
{
    /**
     * Subject the command is applied to.
     *
     * @var EntityReference
     */
    protected $subject;

    /**
     * @var array|null
     */
    protected $metadata;

    /**
     * @return null|EntityReference
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param EntityReference $subject
     * @return AbstractCommand
     */
    public function setSubject(EntityReference $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param array|null $metadata
     * @return AbstractCommand
     */
    public function setMetadata(array $metadata = null)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
