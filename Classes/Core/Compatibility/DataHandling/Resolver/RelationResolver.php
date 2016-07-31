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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\CommandMapperScope;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver\AbstractResolver;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\Domain\Object\Record;

class RelationResolver extends AbstractResolver
{
    /**
     * @return RelationResolver
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(RelationResolver::class);
    }

    /**
     * @var CommandMapperScope
     */
    protected $scope;

    public function setScope(CommandMapperScope $scope): RelationResolver
    {
        $this->scope = $scope;
    }

    /**
     * @param Record\Reference $reference
     * @param array $rawValues
     * @return Record\Reference[][]
     */
    public function resolve(Record\Reference $reference, array $rawValues): array {
        $relations = [];

        foreach ($rawValues as $propertyName => $rawValue) {
            if (!MetaModelService::instance()->isRelationProperty($reference->getName(), $propertyName)) {
                continue;
            }

            $relationReferences = [];
            $pointers = GeneralUtility::trimExplode(',', $rawValue, true);

            foreach ($pointers as $pointer) {
                $relationReference = Record\Reference::instance();
                $pointerParts = explode('_', $pointer);
                $pointerPartsCount = count($pointerParts);

                if ($pointerPartsCount > 1 && MathUtility::canBeInterpretedAsInteger($pointerParts[$pointerPartsCount-1])) {
                    $relationReference->setUid(array_pop($pointerParts))->setName(implode('_', $pointerParts));
                } elseif (MathUtility::canBeInterpretedAsInteger($pointer)) {
                    $configuration = $GLOBALS['TCA'][$reference->getName()]['columns'][$propertyName]['config'];
                    if ($configuration['type'] === 'group') {
                        $relationName = $configuration['allowed'];
                    } elseif ($configuration['type'] === 'select' || $configuration['type'] === 'inline') {
                        $relationName = $configuration['foreign_table'];
                    } else {
                        throw new \UnexpectedValueException('Reference name cannot be resolved', 1469968438);
                    }
                    $relationReference->setName($relationName)->setUid($pointer);
                } elseif (isset($this->scope->newChangesMap[$pointer])) {
                    $relationReference = $this->scope->newChangesMap[$pointer]->getCurrentState()->getReference();
                } else {
                    throw new \UnexpectedValueException('Reference cannot be resolved', 1469968439);
                }

                $relationReferences[] = $relationReference;
            }

            $relations[$propertyName] = $relationReferences;
        }

        return $relations;
    }
}
