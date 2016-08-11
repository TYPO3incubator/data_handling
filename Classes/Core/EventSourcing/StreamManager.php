<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing;

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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\AbstractStream;
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class StreamManager implements Providable
{
    /**
     * @var StreamManager
     */
    static protected $streamManager;

    /**
     * @param bool $force
     * @return StreamManager
     */
    public static function provide(bool $force = false)
    {
        if (!isset(static::$streamManager)) {
            static::$streamManager = static::instance();
        }
        return static::$streamManager;
    }

    /**
     * @return StreamManager
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(StreamManager::class);
    }

    /**
     * @var AbstractStream[]
     */
    protected $streams = [];

    /**
     * @param string $name
     * @param string $className
     * @return AbstractStream
     */
    public function provideStream(string $name, string $className) {
        if (!is_subclass_of($className, AbstractStream::class)) {
            throw new \RuntimeException('Class "' . $className . '" is not a stream', 1470919616);
        }
        if (!in_array(Instantiable::class, class_implements($className))) {
            throw new \RuntimeException('Class "' . $className . '" cannot be instantiated', 1470919617);
        }
        if (!isset($this->streams[$name])) {
            /** @var AbstractStream $stream */
            $stream = call_user_func($className . '::instance');
            $stream->setName($name);
            $this->streams[$name] = $stream;
        }
        return $this->streams[$name];
    }
}
