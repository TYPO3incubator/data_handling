<?php
namespace TYPO3\CMS\DataHandling\Domain\Object\Record;

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

class BundleChangeMap
{
    /**
     * @return BundleChangeMap
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(BundleChangeMap::class);
    }

    /**
     * @var Bundle
     */
    protected $bundle;

    /**
     * @var Change
     */
    protected $change;

    public function getBundle(): Bundle
    {
        return $this->bundle;
    }

    public function setBundle(Bundle $bundle): BundleChangeMap
    {
        $this->bundle = $bundle;
        return $this;
    }

    public function getChange(): Change
    {
        return $this->change;
    }

    public function setChange(Change $change): BundleChangeMap
    {
        $this->change = $change;
        return $this;
    }
}
