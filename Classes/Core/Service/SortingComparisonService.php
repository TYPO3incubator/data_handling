<?php
namespace TYPO3\CMS\DataHandling\Core\Service;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Framework\Object\RepresentableAsString;

class SortingComparisonService implements SingletonInterface
{
    const ACTION_ADD = 'add';
    const ACTION_REMOVE = 'remove';
    const ACTION_ORDER = 'order';

    /**
     * @return SortingComparisonService
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(SortingComparisonService::class);
    }

    public function compare(array $source, array $target) {
        $actions = [];

        if (empty($source) && empty($target)) {
            return $actions;
        }
        if (empty($source) && !empty($target)) {
            foreach ($target as $item) {
                $actions[] = [
                    'action' => static::ACTION_ADD,
                    'item' => $item,
                ];
            }
            return $actions;
        }
        if (!empty($source) && empty($target)) {
            foreach ($source as $item) {
                $actions[] = [
                    'action' => static::ACTION_REMOVE,
                    'item' => $item,
                ];
            }
            return $actions;
        }

        $removedItems = $this->arrayDiff($source, $target);
        $addedItems = $this->arrayDiff($target, $source);
        $sameItems = $this->arrayIntersect($source, $target);

        foreach ($removedItems as $item) {
            $actions[] = [
                'action' => static::ACTION_REMOVE,
                'item' => $item,
            ];
        }
        foreach ($addedItems as $item) {
            $actions[] = [
                'action' => static::ACTION_ADD,
                'item' => $item,
            ];
        }

        if (empty($sameItems) && !empty($addedItems)) {
            return $actions;
        }

        if ($this->isSequential($target, $sameItems)) {
            return $actions;
        }

        $actions[] = [
            'action' => static::ACTION_ORDER,
            'items' => $target,
        ];

        return $actions;
    }

    protected function isSequential(array $haystack, array $needle): bool
    {
        foreach ($needle as $index => $needleItem) {
            if (!isset($haystack[$index])
                || !$this->equals($haystack[$index], $needleItem)) {
                return false;
            }
        }

        return true;
    }

    protected function equals($source, $target): bool
    {
        if (
            $source instanceof RepresentableAsString
            && $target instanceof RepresentableAsString
        ) {
            return ($source->__toString() === $target->__toString());
        }

        return ($source === $target);
    }

    /**
     * Executes array_diff() and re-indexes array.
     *
     * @param array $source
     * @param array $target
     * @return array
     */
    protected function arrayDiff(array $source, array $target): array
    {
        $array = array_diff($source, $target);
        return array_merge($array);
    }

    /**
     * Executes array_intersect() and re-indexes array.
     *
     * @param array $source
     * @param array $target
     * @return array
     */
    protected function arrayIntersect(array $source, array $target): array
    {
        $array = array_intersect($source, $target);
        return array_merge($array);
    }
}
