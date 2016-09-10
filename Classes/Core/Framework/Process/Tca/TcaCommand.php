<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Process\Tca;

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

use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

final class TcaCommand
{
    public static function create(string $tableName)
    {
        return new static($tableName);
    }

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var TcaCommandEntityBehavior
     */
    private $create;

    /**
     * @var TcaCommandEntityBehavior
     */
    private $modify;

    /**
     * @var TcaCommandEntityBehavior
     */
    private $delete;

    /**
     * @var TcaCommandEntityBehavior
     */
    private $disable;

    /**
     * @var TcaCommandEntityBehavior
     */
    private $enable;

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @var bool
     */
    private $deniedPerDefault = false;

    private function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->create = TcaCommandEntityBehavior::instance();
        $this->modify = TcaCommandEntityBehavior::instance();
        $this->delete = TcaCommandEntityBehavior::instance();
        $this->disable = TcaCommandEntityBehavior::instance();
        $this->enable = TcaCommandEntityBehavior::instance();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function onCreate()
    {
        return $this->create;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function onModify()
    {
        return $this->modify;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function onDelete()
    {
        return $this->delete;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function onDisable()
    {
        return $this->disable;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function onEnable()
    {
        return $this->enable;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param array $mapping
     * @return static
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeniedPerDefault()
    {
        return $this->deniedPerDefault;
    }

    /**
     * @param boolean $deniedPerDefault
     * @return static
     */
    public function setDeniedPerDefault($deniedPerDefault)
    {
        $this->deniedPerDefault = $deniedPerDefault;
        return $this;
    }
}
