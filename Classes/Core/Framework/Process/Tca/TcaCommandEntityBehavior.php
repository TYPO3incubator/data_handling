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

final class TcaCommandEntityBehavior implements Instantiable
{
    public static function instance()
    {
        return new static();
    }

    private function __constructor()
    {
    }

    /**
     * @var TcaCommandFactory
     */
    private $factory;

    /**
     * @var bool
     */
    private $allowed = false;

    /**
     * @var bool
     */
    private $parentRequired = false;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var TcaCommandRelationBehavior[]
     */
    private $relations = [];

    /**
     * @return boolean
     */
    public function isAllowed()
    {
        return $this->allowed;
    }

    /**
     * @param boolean $allowed
     * @return static
     */
    public function setAllowed($allowed)
    {
        $this->allowed = $allowed;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isParentRequired()
    {
        return $this->parentRequired;
    }

    /**
     * @param boolean $parentRequired
     * @return static
     */
    public function setParentRequired(bool $parentRequired)
    {
        $this->parentRequired = $parentRequired;
        return $this;
    }

    /**
     * @return TcaCommandFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param TcaCommandFactory $factory
     * @return static
     */
    public function setFactory(TcaCommandFactory $factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return static
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param string $fieldName
     * @return TcaCommandRelationBehavior
     */
    public function forRelation(string $fieldName)
    {
        if (!$this->hasRelation($fieldName)) {
            $this->relations[$fieldName] = TcaCommandRelationBehavior::instance();
        }
        return $this->relations[$fieldName];
    }

    public function hasRelation(string $fieldName)
    {
        return isset($this->relations[$fieldName]);
    }
}
