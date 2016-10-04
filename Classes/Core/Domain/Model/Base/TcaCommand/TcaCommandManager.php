<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\TcaCommand;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Providable;

final class TcaCommandManager implements Providable
{
    /**
     * @var TcaCommandManager
     */
    private static $instance;

    /**
     * @var TcaCommand[]
     */
    private $tableCommand;

    /**
     * @param bool $force
     * @return TcaCommandManager
     */
    static public function provide(bool $force = false)
    {
        if ($force || !isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function __construct()
    {
    }

    /**
     * @param string $tableName
     * @return TcaCommand
     */
    public function for(string $tableName)
    {
        if (!$this->has($tableName))
        {
            $this->tableCommand[$tableName] = TcaCommand::create($tableName);
        }
        return $this->tableCommand[$tableName];
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function has(string $tableName)
    {
        return isset($this->tableCommand[$tableName]);
    }
}
