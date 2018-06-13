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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\EventSourcing\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;

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
                case 'move':
                    // @todo
                    break;
                case 'copy':
                    // @todo
                    break;
                case 'localize':
                    $this->resolveLocalizeAction($action);
                    break;
                case 'copyToLanguage':
                    // @todo
                    break;
                case 'inlineLocalizeSynchronize':
                    // @todo
                    break;
                case 'delete':
                    $this->resolveDeleteAction($action);
                    break;
                case 'undelete':
                    $this->resolveRecoverAction($action);
                    break;
            }
        }
    }

    /**
     * @param Action $action
     */
    private function resolveDeleteAction(Action $action)
    {
        $sourceState = $action->getSourceState();
        $deletedFieldName = MetaModelService::instance()
            ->getDeletedFieldName($sourceState->getSubject()->getName());

        if ($deletedFieldName !== null) {
            $this->commands[] = Command\DeleteEntityCommand::create(
                $action->getTargetContext(),
                $sourceState->getSubject()
            );
        } else {
            $this->commands[] = Command\PurgeEntityCommand::create(
                $action->getTargetContext(),
                $sourceState->getSubject()
            );
        }
    }

    private function resolveRecoverAction(Action $action)
    {
        $sourceState = $action->getSourceState();
        $this->commands[] = Command\RecoverEntityCommand::create(
            $action->getTargetContext(),
            $sourceState->getSubject()
        );
    }

    /**
     * @param Action $action
     */
    private function resolveLocalizeAction(Action $action)
    {
        $sourceState = $action->getSourceState();
        $metaModelService = MetaModelService::instance();

        $languageId = (int)$action->getPayload();
        $context = Context::create(
            $action->getTargetContext()->getWorkspaceId(),
            $languageId
        );

        $languageEntity = $this->fetchLanguageEntity($languageId);
        $languageTitle = $languageEntity->getValue('title');

        $commands = [];
        $values = [];
        foreach ($action->getSourceState()->getValues()
                 as $propertyName => $propertyValue
        ) {
            if (!$metaModelService->shallPrefixTitleOnTranslation(
                $sourceState->getSubject()->getName(),
                $propertyName
            )) {
                continue;
            }

            // @todo consider $TSConfig['translateToMessage']
            $values[$propertyName] =
                '[Translate to ' . $languageTitle . ':] ' . $propertyValue;
        }

        if (!empty($values)) {
            $commands[] = Command\ModifyEntityCommand::create(
                $context,
                EntityReference::instance(),
                $values
            );
        }

        $this->commands[] = Command\TranslateEntityBundleCommand::create(
            $context,
            $sourceState->getSubject(),
            $commands
        );
    }

    /**
     * @param int $languageId
     * @return GenericEntity
     */
    private function fetchLanguageEntity(int $languageId)
    {
        $languageReference = EntityReference::create('sys_language')
            ->setUid($languageId);
        $languageReference->setUuid(
            UuidUtility::fetchUuid($languageReference)
        );
        $languageEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference($languageReference);
        return $languageEntity;
    }
}
