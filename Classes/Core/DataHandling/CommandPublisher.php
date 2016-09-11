<?php
namespace TYPO3\CMS\DataHandling\Core\DataHandling;

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
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta\CommandHandlerBundle;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Command\DomainCommand;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Providable;

/**
 * @deprecated
 */
class CommandPublisher implements Providable
{
    /**
     * @var CommandPublisher
     */
    static $commandPublisher;

    /**
     * @param bool $force
     * @return CommandPublisher
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(static::$commandPublisher)) {
            static::$commandPublisher = static::instance();
        }
        return static::$commandPublisher;
    }

    /**
     * @return CommandPublisher
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CommandPublisher::class);
    }

    public function publish(DomainCommand $command)
    {
        // @todo Add subscription logic
        if ($command instanceof AbstractCommand) {
            CommandHandlerBundle::instance()->handle($command);
        }
    }
}
