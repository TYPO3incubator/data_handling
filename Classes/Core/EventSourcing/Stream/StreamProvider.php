<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Stream;

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
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class StreamProvider implements Instantiable, Providable
{
    /**
     * @var StreamProvider
     */
    protected static $streamProvider;

    /**
     * @param bool $force
     * @return StreamProvider
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(static::$streamProvider)) {
            static::$streamProvider = static::instance();
        }
        return static::$streamProvider;
    }

    /**
     * @return StreamProvider
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(StreamProvider::class);
    }

    /**
     * @var AbstractStream[]
     */
    protected $streams = [];

    /**
     * @param string $name
     * @param AbstractStream $stream
     * @return StreamProvider
     */
    public function registerStream(string $name, AbstractStream $stream)
    {
        if (!($stream instanceof Instantiable)) {
            throw new \RuntimeException('Stream must be instantiable', 1471614998);
        }
        $this->streams[$name] = $stream;
        return $this;
    }

    /**
     * @param string $name
     * @return AbstractStream
     */
    public function useStream(string $name)
    {
        if (!isset($this->streams[$name])) {
            throw new \RuntimeException('Stream "' . $name . '" is not registered', 1471614999);
        }
        return $this->streams[$name];
    }

    /**
     * @param string $name
     * @return AbstractStream
     */
    public function newStream(string $name)
    {
        /** @var Instantiable $streamClassName */
        $streamClassName = get_class($this->useStream($name));
        return $streamClassName::instance();
    }
}
