<?php
namespace TYPO3\CMS\DataHandling\Integration\Slot;

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

use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Domain\Object\Record\Bundle;

/**
 * Fills generated uuid columns in schema
 */
class EditDocumentControllerSlot implements SingletonInterface
{
    /**
     * @var Bundle[]
     */
    protected $aggregates = [];

    /**
     * @return EditDocumentControllerSlot
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EditDocumentControllerSlot::class);
    }

    /**
     * Basic method to determine edited aggregates in backend forms.
     *
     * @param EditDocumentController $controller
     * @todo The quick-edit mode is processed in SimpleDataHandlerController instead
     */
    public function fetchEditedAggregates(EditDocumentController $controller)
    {
        foreach ($controller->editconf as $tableName => $uidListActions) {
            foreach ($uidListActions as $uidList => $action) {
                $uids = GeneralUtility::trimExplode(',', $uidList, true);
                foreach ($uids as $uid) {
                    $this->setAggregate($tableName, $uid);
                }
            }
        }
    }

    /**
     * @return Bundle[]
     */
    public function getAggregates(): array
    {
        return $this->aggregates;
    }

    public function setAggregate(string $tableName, string $uid)
    {
        $bundle = Bundle::instance();
        $bundle->getReference()->setUuid($uid)->setName($tableName);
        $this->aggregates[$tableName . ':' . $uid] = $bundle;
    }

    public function unsetAggregate(string $tableName, string $uid)
    {
        if ($this->hasAggregate($tableName, $uid)) {
            unset($this->aggregates[$tableName . ':' . $uid]);
        }
    }

    public function hasAggregate(string $tableName, string $uid): bool
    {
        return isset($this->aggregates[$tableName . ':' . $uid]);
    }
}
