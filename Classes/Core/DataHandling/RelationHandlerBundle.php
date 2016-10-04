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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
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
        $this->relationHandler->itemArray[] = [
            'table' => $reference->getName(),
            'id' => $reference->getUuid(),
        ];
    }

    public function remove(EntityReference $reference)
    {
        $index = $this->searchItem($reference);
        if ($index !== false) {
            unset($this->relationHandler->itemArray[$index]);
        }
    }

    public function order(RelationSequence $sequence)
    {
        $items = [];
        foreach ($sequence->get() as $relationReference) {
            $reference = $relationReference->getEntityReference();

            $index = $this->searchItem($reference);
            if ($index === false) {
                throw new \RuntimeException(
                    'Item ' . $reference->__toString() . ' not found',
                    1474479332
                );
            }
            unset($this->relationHandler->itemArray[$index]);

            $items[] = [
                'table' => $reference->getName(),
                'id' => $reference->getUid(),
            ];
        }

        if (count($this->relationHandler->itemArray)) {
            throw new \RuntimeException(
                'Not all items have been ordered',
                1474479333
            );
        }

        $this->relationHandler->itemArray = $items;
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
    private function searchItem(EntityReference $reference)
    {
        foreach ($this->relationHandler->itemArray as $index => $item) {
            if (
                $item['table'] === $reference->getName()
                && (string)$item['id'] === (string)$reference->getUid()
            ) {
                return $index;
            }
        }
        return false;
    }
}