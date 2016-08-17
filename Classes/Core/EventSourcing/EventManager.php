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
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class EventManager implements Providable, Manageable, Listenable
{
    const LISTEN_BEFORE = 'beforeManage';
    const LISTEN_AFTER = 'afterManage';

    /**
     * @var EventManager
     */
    static protected $eventManager;

    /**
     * @param bool $force
     * @return EventManager
     */
    public static function provide(bool $force = false)
    {
        if (!isset(static::$eventManager)) {
            static::$eventManager = static::instance();
        }
        return static::$eventManager;
    }
    public function handle() {

    }

    /**
     * @return EventManager
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventManager::class);
    }

    /**
     * @var Committable[]
     */
    protected $committers = [];

    /**
     * @var Publishable[]
     */
    protected $publishers = [];

    /**
     * @var callable[][]
     */
    protected $listeners = [
        self::LISTEN_BEFORE => [],
        self::LISTEN_AFTER => [],
    ];

    /**
     * @param mixed $delegate
     * @return EventManager
     * @deprecated Either use bindCommitter() or bindPublisher()
     */
    public function bind($delegate) {
        if (!($delegate instanceof Committable) && !($delegate instanceof Publishable)) {
            throw new \RuntimeException('Committable or Publishable expected', 1471467847);
        }

        if ($delegate instanceof Committable) {
            $this->bindCommitter($delegate);
        }
        if ($delegate instanceof Publishable) {
            $this->bindPublisher($delegate);
        }

        return $this;
    }

    /**
     * @param Committable $committer
     * @return EventManager
     */
    public function bindCommitter(Committable $committer)
    {
        if (!in_array($committer, $this->publishers, true)) {
            $this->committers[] = $committer;
        }
        return $this;
    }

    /**
     * @param Publishable $publisher
     * @return EventManager
     */
    public function bindPublisher(Publishable $publisher) {
        if (!in_array($publisher, $this->publishers, true)) {
            $this->publishers[] = $publisher;
        }
        return $this;
    }

    /**
     * @param string $type
     * @param callable $listener
     * @return EventManager
     */
    public function on(string $type, callable $listener)
    {
        if (!in_array($type, array_keys($this->listeners))) {
            throw new \RuntimeException('Cannot listen to type "' . $type . '"', 1471032608);
        }
        if (!in_array($listener, $this->listeners[$type])) {
            $this->listeners[$type][] = $listener;
        }
        return $this;
    }

    /**
     * @param string $type
     * @param callable $listener
     * @return EventManager
     */
    public function off(string $type, callable $listener)
    {
        if (!in_array($type, array_keys($this->listeners))) {
            throw new \RuntimeException('Cannot listen to type "' . $type . '"', 1471032609);
        }
        if (!in_array($listener, $this->listeners[$type])) {
            $this->listeners[$type][] = $listener;
        }
        return $this;
    }

    /**
     * @param AbstractEvent $event
     * @return EventManager
     */
    public function manage(AbstractEvent $event)
    {
        foreach ($this->listeners[static::LISTEN_BEFORE] as $listener) {
            call_user_func($listener, $event);
        }
        foreach ($this->committers as $commiter) {
            $commiter->commit($event);
        }
        foreach ($this->publishers as $publisher) {
            $publisher->publish($event);
        }
        foreach ($this->listeners[static::LISTEN_AFTER] as $listener) {
            call_user_func($listener, $event);
        }
        return $this;
    }
}
