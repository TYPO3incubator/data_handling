<?php
namespace TYPO3\CMS\DataHandling\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Tca\TcaCommand;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Tca\TcaCommandEntityBehavior;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Tca\TcaCommandManager;
use TYPO3\CMS\DataHandling\Core\MetaModel\ActiveRelation;
use TYPO3\CMS\DataHandling\Core\MetaModel\Map;

/**
 * Modifies TCA settings depending on current state
 * and TcaCommand definitions for projections and relations.
 */
class TcaCommandModifier implements FormDataProviderInterface
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var TcaCommand
     */
    private $tcaCommand;

    /**
     * @var array
     */
    private $formEngineResult;

    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $this->tableName = $result['tableName'];

        $tcaCommandManager = TcaCommandManager::provide();
        if (!$tcaCommandManager->has($this->tableName)) {
            return $result;
        }

        $this->formEngineResult = $result;
        $this->tcaCommand = $tcaCommandManager->for($this->tableName);

        if ($result['command'] === 'new') {
            $this->handleNewAction();
        }
        if ($result['command'] === 'edit') {
            $this->handleEditAction();
        }

        return $this->formEngineResult;
    }

    private function handleNewAction()
    {
        $behavior = $this->tcaCommand->create();
        $this->handleBehavior($behavior);
    }

    private function handleEditAction()
    {
        $behavior = $this->tcaCommand->modify();
        $this->handleBehavior($behavior);
    }

    /**
     * @param TcaCommandEntityBehavior $behavior
     */
    private function handleBehavior(TcaCommandEntityBehavior $behavior)
    {
        if (!$behavior->isAllowed()) {
            $this->definedReadOnlyFields(
                array_keys($this->formEngineResult['processedTca']['columns'])
            );
            return;
        }

        $missingNames = array_diff(
            array_keys($this->formEngineResult['processedTca']['columns']),
            array_keys($behavior->getProperties())
        );

        $this->definedReadOnlyFields($missingNames);

        foreach ($behavior->getProperties() as $name => $instruction) {
            if ($instruction instanceof \Closure) {
                $this->defineDefaultValue($name, $instruction());
            } elseif (is_callable($instruction)) {
                $this->defineDefaultValue($name, call_user_func($instruction));
            }
        }

        $properties = Map::provide()->getSchema($this->tableName)->getProperties();
        foreach ($properties as $property) {
            foreach ($property->getActiveRelations() as $relation) {
                $this->handleRelation($relation, $behavior);
            }
        }
    }

    /**
     * @param ActiveRelation $relation
     * @param TcaCommandEntityBehavior $behavior
     */
    private function handleRelation(ActiveRelation $relation, TcaCommandEntityBehavior $behavior)
    {
        $propertyName = $relation->getProperty()->getName();
        $type = $this->formEngineResult['processedTca']['columns'][$propertyName]['config']['type'];

        // @todo group & select are missing here

        if ($type === 'inline') {
            if (!$behavior->hasRelation($propertyName)) {
                $this->formEngineResult['processedTca']['columns'][$propertyName]['config']['appearance']['enabledControls'] = [];
                return;
            }

            $relationBehavior = $behavior->forRelation($propertyName);
            $referenceTableBehavior = TcaCommandManager::provide()->for($relation->getTo()->getName());

            $enabledControls = [];
            $currentEnableControls = null;
            if (isset($this->formEngineResult['processedTca']['columns'][$propertyName]['config']['appearance']['enabledControls'])) {
                $currentEnableControls = $this->formEngineResult['processedTca']['columns'][$propertyName]['config']['appearance']['enabledControls'];
            }

            // @todo "localize" is missing here

            if ($relationBehavior->isAttachAllowed() && $referenceTableBehavior->create()->isAllowed()) {
                $enabledControls['new'] = true;
            }
            if ($relationBehavior->isRemoveAllowed() && $referenceTableBehavior->delete()->isAllowed()) {
                $enabledControls['delete'] = true;
            }
            if ($relationBehavior->isOrderAllowed()) {
                $enabledControls['sort'] = true;
                $enabledControls['dragdrop'] = true;
            }
            if ($referenceTableBehavior->disable()->isAllowed()) {
                $enabledControls['delete'] = true;
            }

            if ($currentEnableControls !== null) {
                $enabledControls = array_intersect_assoc(
                    $enabledControls,
                    $currentEnableControls
                );
            }

            $this->formEngineResult['processedTca']['columns'][$propertyName]['config']['appearance']['enabledControls'] = $enabledControls;
        }
    }

    /**
     * @param array $names
     */
    private function definedReadOnlyFields(array $names)
    {
        foreach ($names as $name) {
            $this->formEngineResult['processedTca']['columns'][$name]['config']['readOnly'] = true;
        }
    }

    /**
     * @param string $name
     * @param string $defaultValue
     */
    private function defineDefaultValue(string $name, string $defaultValue)
    {
        $this->formEngineResult['processedTca']['columns'][$name]['config']['default'] = $defaultValue;
    }
}
