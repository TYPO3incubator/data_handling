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
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver\AbstractResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\EventSourcing\Core\Service\MetaModelService;

class RelationResolver extends AbstractResolver
{
    /**
     * @return RelationResolver
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param EntityReference $reference
     * @param array $rawValues
     * @return PropertyReference[]
     */
    public function resolve(EntityReference $reference, array $rawValues): array {
        $relations = [];

        foreach ($rawValues as $propertyName => $rawValue) {
            if (!MetaModelService::instance()->isRelationProperty($reference->getName(), $propertyName)) {
                continue;
            }

            $pointers = GeneralUtility::trimExplode(',', $rawValue, true);

            foreach ($pointers as $pointer) {
                $entityReference = EntityReference::instance();
                $pointerParts = explode('_', $pointer);
                $pointerPartsCount = count($pointerParts);

                if ($pointerPartsCount > 1 && MathUtility::canBeInterpretedAsInteger($pointerParts[$pointerPartsCount-1])) {
                    $entityReference
                        ->setUid(array_pop($pointerParts))
                        ->setName(implode('_', $pointerParts));
                    $entityReference->setUuid(
                        $this->fetchUuid($entityReference)
                    );
                } elseif (MathUtility::canBeInterpretedAsInteger($pointer)) {
                    $configuration = $GLOBALS['TCA'][$reference->getName()]['columns'][$propertyName]['config'];
                    if (($configuration['special'] ?? null) === 'languages') {
                        $relationName = 'sys_language';
                    } elseif ($configuration['type'] === 'group') {
                        $relationName = $configuration['allowed'];
                    } elseif ($configuration['type'] === 'select' || $configuration['type'] === 'inline') {
                        $relationName = $configuration['foreign_table'];
                    } else {
                        throw new \UnexpectedValueException('EntityReference name cannot be resolved', 1469968438);
                    }
                    $entityReference
                        ->setName($relationName)
                        ->setUid($pointer);
                    $entityReference->setUuid(
                        $this->fetchUuid($entityReference)
                    );
                } elseif (isset($this->scope->acceptedNewEntityReferences[$pointer])) {
                    $entityReference = $this->scope->acceptedNewEntityReferences[$pointer];
                // @todo Remove work-around for a FormEngine bug
                } elseif (!isset($this->scope->ignoredNewEntityReferences[$pointer])) {
                    continue;
                } else {
                    // If this exception is thrown, most probably the entity is
                    // not configured to be handled for event sourcing,
                    // see $TCA[<table-name>]['ctrl']['eventSourcing']
                    throw new \UnexpectedValueException('EntityReference cannot be resolved', 1469968439);
                }

                $relations[] = PropertyReference::instance()
                    ->setEntityReference($entityReference)
                    ->setName($propertyName);
            }
        }

        return $relations;
    }
}
