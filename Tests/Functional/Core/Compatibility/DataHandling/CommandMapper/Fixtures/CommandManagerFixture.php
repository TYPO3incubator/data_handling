<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\CommandMapper\Fixtures;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\DataHandling\Core\DataHandling\CommandManager;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta\AbstractCommand;

class CommandManagerFixture extends CommandManager implements SingletonInterface
{
    /**
     * @var AbstractCommand[]
     */
    protected $commands = [];

    public function handle(AbstractCommand $command)
    {
        $this->commands[] = $command;
    }

    public function getCommands(): array {
        return $this->commands;
    }
}