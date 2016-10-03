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

use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\DataHandling\Core\Context\ProjectionContext;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;
use TYPO3\CMS\DataHandling\Core\MetaModel\ActiveRelation;
use TYPO3\CMS\DataHandling\Core\MetaModel\RelationMap;
use TYPO3\CMS\DataHandling\Core\MetaModel\PassiveRelation;
use TYPO3\CMS\DataHandling\Core\MetaModel\Relational;
use TYPO3\CMS\DataHandling\Tests\Framework\AssertionUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class ConnectionPoolTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/data_handling',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    /**
     * @var ConnectionPool
     */
    protected $subject;

    protected function setup()
    {
        parent::setUp();
        $this->subject = ConnectionPool::instance();
    }

    protected function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function localStorageIsCreated()
    {
        $projectionContext = ProjectionContext::instance();
        $this->subject->provideLocalStorageConnection($projectionContext->asLocalStorageName());

        $basePath = rtrim($this->instancePath, '/') . '/typo3temp/var/LocalStorage';

        $this->assertFileExists($basePath . '/.htaccess');
        $this->assertFileExists($basePath . '/workspace-0.sqlite');
    }
}