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

class Position
{
    const TYPE_TOP = 'top';
    const TYPE_BOTTOM = 'bottom';
    const TYPE_AFTER = 'after';

    /**
     * @return Position
     */
    public static function createTop()
    {
        $position = new static;
        $position->type = static::TYPE_TOP;
        return $position;
    }

    /**
     * @return Position
     */
    public static function createBottom()
    {
        $position = new static;
        $position->type = static::TYPE_BOTTOM;
        return $position;
    }

    /**
     * @param EntityReference $subject
     * @return Position
     */
    public static function createAfter(EntityReference $subject)
    {
        $position = new static;
        $position->type = static::TYPE_AFTER;
        $position->subject = $subject;
        return $position;
    }

    /**
     * @var string
     */
    private $type;

    /**
     * @var EntityReference
     */
    private $subject;

    private function __construct()
    {
    }

    /**
     * @return bool
     */
    public function isTop()
    {
        return ($this->type === static::TYPE_TOP);
    }

    /**
     * @return bool
     */
    public function isBottom()
    {
        return ($this->type === static::TYPE_BOTTOM);
    }

    /**
     * @return bool
     */
    public function isAfter()
    {
        return ($this->type === static::TYPE_AFTER);
    }

    /**
     * @return EntityReference
     */
    public function getSubject(): EntityReference
    {
        return $this->subject;
    }
}
