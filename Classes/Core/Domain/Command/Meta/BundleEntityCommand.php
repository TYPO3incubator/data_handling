<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Command\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Bundle;
use TYPO3\CMS\DataHandling\Core\Domain\Object\BundleTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class BundleEntityCommand extends AbstractCommand implements Instantiable, Bundle
{
    use BundleTrait;

    /**
     * @return BundleEntityCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param AbstractCommand[] $commands
     * @return AttachRelationCommand
     */
    public static function create(array $commands)
    {
        if (empty($commands)) {
            throw new \LogicException('At least one command required in bundle', 1473452055);
        }

        $command = static::instance();
        $command->commands = $commands;
        return $command;
    }

    /**
     * @return AbstractCommand
     */
    public function getFirstCommand()
    {
        reset($this->commands);
        return current($this->commands);
    }
}
