<?php
namespace TYPO3\CMS\DataHandling\Core\DataHandling\Resolver;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\EventSourcing\Core\Service\MetaModelService;

class ValueResolver extends AbstractResolver
{
    /**
     * @return ValueResolver
     */
    public static function instance()
    {
        return new static();
    }

    public function resolve(EntityReference $reference, array $rawValues): array
    {
        $values = [];

        foreach ($rawValues as $propertyName => $rawValue) {
            if (MetaModelService::instance()->isInvalidValueProperty($reference->getName(), $propertyName)) {
                continue;
            }

            $values[$propertyName] = $rawValue;
        }

        return $values;
    }
}