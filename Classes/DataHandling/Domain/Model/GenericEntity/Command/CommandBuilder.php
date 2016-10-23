<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;

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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\AggregateReferenceTrait;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\ContextualTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\NodeReferenceTrait;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Instantiable;

class CommandBuilder implements Instantiable
{
    use AggregateReferenceTrait;
    use NodeReferenceTrait;
    use ContextualTrait;

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
        return new static();
    }

    /**
     * @var string
     */
    private $type;

    /**
     * @var AbstractCommand[]
     */
    private $commands = [];

    public function newCreateCommand(Context $context, EntityReference $aggregateReference, EntityReference $nodeReference)
    {
        $this->type = static::TYPE_CREATE;
        $this->context = $context;
        $this->aggregateReference = $aggregateReference;
        $this->nodeReference = $nodeReference;
        return $this;
    }

    public function newBranchCommand(Context $context, EntityReference $aggregateReference)
    {
        $this->type = static::TYPE_BRANCH;
        $this->context = $context;
        $this->aggregateReference = $aggregateReference;
        return $this;
    }

    public function newBranchAndTranslateCommand(Context $context, EntityReference $aggregateReference)
    {
        $this->type = static::TYPE_BRANCH_AND_TRANSLATE;
        $this->context = $context;
        $this->aggregateReference = $aggregateReference;
        return $this;
    }

    public function newTranslateCommand(Context $context, EntityReference $aggregateReference)
    {
        $this->type = static::TYPE_TRANSLATE;
        $this->context = $context;
        $this->aggregateReference = $aggregateReference;
        return $this;
    }

    public function newModifyCommand(Context $context, EntityReference $aggregateReference)
    {
        $this->type = static::TYPE_MODIFY;
        $this->context = $context;
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
                $this->context,
                $this->aggregateReference,
                $this->nodeReference,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_BRANCH) {
            return BranchEntityBundleCommand::create(
                $this->context,
                $this->aggregateReference,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_BRANCH_AND_TRANSLATE) {
            return BranchAndTranslateEntityBundleCommand::create(
                $this->context,
                $this->aggregateReference,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_TRANSLATE) {
            return TranslateEntityBundleCommand::create(
                $this->context,
                $this->aggregateReference,
                $this->commands
            );
        }
        if ($this->type === static::TYPE_MODIFY) {
            return ModifyEntityBundleCommand::create(
                $this->context,
                $this->aggregateReference,
                $this->commands
            );
        }

        throw new \RuntimeException('Command cannot be build', 1473512582);
    }
}
