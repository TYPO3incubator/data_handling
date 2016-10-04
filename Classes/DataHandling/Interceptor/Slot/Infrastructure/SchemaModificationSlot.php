<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Interceptor\Slot\Infrastructure;

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

use TYPO3\CMS\DataHandling\Common;

class SchemaModificationSlot
{
    /**
     * @var string
     */
    protected $definitionTemplate;

    public function __construct()
    {
        $delimiter = str_repeat(PHP_EOL, 3);
        $this->definitionTemplate =
            $delimiter . implode(PHP_EOL, [
                'CREATE TABLE %s (',
                    Common::FIELD_UUID . ' varchar(36) DEFAULT NULL,',
                    Common::FIELD_REVISION . ' bigint(20) DEFAULT NULL',
                ');',
            ]) . $delimiter;
    }

    public function generate(array $sqlString): array
    {
        $sqlString[] = $this->buildDefinitions();
        return array('sqlString' => $sqlString);
    }

    protected function buildDefinitions(): string
    {
        $definitions = '';
        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            $definitions .= sprintf($this->definitionTemplate, $tableName);
        }
        return $definitions;
    }
}
