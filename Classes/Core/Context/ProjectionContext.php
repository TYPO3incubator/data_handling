<?php
namespace TYPO3\CMS\DataHandling\Core\Context;

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

class ProjectionContext
{
    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @var int[]
     */
    protected $languageChain;

    /**
     * @var mixed
     */
    protected $permissions;

    /**
     * @param int $workspaceId
     * @param int[]|null $languageChain
     * @return ProjectionContext
     */
    public static function instance(int $workspaceId, array $languageChain = null)
    {
        return GeneralUtility::makeInstance(
            ProjectionContext::class,
            $workspaceId,
            $languageChain
        );
    }

    public function __construct(int $workspaceId, array $languageChain = null)
    {
        if ($languageChain === null) {
            $languageChain = [0];
        }

        $this->workspaceId = $workspaceId;
        $this->languageChain = $languageChain;
    }

    public function getWorkspaceId()
    {
        return $this->workspaceId;
    }

    public function getLanguageChain()
    {
        return $this->languageChain;
    }
}
