<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Meta;

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

class ActiveRelation implements Relational
{
    /**
     * @return ActiveRelation
     */
    public static function instance()
    {
        return new static();
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
