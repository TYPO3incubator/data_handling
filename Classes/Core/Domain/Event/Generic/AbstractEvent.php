<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event\Generic;

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
use TYPO3\CMS\DataHandling\Core\Domain\Command\Generic\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Identifiable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Relational;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequenceable;

abstract class AbstractEvent extends Event\AbstractEvent implements Event\Definition\StorableEvent
{
    /**
     * @param AbstractCommand $command
     * @return AbstractEvent
     */
    static public function fromCommand(AbstractCommand $command)
    {
        $event = GeneralUtility::makeInstance(static::class);

        if ($command->getSubject() !== null) {
            $event->setSubject($command->getSubject());
        }

        if ($command instanceof Identifiable && $event instanceof Identifiable) {
            $event->setIdentity($command->getIdentity());
        }
        if ($command instanceof Relational && $event instanceof Relational) {
            $event->setRelation($command->getRelation());
        }
        if ($command instanceof Sequenceable && $event instanceof Sequenceable) {
            $event->setSequence($command->getSequence());
        }

        if ($command->getData() !== null) {
            $event->setData($command->getData());
        }
        if ($command->getMetadata() !== null) {
            $event->setMetadata($command->getMetadata());
        }

        return $event;
    }

    /**
     * Subject the event was applied to.
     *
     * @var EntityReference
     */
    protected $subject;

    /**
     * @return null|EntityReference
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param EntityReference $subject
     * @return AbstractEvent
     */
    public function setSubject(EntityReference $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return null|array
     */
    public function exportData()
    {
        $data = [];

        if ($this->subject !== null) {
            $data['subject'] = $this->subject->__toArray();
        }

        if ($this instanceof Identifiable) {
            $data['identity'] = $this->getIdentity()->__toArray();
        }
        if ($this instanceof Relational) {
            $data['relation'] = $this->getRelation()->__toArray();
        }
        if ($this instanceof Sequenceable) {
            $data['sequence'] = $this->getSequence()->__toArray();
        }

        if ($this->data !== null) {
            $data['data'] = $this->data;
        }

        if (empty($data)) {
            $data = null;
        }

        return $data;
    }

    /**
     * @param null|array $data
     * @return AbstractEvent
     */
    public function importData($data)
    {
        if ($data === null) {
            return $this;
        }

        if (isset($data['data'])) {
            $this->data = $data['data'];
        }

        if (isset($data['subject'])) {
            $this->setSubject(
                EntityReference::fromArray($data['subject'])
            );
        }

        if ($this instanceof Identifiable) {
            $this->setIdentity(
                EntityReference::fromArray($data['identity'])
            );
        }
        if ($this instanceof Relational) {
            $this->setRelation(
                PropertyReference::fromArray($data['relation'])
            );
        }
        if ($this instanceof Sequenceable) {
            $this->setSequence(
                RelationSequence::fromArray($data['sequence'])
            );
        }

        return $this;
    }
}
