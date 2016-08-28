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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Locale;
use TYPO3\CMS\DataHandling\Core\Domain\Object\LocaleTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Workspace;
use TYPO3\CMS\DataHandling\Core\Domain\Object\WorkspaceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CreateEntityCommand extends AbstractCommand implements Instantiable, NodeReference, Workspace, Locale
{
    use NodeReferenceTrait;
    use WorkspaceTrait;
    use LocaleTrait;

    /**
     * @return CreateEntityCommand
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CreateEntityCommand::class);
    }

    /**
     * @param string $aggregateType
     * @param EntityReference $nodeReference
     * @param int $workspaceId
     * @param string $locale
     * @return CreateEntityCommand
     */
    public static function create(string $aggregateType, EntityReference $nodeReference, int $workspaceId, string $locale)
    {
        $command = static::instance();
        $command->aggregateType = $aggregateType;
        $command->nodeReference = $nodeReference;
        $command->workspaceId = $workspaceId;
        $command->locale = $locale;
        return $command;
    }

    /**
     * @var string
     */
    protected $aggregateType;

    /**
     * @return string
     */
    public function getAggregateType()
    {
        return $this->aggregateType;
    }
}
