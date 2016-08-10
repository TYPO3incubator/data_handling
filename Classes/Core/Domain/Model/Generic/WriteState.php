<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Generic;

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

use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Command\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\State;

class WriteState extends State
{
    /**
     * @param EntityReference $reference
     * @return WriteState
     */
    public static function instance(EntityReference $reference)
    {
        return GeneralUtility::makeInstance(WriteState::class, $reference);
    }

    /**
     * @param string $name
     * @return WriteState
     */
    public static function create(string $name)
    {
        return static::instance(EntityReference::instance()->setName($name)->setUuid(Uuid::uuid4()));
    }

    public function __construct(EntityReference $reference)
    {
        parent::__construct();
        $this->reference = $reference;
    }

    public function handleCommand(AbstractCommand $command)
    {
        $classNameParts = GeneralUtility::trimExplode('\\', get_class($command), true);
        $commandName = $classNameParts[count($classNameParts) - 1];
        $commandName = preg_replace('#Command$#i', '', $commandName);
        $commandName = lcfirst($commandName);

        if (method_exists($this, $commandName)) {

        }
    }

    public function change(array $values)
    {

    }

    public function remove() {

    }
}
