<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\Action;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command\DeleteEntityCommand;

class ActionCommandResolver
{
    /**
     * @param Action[] $actions
     * @return ActionCommandResolver
     */
    public static function create(array $actions)
    {
        return new static($actions);
    }

    /**
     * @param Action[] $actions
     */
    private function __construct(array $actions)
    {
        $this->actions = $actions;
        $this->resolve();
    }

    /**
     * @var Action[]
     */
    private $actions;

    /**
     * @var Command\AbstractCommand[]
     */
    private $commands = [];

    /**
     * @return Command\AbstractCommand[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    private function resolve()
    {
        // root aggregate action is processed first
        foreach ($this->actions as $action) {
            switch ($action->getName()) {
                case 'delete':
                    $this->resolveDeleteAction($action);
                    break;
                // @todo Implement remaining actions
            }
        }
    }

    /**
     * @param Action $action
     */
    private function resolveDeleteAction(Action $action)
    {
        $this->commands[] = DeleteEntityCommand::create(
            $action->getContext(),
            $action->getSubject()
        );
    }
}
