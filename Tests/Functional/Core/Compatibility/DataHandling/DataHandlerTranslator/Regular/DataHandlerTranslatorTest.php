<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\DataHandlerTranslator\Regular;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\EventSourcing\Core\Database\ConnectionPool;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Command\CommandBus;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate;
use TYPO3\CMS\EventSourcing\Tests\Framework\AssertionUtility;
use TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\DataHandlerTranslator\Fixtures\CommandHandlerFixture;

class DataHandlerTranslatorTest extends AbstractActionTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/event_sourcing',
        'typo3conf/ext/data_handling',
    ];

    /**
     * @var CommandHandlerFixture
     */
    private $commandHandler;

    protected function setup()
    {
        ConnectionPool::originAsDefault(true);
        parent::setUp();
        ConnectionPool::originAsDefault(false);

        EventInitializationUpdate::instance()->performUpdate(
            $queriesReference = [],
            $messagesReference = []
        );

        $this->commandHandler = new CommandHandlerFixture();

        CommandBus::provide(true)->addHandlerBundle(
            $this->commandHandler,
            [
                Command\CreateEntityBundleCommand::class,
                Command\BranchEntityBundleCommand::class,
                Command\BranchAndTranslateEntityBundleCommand::class,
                Command\TranslateEntityBundleCommand::class,
                Command\ModifyEntityBundleCommand::class,
                Command\DeleteEntityCommand::class,
            ]
        );
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        unset($this->commandHandler);
        CommandBus::provide(true);
    }

    /**
     * @test
     */
    public function createContents()
    {
        parent::createContents();

        $this->assertHasCommands(
            [
                Command\CreateEntityBundleCommand::class => [
                    [
                        'aggregateReference.name' => static::TABLE_Content,
                        'aggregateReference.uuid' => '@@UUID@@',
                        'nodeReference.name' => static::TABLE_Page,
                        'nodeReference.uuid' => '@@UUID@@',
                    ],
                    [
                        'aggregateReference.name' => static::TABLE_Content,
                        'aggregateReference.uuid' => '@@UUID@@',
                        'nodeReference.name' => static::TABLE_Page,
                        'nodeReference.uuid' => '@@UUID@@',
                    ],
                ],
            ],
            $this->commandHandler->getCommands()
        );

        $this->assertHasCommands(
            [
                Command\ModifyEntityCommand::class => [
                    [
                        'aggregateReference.name' => static::TABLE_Content,
                        'aggregateReference.uuid' => '@@UUID@@',
                        'data.header' => 'Testing #1',
                    ],
                    [
                        'aggregateReference.name' => static::TABLE_Content,
                        'aggregateReference.uuid' => '@@UUID@@',
                        'data.header' => 'Testing #2',
                    ],
                ],
            ],
            $this->commandHandler->getBundleCommands(
                Command\CreateEntityBundleCommand::class
            )
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
                Command\ModifyEntityBundleCommand::class => [
                    [
                        'aggregateReference.name' => static::TABLE_Content,
                        'aggregateReference.uuid' => '@@UUID@@',
                    ]
                ],
            ],
            $this->commandHandler->getCommands()
        );

        $this->assertHasCommands(
            [
                Command\ModifyEntityCommand::class => [
                    [
                        'aggregateReference.name' => static::TABLE_Content,
                        'aggregateReference.uuid' => '@@UUID@@',
                        'data.header' => 'Testing #1',
                    ],
                ],
            ],
            $this->commandHandler->getBundleCommands(
                Command\ModifyEntityBundleCommand::class
            )
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