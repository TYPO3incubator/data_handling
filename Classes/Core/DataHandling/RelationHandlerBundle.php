<?php
namespace TYPO3\CMS\DataHandling\Core\DataHandling;

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

use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\RelationSequence;

class RelationHandlerBundle
{
    /**
     * @param RelationHandler $relationHandler
     * @param \Closure $commitClosure
     * @return RelationHandlerBundle
     */
    public static function create(RelationHandler $relationHandler, \Closure $commitClosure)
    {
        return new static($relationHandler, $commitClosure);
    }

    /**
     * @var RelationHandler
     */
    private $relationHandler;

    /**
     * @var \Closure
     */
    private $commitClosure;

    /**
     * @param RelationHandler $relationHandler
     * @param \Closure $commitClosure
     */
    private function __construct(RelationHandler $relationHandler, \Closure $commitClosure)
    {
        $this->relationHandler = $relationHandler;
        $this->commitClosure = $commitClosure;
    }

    /**
     * @return RelationHandler
     */
    public function getRelationHandler()
    {
        return $this->relationHandler;
    }

    public function attach(EntityReference $reference)
    {
        if ($reference->getUuid() === EntityReference::DEFAULT_UUID) {
            $uid = 0;
        } else {
            $uid = $this->updateReference($reference)->getUid();
        }

        $this->relationHandler->itemArray[] = [
            'table' => $reference->getName(),
            'id' => $uid,
        ];

        $this->relationHandler->tableArray[$reference->getName()][] = $uid;
    }

    public function remove(EntityReference $reference)
    {
        $tableName = $reference->getName();
        $itemArrayIndex = $this->searchInItemArray($reference);
        $tableArrayIndex = $this->searchInTableArray($reference);

        if ($itemArrayIndex !== false && $tableArrayIndex !== false) {
            unset(
                $this->relationHandler->itemArray[$itemArrayIndex]
            );
            unset(
                $this->relationHandler->tableArray[$tableName][$itemArrayIndex]
            );
        }
    }

    public function order(RelationSequence $sequence)
    {
        $items = [];
        $tableArray = [];
        $tableName = $sequence->getName();

        foreach ($sequence->get() as $relationReference) {
            $reference = $relationReference->getEntityReference();

            $itemArrayIndex = $this->searchInItemArray($reference);
            $tableArrayIndex = $this->searchInTableArray($reference);

            if ($itemArrayIndex === false || $tableArrayIndex === false) {
                throw new \RuntimeException(
                    'Item ' . $reference->__toString() . ' not found',
                    1474479332
                );
            }

            unset(
                $this->relationHandler->itemArray[$itemArrayIndex]
            );
            unset(
                $this->relationHandler->tableArray[$tableName][$tableArrayIndex]
            );

            $items[] = [
                'table' => $reference->getName(),
                'id' => $reference->getUid(),
            ];
            $tableArray[] = $reference->getUid();
        }

        if (count($this->relationHandler->itemArray)) {
            throw new \RuntimeException(
                'Not all items have been ordered',
                1474479333
            );
        }

        $this->relationHandler->itemArray = $items;
        $this->relationHandler->tableArray[$tableName] = $tableArray;
    }

    /**
     * @return string|null
     */
    public function commit()
    {
        return $this->commitClosure->call($this, $this->relationHandler);
    }

    /**
     * @param EntityReference $reference
     * @return bool|int|string
     */
    private function searchInItemArray(EntityReference $reference)
    {
        $tableName = $reference->getName();
        if (empty($this->relationHandler->itemArray)) {
            return false;
        }
        foreach ($this->relationHandler->itemArray as $index => $item) {
            if (
                $item['table'] === $tableName
                && (string)$item['id'] === (string)$reference->getUid()
            ) {
                return $index;
            }
        }
        return false;
    }

    /**
     * @param EntityReference $reference
     * @return bool|int|string
     */
    private function searchInTableArray(EntityReference $reference)
    {
        $tableName = $reference->getName();
        if (empty($this->relationHandler->tableArray[$tableName])) {
            return false;
        }
        foreach ($this->relationHandler->tableArray[$tableName] as $index => $itemId) {
            if (
                (string)$itemId === (string)$reference->getUid()
            ) {
                return $index;
            }
        }
        return false;
    }

    /**
     * @param EntityReference $reference
     * @return EntityReference
     */
    private function updateReference(EntityReference $reference)
    {
        if (MathUtility::canBeInterpretedAsInteger($reference->getUid())) {
            return $reference;
        }

        $uid = UuidUtility::fetchUid($reference);
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \RuntimeException('Cannot resolve UID', 1476700045);
        }

        $resolvedReference = EntityReference::instance()
            ->import($reference)
            ->setUid($uid);
        return $resolvedReference;
    }
}