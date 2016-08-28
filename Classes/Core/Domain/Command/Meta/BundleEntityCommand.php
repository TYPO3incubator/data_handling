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
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class BundleEntityCommand extends AbstractCommand implements Instantiable
{
    /**
     * @return BundleEntityCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(BundleEntityCommand::class);
    }

    /**
     * @param AbstractCommand[] $commands
     * @return AttachRelationCommand
     */
    public static function create(array $commands)
    {
        $command = static::instance();
        $command->commands = $commands;
        return $command;
    }

    /**
     * @var AbstractCommand[]
     */
    protected $commands;

    /**
     * @return AbstractCommand[]
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
