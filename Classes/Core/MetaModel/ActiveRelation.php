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

class ActiveRelation implements Relational
{
    /**
     * @return ActiveRelation
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ActiveRelation::class);
    }

    /**
     * @var Property
     */
    protected $property;

    /**
     * @var Schema
     */
    protected $to;

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function setProperty(Property $property): ActiveRelation
    {
        $this->property = $property;
        return $this;
    }

    public function getTo(): Schema
    {
        return $this->to;
    }

    public function setTo(Schema $to): ActiveRelation
    {
        $this->to = $to;
        return $this;
    }
}
