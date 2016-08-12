<?php
namespace TYPO3\CMS\DataHandling\Core\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageRestriction implements QueryRestrictionInterface
{
    /**
     * @param int $languageId
     * @return LanguageRestriction
     */
    public static function create(int $languageId)
    {
        return GeneralUtility::makeInstance(LanguageRestriction::class, $languageId);
    }

    /**
     * @var int
     */
    protected $languageId;

    /**
     * @param int $languageId
     */
    public function __construct(int $languageId)
    {
        $this->languageId = $languageId;
    }

    /**
     * @param array $queriedTables
     * @param ExpressionBuilder $expressionBuilder
     * @return CompositeExpression
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableName => $tableAlias) {
            $languageFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? null;
            if (!empty($languageFieldName)) {
                $tablePrefix = $tableAlias ?: $tableName;
                $constraints[] = $expressionBuilder->eq(
                    $tablePrefix . '.' . $languageFieldName,
                    $this->languageId
                );
            }
        }
        return $expressionBuilder->andX(...$constraints);
    }
}
