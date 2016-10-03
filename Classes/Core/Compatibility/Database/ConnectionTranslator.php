<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\Database;

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

use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;

class ConnectionTranslator
{
    public static function instance()
    {
        return new static();
    }

    public function createEntity(EntityReference $reference, array $data)
    {
        // @todo Implement command translation
    }

    public function modifyEntity(EntityReference $reference, array $data)
    {
        // @todo Implement command translation
    }

    public function purgeEntity(EntityReference $reference)
    {
        // @todo Implement command translation
    }
}
