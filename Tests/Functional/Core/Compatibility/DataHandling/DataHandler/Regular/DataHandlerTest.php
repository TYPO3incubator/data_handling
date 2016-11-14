<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\Core\Compatibility\DataHandling\DataHandler\Regular;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\DataSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\EventSourcing\Core\Database\ConnectionPool;
use TYPO3\CMS\EventSourcing\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command as GenericCommand;
use TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate;

class DataHandlerTest extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/event_sourcing',
        'typo3conf/ext/data_handling',
    ];

    protected function setup()
    {
        $currentValue = ConnectionPool::originAsDefault(true);
        parent::setUp();
        ConnectionPool::originAsDefault($currentValue);

        EventInitializationUpdate::instance()->performUpdate(
            $queriesReference = [],
            $messagesReference = []
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function createContents()
    {
        parent::createContents();
    }

    /**
     * @test
     */
    public function modifyContent()
    {
        parent::modifyContent();
    }

    /**
     * @test
     */
    public function deleteContent() {
        parent::deleteContent();
    }

    /**
     * @test
     */
    public function deleteLocalizedContentAndDeleteContent()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::deleteLocalizedContentAndDeleteContent();
    }

    /**
     * @test
     */
    public function copyContent()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::copyContent();
    }

    /**
     * @test
     */
    public function copyPasteContent()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::copyPasteContent();
    }

    /**
     * @test
     */
    public function localizeContent()
    {
        parent::localizeContent();
    }

    /**
     * @test
     */
    public function changeContentSorting()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::changeContentSorting();
    }

    /**
     * @test
     */
    public function moveContentToDifferentPage()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::moveContentToDifferentPage();
    }

    /**
     * @test
     */
    public function movePasteContentToDifferentPage()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::movePasteContentToDifferentPage();
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::moveContentToDifferentPageAndChangeSorting();
    }

    /**
     * @test
     */
    public function createPage()
    {
        parent::createPage();
    }

    /**
     * @test
     */
    public function modifyPage()
    {
        parent::modifyPage();
    }

    /**
     * @test
     */
    public function deletePage()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::deletePage();
    }

    /**
     * @test
     */
    public function copyPage()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::copyPage();
    }

    /**
     * @test
     */
    public function localizePage()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::localizePage();
    }

    /**
     * @test
     */
    public function changePageSorting()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::changePageSorting();
    }

    /**
     * @test
     */
    public function movePageToDifferentPage()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::movePageToDifferentPage();
    }

    /**
     * @test
     */
    public function movePageToDifferentPageAndChangeSorting()
    {
        $this->markTestSkipped('Not completely implemented');
        parent::movePageToDifferentPageAndChangeSorting();
    }

    /**
     * Override core methods
     */

    /**
     * @param string $tableName
     * @param array $record
     * @return Context
     */
    private function getContextForRecord(string $tableName, array $record)
    {
        $workspaceId = 0;
        if (MetaModelService::instance()->isWorkspaceAware($tableName)) {
            if (!empty($record['t3ver_wsid'])) {
                $workspaceId = (int)$record['t3ver_wsid'];
            }
        }
        return Context::create($workspaceId);
    }

    /**
     * @param string $dataSetName
     */
    protected function assertAssertionDataSet($dataSetName)
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);

        $dataSet = DataSet::read($fileName);
        $failMessages = [];

        foreach ($dataSet->getTableNames() as $tableName) {
            $sortingField = MetaModelService::instance()->getSortingField($tableName);

            $hasUidField = ($dataSet->getIdIndex($tableName) !== null);
            $records = $this->getAllRecords($tableName, $hasUidField);
            foreach ($dataSet->getElements($tableName) as $assertion) {
                // @todo Remove work-around to ignore sorting assertions
                if ($sortingField !== null && isset($assertion[$sortingField])) {
                    unset($assertion[$sortingField]);
                }

                $result = $this->assertInRecords($assertion, $records);
                if ($result === false) {
                    if ($hasUidField && empty($records[$assertion['uid']])) {
                        $failMessages[] = 'Record "' . $tableName . ':' . $assertion['uid'] . '" not found in database';
                        continue;
                    }
                    $recordIdentifier = $tableName . ($hasUidField ? ':' . $assertion['uid'] : '');
                    $additionalInformation = ($hasUidField ? $this->renderRecords($assertion, $records[$assertion['uid']]) : $this->arrayToString($assertion));
                    $failMessages[] = 'Assertion in data-set failed for "' . $recordIdentifier . '":' . LF . $additionalInformation;
                    // Unset failed asserted record
                    if ($hasUidField) {
                        unset($records[$assertion['uid']]);
                    }
                } else {
                    // Unset asserted record
                    unset($records[$result]);
                    // Increase assertion counter
                    $this->assertTrue($result !== false);
                }
            }
            if (!empty($records)) {
                foreach ($records as $record) {
                    $recordIdentifier = $tableName . ':' . $record['uid'];
                    $emptyAssertion = array_fill_keys($dataSet->getFields($tableName), '[none]');
                    $reducedRecord = array_intersect_key($record, $emptyAssertion);
                    $additionalInformation = ($hasUidField ? $this->renderRecords($emptyAssertion, $reducedRecord) : $this->arrayToString($reducedRecord));
                    $failMessages[] = 'Not asserted record found for "' . $recordIdentifier . '":' . LF . $additionalInformation;
                }
            }
        }

        if (!empty($failMessages)) {
            $this->fail(implode(LF, $failMessages));
        }
    }
}