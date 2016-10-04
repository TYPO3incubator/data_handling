<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Command;

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

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use League\Tactician\Plugins\LockingMiddleware;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Command\CommandHandler;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Providable;

final class CommandBus implements Providable
{
    /**
     * @var CommandBus
     */
    private static $instance;

    /**
     * @param bool $force
     * @return CommandBus
     */
    static public function provide(bool $force = false)
    {
        if ($force || !isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @var HandlerLocator
     */
    private $locator;

    /**
     * @var \League\Tactician\CommandBus
     */
    private $commandBus;

    private function __construct()
    {
        $this->locator = new InMemoryLocator();

        $handlerMiddleware = new CommandHandlerMiddleware(
            new ClassNameExtractor(),
            $this->locator,
            new HandleInflector()
        );

        $lockingMiddleware = new LockingMiddleware();
        $this->commandBus =  new \League\Tactician\CommandBus([$lockingMiddleware, $handlerMiddleware]);
    }

    /**
     * @param CommandHandler $handler
     * @param string $commandClassName
     */
    public function addHandler(CommandHandler $handler, string $commandClassName)
    {
        $this->locator->addHandler($handler, $commandClassName);
    }

    /**
     * @param CommandHandler $handlerBundle
     * @param string[] $commandClassNames
     */
    public function addHandlerBundle(CommandHandler $handlerBundle, array $commandClassNames)
    {
        foreach ($commandClassNames as $commandClassName) {
            $this->addHandler($handlerBundle, $commandClassName);
        }
    }

    public function handle($command)
    {
        $this->commandBus->handle($command);
    }
}
