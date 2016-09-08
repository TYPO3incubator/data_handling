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

final class TcaCommand implements Instantiable
{
    public static function instance()
    {
        return new static();
    }

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

    private function __construct()
    {
        $this->create = TcaCommandEntityBehavior::instance();
        $this->modify = TcaCommandEntityBehavior::instance();
        $this->delete = TcaCommandEntityBehavior::instance();
        $this->disable = TcaCommandEntityBehavior::instance();
        $this->enable = TcaCommandEntityBehavior::instance();
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function create()
    {
        return $this->create;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function modify()
    {
        return $this->modify;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function delete()
    {
        return $this->delete;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function disable()
    {
        return $this->disable;
    }

    /**
     * @return TcaCommandEntityBehavior
     */
    public function enable()
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
}
