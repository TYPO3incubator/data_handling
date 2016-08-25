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
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\CommandMapperScope;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\ValueConverter;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver\AbstractResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

class ValueResolver extends AbstractResolver
{
    /**
     * @return ValueResolver
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ValueResolver::class);
    }

    /**
     * @var CommandMapperScope
     */
    protected $scope;

    public function setScope(CommandMapperScope $scope): RelationResolver
    {
        $this->scope = $scope;
    }

    public function resolve(EntityReference $reference, array $rawValues): array
    {
        $values = [];

        foreach ($rawValues as $propertyName => $rawValue) {
            if (MetaModelService::instance()->isInvalidValueProperty($reference->getName(), $propertyName)) {
                continue;
            }

            $propertyReference = PropertyReference::instance();
            $propertyReference->setEntityReference($reference);
            $propertyReference->setName($propertyName);
            $values[$propertyName] = ValueConverter::instance()->convert($propertyReference, $rawValue);
        }

        return $values;
    }
}
