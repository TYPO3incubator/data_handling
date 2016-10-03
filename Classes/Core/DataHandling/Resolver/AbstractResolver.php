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

use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\DataHandlerScope;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;

abstract class AbstractResolver
{
    /**
     * @var
     * @todo Assign and use context
     */
    protected $context;

    /**
     * @var DataHandlerScope
     */
    protected $scope;

    /**
     * @param DataHandlerScope $scope
     * @return static
     */
    public function setScope(DataHandlerScope $scope)
    {
        $this->scope = $scope;
        return $this;
    }

    abstract public function resolve(EntityReference $reference, array $rawValues): array;

    /**
     * @param EntityReference $reference
     * @return string
     */
    protected function fetchUuid(EntityReference $reference)
    {
        return UuidUtility::fetchUuid($reference);
    }
}