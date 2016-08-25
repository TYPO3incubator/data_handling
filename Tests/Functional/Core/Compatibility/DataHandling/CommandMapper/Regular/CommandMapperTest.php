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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\DataHandling\CommandManager;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta as GenericCommand;
use TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate;
use TYPO3\CMS\DataHandling\Tests\Framework\AssertionUtility;
use TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\CommandMapper\Fixtures\CommandManagerFixture;

class CommandMapperTest extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\AbstractActionTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/data_handling'
    ];

    /**
     * @var CommandManagerFixture
     */
    protected $commandManager;

    protected function setup()
    {
        parent::setUp();

        EventInitializationUpdate::instance()->performUpdate($queriesReference = [], $messagesReference = []);

        $this->commandManager = new CommandManagerFixture();

        GeneralUtility::setSingletonInstance(
            CommandManager::class,
            $this->commandManager
        );
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        unset($this->commandManager);
    }

    /**
     * @test
     */
    public function createContents()
    {
        parent::createContents();

        $this->assertHasCommands(
            [
                GenericCommand\CreateCommand::class => [
                    [ 'identity.name' => static::TABLE_Content, 'identity.uuid' => '@@UUID@@' ],
                    [ 'identity.name' => static::TABLE_Content, 'identity.uuid' => '@@UUID@@' ],
                ],
                GenericCommand\ChangeCommand::class => [
                    [ 'subject.name' => static::TABLE_Content, 'subject.uuid' => '@@UUID@@', 'data.header' => 'Testing #1' ],
                    [ 'subject.name' => static::TABLE_Content, 'subject.uuid' => '@@UUID@@', 'data.header' => 'Testing #2' ],
                ],
            ],
            $this->commandManager->getCommands()
        );
    }

    /**
     * @test
     */
    public function modifyContent()
    {
        parent::modifyContent();

        $this->assertHasCommands(
            [
                GenericCommand\ChangeCommand::class => [
                    [ 'subject.name' => static::TABLE_Content, 'subject.uuid' => '@@UUID@@', 'data.header' => 'Testing #1' ],
                ],
            ],
            $this->commandManager->getCommands()
        );
    }

    public function deleteContent() {
        parent::deleteContent();
    }

    public function deleteLocalizedContentAndDeleteContent()
    {
        parent::deleteLocalizedContentAndDeleteContent();
    }

    public function copyContent()
    {
        parent::copyContent();
    }

    public function copyPasteContent()
    {
        parent::copyPasteContent();
    }

    public function localizeContent()
    {
        parent::localizeContent();
    }

    public function changeContentSorting()
    {
        parent::changeContentSorting();
    }

    public function moveContentToDifferentPage()
    {
        parent::moveContentToDifferentPage();
    }

    public function movePasteContentToDifferentPage()
    {
        parent::movePasteContentToDifferentPage();
    }

    public function moveContentToDifferentPageAndChangeSorting()
    {
        parent::moveContentToDifferentPageAndChangeSorting();
    }

    public function createPage()
    {
        parent::createPage();
    }

    public function modifyPage()
    {
        parent::modifyPage();
    }

    public function deletePage()
    {
        parent::deletePage();
    }

    public function copyPage()
    {
        parent::copyPage();
    }

    public function localizePage()
    {
        parent::localizePage();
    }

    public function changePageSorting()
    {
        parent::changePageSorting();
    }

    public function movePageToDifferentPage()
    {
        parent::movePageToDifferentPage();
    }

    public function movePageToDifferentPageAndChangeSorting()
    {
        parent::movePageToDifferentPageAndChangeSorting();
    }

    protected function assertHasCommands(array $expectations, array $actualCommands)
    {
        $foundCommands = [];
        $expectedCommandCount = 0;
        foreach ($expectations as $commandClassName => $commandExpectationCollection) {
            foreach ($commandExpectationCollection as $commandExpectations) {
                $expectedCommandCount++;
                foreach ($actualCommands as $actualCommand) {
                    if (in_array($actualCommand, $foundCommands)) {
                        continue;
                    }
                    if (!is_a($actualCommand, $commandClassName)) {
                        continue;
                    }
                    if (AssertionUtility::matchesExpectations($commandExpectations, $actualCommand)) {
                        $foundCommands[] = $actualCommand;
                    }
                }
            }
        }
        $this->assertEquals($expectedCommandCount, count($foundCommands), 'Could not assert all commands');
    }
}