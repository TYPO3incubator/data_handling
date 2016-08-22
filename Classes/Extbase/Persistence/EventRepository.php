<?php
namespace TYPO3\CMS\DataHandling\Extbase\Persistence;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\RevisionReference;
use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The repository for events.
 */
class EventRepository extends Repository
{
    /**
     * @param int $uid
     * @return AbstractProjectableEntity
     */
    public function findByUid($uid)
    {
        return parent::findByUid($uid);
    }

    /**
     * @param string $uuid
     * @return AbstractProjectableEntity
     */
    public function fetchByUuid(string $uuid)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('uuid', $uuid));
        return $query->execute()->getFirst();
    }

    /**
     * @param string $uuid
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function removeByUuid(string $uuid)
    {
        $entity = $this->fetchByUuid($uuid);
        if (!empty($entity)) {
            $this->remove($entity);
        }
    }

    /**
     * @return RevisionReference[]
     */
    public function fetchRevisionReferences()
    {
        $revisionReferences = [];
        $query = $this->createQuery();
        $query->matching(
            $query->greaterThanOrEqual('uuid', 0)
        );
        foreach ($query->execute(true) as $entity) {
            $reference = RevisionReference::fromRecord($this->getTableName(), $entity);
            $revisionReferences[$reference->getEntityReference()->getUuid()] = $reference;;
        }
        return $revisionReferences;
    }

    public function persistAll()
    {
        $this->persistenceManager->persistAll();
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        return $this->getDataMapper()->convertClassNameToTableName($this->objectType);
    }

    /**
     * @return DataMapper
     */
    protected function getDataMapper()
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(DataMapper::class);
    }
}
