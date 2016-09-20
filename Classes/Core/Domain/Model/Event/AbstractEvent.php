<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Event;

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


use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\FromReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\FromReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Locale;
use TYPO3\CMS\DataHandling\Core\Domain\Object\LocaleTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EventReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\RelationReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\RelationReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence;
use TYPO3\CMS\DataHandling\Core\Domain\Object\SequenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\TargetReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\TargetReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Workspace;
use TYPO3\CMS\DataHandling\Core\Domain\Object\WorkspaceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\StorableEvent;

abstract class AbstractEvent extends BaseEvent implements StorableEvent, AggregateReference
{
    use AggregateReferenceTrait;

    /**
     * @return array
     */
    public function exportData()
    {
        $data = [];

        if ($this instanceof AggregateReference) {
            $data['aggregateReference'] = $this->getAggregateReference()->__toArray();
        }
        if ($this instanceof NodeReference) {
            $data['nodeReference'] = $this->getNodeReference()->__toArray();
        }
        if ($this instanceof TargetReference) {
            $data['targetReference'] = $this->getTargetReference()->__toArray();
        }
        if ($this instanceof FromReference) {
            $data['fromReference'] = $this->getFromReference()->__toArray();
        }
        if ($this instanceof RelationReference) {
            $data['relationReference'] = $this->getRelationReference()->__toArray();
        }
        if ($this instanceof Sequence) {
            $data['sequence'] = $this->getSequence()->__toArray();
        }
        if ($this instanceof Workspace) {
            $data['workspaceId'] = $this->getWorkspaceId();
        }
        if ($this instanceof Locale) {
            $data['locale'] = $this->getLocale();
        }

        return $data;
    }

    /**
     * @param null|array $data
     */
    public function importData($data)
    {
        if ($data === null) {
            return;
        }

        if ($this instanceof AggregateReference) {
            /** @var $this AggregateReferenceTrait */
            $this->aggregateReference = EntityReference::fromArray($data['aggregateReference']);
        }
        if ($this instanceof NodeReference) {
            /** @var $this NodeReferenceTrait */
            $this->nodeReference = EntityReference::fromArray($data['nodeReference']);
        }
        if ($this instanceof TargetReference) {
            /** @var $this TargetReferenceTrait */
            $this->targetReference = EntityReference::fromArray($data['targetReference']);
        }
        if ($this instanceof FromReference) {
            /** @var $this FromReferenceTrait */
            $this->fromReference = EventReference::fromArray($data['fromReference']);
        }
        if ($this instanceof RelationReference) {
            /** @var $this RelationReferenceTrait */
            $this->relationReference = PropertyReference::fromArray($data['relationReference']);
        }
        if ($this instanceof Sequence) {
            /** @var $this SequenceTrait */
            $this->sequence = EntityReference::fromArray($data['sequence']);
        }
        if ($this instanceof Workspace) {
            /** @var $this WorkspaceTrait */
            $this->workspaceId = $data['workspaceId'];
        }
        if ($this instanceof Locale) {
            /** @var $this LocaleTrait */
            $this->locale = $data['locale'];
        }
    }
}
