<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity;

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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Saga;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\EventApplicable;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\EventHandlerTrait;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;

class BranchedToWorkspace implements EventApplicable
{
    use EventHandlerTrait;

    /**
     * @param EntityReference $aggregateReference
     * @param int $workspaceId
     * @return BranchedToWorkspace
     */
    public static function create(EntityReference $aggregateReference, int $workspaceId)
    {
        return new static($aggregateReference, $workspaceId);
    }

    /**
     * @var EntityReference
     */
    private $aggregateReference;

    /**
     * @var int
     */
    private $workspaceId;

    /**
     * @var bool
     */
    private $result = false;

    /**
     * @param EntityReference $aggregateReference
     * @param int $workspaceId
     */
    private function __construct(EntityReference $aggregateReference, int $workspaceId)
    {
        $this->projected = true;
        $this->aggregateReference = $aggregateReference;
        $this->workspaceId = $workspaceId;
        $this->evaluate();
    }

    private function evaluate()
    {
        $eventSelector = GenericEntityEventRepository::createEventSelector(
            $this->aggregateReference
        );

        Saga::create($eventSelector)->tell($this);
    }

    /**
     * @param Event\BranchedEntityToEvent $event
     */
    protected function applyBranchedEntityToEvent(Event\BranchedEntityToEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== $this->workspaceId) {
            return;
        }

        if ($this->result) {
            throw new \LogicException(
                'Branched multiple times without merging',
                1474468791
            );
        }

        $this->result = true;
    }

    /**
     * @param Event\AbstractEvent $event
     * @todo Implement Merge functionality
     */
    protected function applyMergedEntityFromEvent(Event\AbstractEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== $this->workspaceId) {
            return;
        }

        if (!$this->result) {
            throw new \LogicException(
                'Merged without branching',
                1474468792
            );
        }

        $this->result = false;
    }

    /**
     * @return boolean
     */
    public function isTrue()
    {
        return $this->result;
    }
}
