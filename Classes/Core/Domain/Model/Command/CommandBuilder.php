<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Command;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Command;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateTypeTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\LocaleTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\NodeReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Object\WorkspaceTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class CommandBuilder implements Instantiable
{
    use AggregateReferenceTrait;
    use NodeReferenceTrait;
    use AggregateTypeTrait;
    use WorkspaceTrait;
    use LocaleTrait;

    const TYPE_CREATE = 'create';
    const TYPE_BRANCH = 'branch';
    const TYPE_BRANCH_AND_TRANSLATE = 'branch+translate';
    const TYPE_TRANSLATE = 'translate';
    const TYPE_MODIFY = 'modify';

    /**
     * @return CommandBuilder
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @var string
     */
    private $type;

    /**
     * @var AbstractCommand[]
     */
    private $commands = [];

    public function newCreateCommand(EntityReference $aggregateReference, EntityReference $nodeReference, int $workspaceId, string $locale)
    {
        $this->type = static::TYPE_CREATE;
        $this->aggregateReference = $aggregateReference;
        $this->nodeReference = $nodeReference;
        $this->workspaceId = $workspaceId;
        $this->locale = $locale;
        return $this;
    }

    public function newBranchCommand(EntityReference $aggregateReference, int $workspaceId)
    {
        $this->type = static::TYPE_BRANCH;
        $this->aggregateReference = $aggregateReference;
        $this->workspaceId = $workspaceId;
        return $this;
    }

    public function newBranchAndTranslateCommand(EntityReference $aggregateReference, int $workspaceId, string $locale)
    {
        $this->type = static::TYPE_BRANCH_AND_TRANSLATE;
        $this->aggregateReference = $aggregateReference;
        $this->workspaceId = $workspaceId;
        $this->locale = $locale;
        return $this;
    }

    public function newTranslateCommand(EntityReference $aggregateReference, string $locale)
    {
        $this->type = static::TYPE_TRANSLATE;
        $this->aggregateReference = $aggregateReference;
        $this->locale = $locale;
        return $this;
    }

    public function newModifyCommand(EntityReference $aggregateReference)
    {
        $this->type = static::TYPE_MODIFY;
        $this->aggregateReference = $aggregateReference;
        return $this;
    }

    public function addCommand(AbstractCommand $command)
    {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * @return null|AbstractCommand
     */
    public function build()
    {
        if (empty($this->commands)) {
            return null;
        }

        if ($this->type === static::TYPE_CREATE) {
            return CreateEntityBundleCommand::create(
                $this->aggregateReference,
                $this->nodeReference,
                $this->workspaceId,
                $this->locale,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_BRANCH) {
            return BranchEntityBundleCommand::create(
                $this->aggregateReference,
                $this->workspaceId,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_BRANCH_AND_TRANSLATE) {
            return BranchAndTranslateEntityBundleCommand::create(
                $this->aggregateReference,
                $this->workspaceId,
                $this->locale,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_TRANSLATE) {
            return TranslateEntityBundleCommand::create(
                $this->aggregateReference,
                $this->locale,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_MODIFY) {
            return ModifyEntityBundleCommand::create(
                $this->aggregateReference,
                $this->commands
            );
        }

        throw new \RuntimeException('Command cannot be build', 1473512582);
    }
}
