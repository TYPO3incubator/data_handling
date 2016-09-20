<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler;

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

use TYPO3\CMS\DataHandling\Core\Framework\Domain\Command\DomainCommand;
use TYPO3\CMS\DataHandling\Core\Utility\ClassNamingUtility;

trait CommandHandlerTrait
{
    /**
     * @param DomainCommand $command
     * @return null|mixed
     */
    public function handle(DomainCommand $command)
    {
        // determine method name, that is used to execute the command
        $methodName = $this->getCommandHandlerMethodName($command);
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($command);
        }
        return null;
    }

    /**
     * @param DomainCommand $command
     * @return string
     */
    protected function getCommandHandlerMethodName(DomainCommand $command)
    {
        $commandName = ClassNamingUtility::getLastPart($command);
        return 'handle' . $commandName;
    }
}
