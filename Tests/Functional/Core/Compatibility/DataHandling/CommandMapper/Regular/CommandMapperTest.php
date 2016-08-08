<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\Regular;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\AbstractActionTestCase;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\CommandMapper;
use TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\CommandMapper\Fixtures\CommandMapperFixture;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class CommandMapperTest extends AbstractActionTestCase
{
    /**
     * @var CommandMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setup()
    {
        parent::setUp();

        $this->subject = new CommandMapperFixture();

        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
            CommandMapper::class,
            $this->subject
        );
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function createContents()
    {
        parent::createContents();
        var_dump($this->subject->getCommands());
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