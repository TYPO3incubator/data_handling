<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Core\MetaModel;

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

use TYPO3\CMS\DataHandling\Core\MetaModel\Map;

class MapTest
{
    /**
     * @var Map
     */
    protected $subject;

    protected function setup()
    {
        $this->map = Map::instance();
    }

    protected function tearDown()
    {
        unset($this->map);
    }

    /**
     * @test
     */
    public function fileReferenceRelationsAreDefined()
    {
        var_dump('x');
    }
}