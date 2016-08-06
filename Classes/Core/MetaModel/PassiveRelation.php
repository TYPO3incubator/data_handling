<?php
namespace TYPO3\CMS\DataHandling\Core\MetaModel;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;

class PassiveRelation implements Relational
{
    /**
     * @return PassiveRelation
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(PassiveRelation::class);
    }

    /**
     * @var Property
     */
    protected $property;

    /**
     * @var Property
     */
    protected $from;

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function setProperty(Property $property): ActiveRelation
    {
        $this->property = $property;
        return $this;
    }

    public function getFrom(): Property
    {
        return $this->from;
    }

    public function setFrom(Property $from): ActiveRelation
    {
        $this->from = $from;
        return $this;
    }
}
