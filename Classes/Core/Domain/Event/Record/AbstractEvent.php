<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event\Record;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Identifiable;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Relational;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequenceable;

abstract class AbstractEvent extends \TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent
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
     * @return array
     */
    public function exportData(): array
    {
        $array = [];

        if ($this->subject !== null) {
            $array['subject'] = $this->subject->__toArray();
        }

        if ($this instanceof IdentifiableTrait) {
            $array['identity'] = $this->getIdentity()->__toArray();
        }
        if ($this instanceof RelationalTrait) {
            $array['relation'] = $this->getRelation()->__toArray();
        }
        if ($this instanceof SequenceableTrait) {
            $array['sequence'] = $this->getSequence()->__toArray();
        }

        if ($this->data !== null) {
            $array['data'] = $this->data;
        }

        return $array;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return 'core-record-' . strtolower(preg_replace('#^(?:.*\\\\)?(\w+)Event$#', '$1', get_class($this)));
    }
}
