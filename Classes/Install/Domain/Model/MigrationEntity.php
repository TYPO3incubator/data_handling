<?php
namespace TYPO3\CMS\DataHandling\Install\Domain\Model;

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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EventReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;

class MigrationEntity extends GenericEntity
{
    /**
     * @return MigrationEntity
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param EntityReference $subject
     * @return EntityReference
     */
    protected static function createNewReference(EntityReference $subject)
    {
        return $subject;
    }

    /**
     * @param Context $context
     * @param EntityReference $suggestedReference
     * @param EntityReference $nodeReference
     * @param array $suggestedMetadata
     * @return static
     */
    public static function createEntityMigration(Context $context, EntityReference $suggestedReference, EntityReference $nodeReference, array $suggestedMetadata)
    {
        $migrationEntity = static::createEntity(
            $context,
            $suggestedReference,
            $nodeReference
        );
        $migrationEntity->applyMetadata($suggestedMetadata);

        return $migrationEntity;
    }

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @param Context $context
     * @param EntityReference $suggestedReference
     * @param array $suggestedMetadata
     * @return static
     */
    public function branchEntityToMigration(Context $context, EntityReference $suggestedReference, array $suggestedMetadata)
    {
        $branchedEntity = static::createNewEntity(
            $context,
            $suggestedReference
        );

        $event = Event\BranchedEntityToEvent::create(
            $context,
            $this->subject,
            $branchedEntity->getSubject()
        );

        $branchedEntity->branchEntityFrom(
            $context,
            EventReference::instance()
                ->setEntityReference($this->getSubject())
                ->setEventId($event->getEventId())
        );
        $branchedEntity->applyMetadata($suggestedMetadata);

        $this->manageEvent($event);

        return $branchedEntity;
    }

    /**
     * @param Context $context
     * @param EntityReference $suggestedReference
     * @param array $suggestedMetadata
     * @return static
     */
    public function translateEntityToMigration(Context $context, EntityReference $suggestedReference, array $suggestedMetadata)
    {
        $translatedEntity = static::createNewEntity(
            $context,
            $suggestedReference
        );

        $event = Event\TranslatedEntityToEvent::create(
            $context,
            $this->subject,
            $translatedEntity->getSubject()
        );

        $translatedEntity->translateEntityFrom(
            $context,
            EventReference::instance()
                ->setEntityReference($this->subject)
                ->setEventId($event->getEventId())
        );
        $translatedEntity->applyMetadata($suggestedMetadata);

        $this->manageEvent($event);

        return $translatedEntity;
    }

    /**
     * @param array $metadata
     * @return static
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @param BaseEvent $event
     */
    protected function recordEvent(BaseEvent $event)
    {
        $event->setMetadata($this->metadata);
        parent::recordEvent($event);
    }

    /**
     * @param array $metadata
     * @return static
     */
    private function applyMetadata(array $metadata)
    {
        foreach ($this->recordedEvents as $recordedEvent) {
            $recordedEvent->setMetadata($metadata);
        }
        return $this;
    }
}
