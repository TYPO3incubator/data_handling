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
use TYPO3\CMS\DataHandling\Core\MetaModel\Manager;

class ManagerTest extends FunctionalTestCase
{
    /**
     * @var Manager
     */
    protected $subject;

    protected function setup()
    {
        parent::setUp();
        $this->subject = Manager::instance();
    }

    protected function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function sameMapInstanceIsReturned()
    {
        $expectedMap = $this->subject->get();
        $actualMap = $this->subject->get();

        $this->assertSame($expectedMap, $actualMap);
    }

    /**
     * @test
     */
    public function differentMapInstanceIsReturnedOnPurgeCommand()
    {
        $expectedMap = $this->subject->get();

        $this->subject->purge();
        $actualMap = $this->subject->get();

        $this->assertNotSame($expectedMap, $actualMap);
    }

    /**
     * @test
     */
    public function differentMapInstanceIsReturnedOnConfigurationChanges()
    {
        $expectedMap = $this->subject->get();

        $propertyName = uniqid('property');
        $GLOBALS['TCA']['tt_content']['columns'][$propertyName]['config']['type'] = 'none';
        $actualMap = $this->subject->get();

        $this->assertNotSame($expectedMap, $actualMap);
        $this->assertNotNull($actualMap->getSchema('tt_content')->getProperty($propertyName));
    }
}