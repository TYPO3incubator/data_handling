<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;

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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Contextual;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\ContextualTrait;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\FromReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\FromReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EventReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\NodeReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\NodeReferenceTrait;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\RelationReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\RelationReferenceTrait;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\SequenceTrait;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\TargetReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\TargetReferenceTrait;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\StorableEvent;

abstract class AbstractEvent extends BaseEvent implements StorableEvent, Contextual, AggregateReference
{
    use ContextualTrait;
    use AggregateReferenceTrait;

    /**
     * @return UuidInterface
     */
    public function getAggregateId()
    {
        return Uuid::fromString(
            $this->aggregateReference->getUuid()
        );
    }

    /**
     * @return string
     */
    public function getAggregateType()
    {
        return $this->aggregateReference->getName();
    }

    /**
     * @return array
     */
    public function exportData()
    {
        $data = [];

        if ($this instanceof Contextual) {
            $data['context'] = $this->context->toArray();
        }
        if ($this instanceof AggregateReference) {
            $data['aggregateReference'] = $this->getAggregateReference()->toArray();
        }
        if ($this instanceof NodeReference) {
            $data['nodeReference'] = $this->getNodeReference()->toArray();
        }
        if ($this instanceof TargetReference) {
            $data['targetReference'] = $this->getTargetReference()->toArray();
        }
        if ($this instanceof FromReference) {
            $data['fromReference'] = $this->getFromReference()->__toArray();
        }
        if ($this instanceof RelationReference) {
            $data['relationReference'] = $this->getRelationReference()->toArray();
        }
        if ($this instanceof Sequence) {
            $data['sequence'] = $this->getSequence()->__toArray();
        }

        return $data;
    }

    /**
     * @param array $data
     */
    public function importData(array $data)
    {
        if ($this instanceof Contextual) {
            /** @var $this ContextualTrait */
            $this->context = Context::fromArray($data['context']);
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
    }
}
