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
use TYPO3\CMS\DataHandling\Core\MetaModel\ActiveRelation;
use TYPO3\CMS\DataHandling\Core\MetaModel\Map;
use TYPO3\CMS\DataHandling\Core\MetaModel\PassiveRelation;
use TYPO3\CMS\DataHandling\Core\MetaModel\Relational;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class MapTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial'
    ];

    /**
     * @var Map
     */
    protected $subject;

    protected function setup()
    {
        parent::setUp();
        $this->subject = Map::instance();
    }

    protected function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function invalidSchemaReturnsNull()
    {
        $this->assertNull(
            $this->subject->getSchema(uniqid('anything'))
        );
    }

    /**
     * @test
     */
    public function invalidPropertyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getSchema('tt_content')->getProperty(uniqid('anything'))
        );
    }

    /**
     * @test
     */
    public function languageRelationsAreDefined()
    {
        $this->assertHasActiveRelation(
            [
                'property.schema.name' => 'tt_content',
                'property.name' => 'sys_language_uid',
                'to.name' => 'sys_language',
            ],
            $this->subject->getSchema('tt_content')->getProperty('sys_language_uid')->getActiveRelations()
        );
        foreach ($this->subject->getSchema('sys_language')->getProperties() as $property) {
            $this->assertEmpty(
                $property->getPassiveRelations()
            );
        }
    }

    /**
     * @test
     */
    public function fileReferenceRelationsAreDefined()
    {
        $this->assertHasActiveRelation(
            [
                'property.schema.name' => 'tt_content',
                'property.name' => 'image',
                'to.name' => 'sys_file_reference',
            ],
            $this->subject->getSchema('tt_content')->getProperty('image')->getActiveRelations()
        );
        $this->assertHasPassiveRelation(
            [
                'property.schema.name' => 'sys_file_reference',
                'property.name' => 'uid_foreign',
                'from.schema.name' => 'tt_content',
                'from.name' => 'image',
            ],
            $this->subject->getSchema('sys_file_reference')->getProperty('uid_foreign')->getPassiveRelations()
        );
    }

    /**
     * @test
     */
    public function oneToManyCommaSeparatedValueRelationsAreDefined()
    {
        $this->assertHasActiveRelation(
            [
                'property.schema.name' => 'tx_irretutorial_1ncsv_hotel',
                'property.name' => 'offers',
                'to.name' => 'tx_irretutorial_1ncsv_offer',
            ],
            $this->subject->getSchema('tx_irretutorial_1ncsv_hotel')->getProperty('offers')->getActiveRelations()
        );
        foreach ($this->subject->getSchema('tx_irretutorial_1ncsv_offer')->getProperties() as $property) {
            $this->assertEmpty(
                $property->getPassiveRelations()
            );
        }
    }

    /**
     * @test
     */
    public function oneToManyForeignFieldRelationsAreDefined()
    {
        $this->assertHasActiveRelation(
            [
                'property.schema.name' => 'tx_irretutorial_1nff_hotel',
                'property.name' => 'offers',
                'to.name' => 'tx_irretutorial_1nff_offer',
            ],
            $this->subject->getSchema('tx_irretutorial_1nff_hotel')->getProperty('offers')->getActiveRelations()
        );
        $this->assertHasPassiveRelation(
            [
                'property.schema.name' => 'tx_irretutorial_1nff_offer',
                'property.name' => 'parentid',
                'from.schema.name' => 'tx_irretutorial_1nff_hotel',
                'from.name' => 'offers',
            ],
            $this->subject->getSchema('tx_irretutorial_1nff_offer')->getProperty('parentid')->getPassiveRelations()
        );
    }

    /**
     * @param array $expectations
     * @param Relational[] $actualRelations
     */
    protected function assertHasActiveRelation(array $expectations, array $actualRelations)
    {
        $found = false;
        foreach ($actualRelations as $actualRelation) {
            if (!($actualRelation instanceof ActiveRelation)) {
                continue;
            }
            if (!$this->matchesExpectations($expectations, $actualRelation)) {
                continue;
            }
            $found = true;
            break;
        }
        $this->assertTrue($found, 'No relation found that matches expectations.');
    }

    /**
     * @param array $expectations
     * @param Relational[] $actualRelations
     */
    protected function assertHasPassiveRelation(array $expectations, array $actualRelations)
    {
        $found = false;
        foreach ($actualRelations as $actualRelation) {
            if (!($actualRelation instanceof PassiveRelation)) {
                continue;
            }
            if (!$this->matchesExpectations($expectations, $actualRelation)) {
                continue;
            }
            $found = true;
            break;
        }
        $this->assertTrue($found, 'No relation found that matches expectations.');
    }

    /**
     * @param array $expectations
     * @param object|array|\ArrayAccess $subject
     * @return bool
     */
    protected function matchesExpectations(array $expectations, $subject): bool
    {
        $matches = 0;
        foreach ($expectations as $expectationPath => $expectationValue) {
            if (ObjectAccess::getPropertyPath($subject, $expectationPath) === $expectationValue) {
                $matches++;
            }
        }
        return ($matches === count($expectations));
    }
}