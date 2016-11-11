<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\DataHandlerTranslator\Fixtures;

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

use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Command\CommandHandler;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Command\DomainCommand;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Bundle;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command\AbstractCommand;

class CommandHandlerFixture implements CommandHandler
{
    /**
     * @var DomainCommand[]
     */
    private $commands = [];

    /**
     * @param DomainCommand $command
     */
    public function handle(DomainCommand $command)
    {
        $this->commands[] = $command;
    }

    public function getCommands(): array {
        return $this->commands;
    }

    /**
     * @param string $commandClassName
     * @return AbstractCommand[]
     */
    public function getBundleCommands(string $commandClassName)
    {
        $bundleCommands = [];

        foreach ($this->commands as $command) {
            if (
                !($command instanceof Bundle)
                || !is_a($command, $commandClassName)
            ) {
                continue;
            }
            $bundleCommands = array_merge(
                $bundleCommands,
                $command->getCommands()
            );
        }

        return $bundleCommands;
    }
}