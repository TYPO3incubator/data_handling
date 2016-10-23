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

class PassiveRelation implements Relational
{
    /**
     * @return PassiveRelation
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
     * @var Property
     */
    protected $from;

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function setProperty(Property $property): PassiveRelation
    {
        $this->property = $property;
        return $this;
    }

    public function getFrom(): Property
    {
        return $this->from;
    }

    public function setFrom(Property $from): PassiveRelation
    {
        $this->from = $from;
        return $this;
    }
}
