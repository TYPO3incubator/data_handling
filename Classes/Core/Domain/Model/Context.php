<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model;

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

class Context
{
    /**
     * @return Context
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param array $properties
     * @return Context
     */
    public static function fromArray(array $properties)
    {
        return static::instance()
            ->setWorkspaceId($properties['workspace'])
            ->setLanguageId($properties['language']);
    }

    /**
     * @var int
     */
    protected $workspaceId = 0;

    /**
     * @var int
     */
    protected $languageId = 0;

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('workspace-%d-language-%d', $this->workspaceId, $this->languageId);
    }

    public function __toArray()
    {
        return [
            'workspace' => $this->workspaceId,
            'language' => $this->languageId,
        ];
    }

    /**
     * @return string
     * @todo Extend for language as well
     */
    public function toLocalStorageName()
    {
        return sprintf('workspace-%d', $this->workspaceId);
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function setWorkspaceId(int $workspaceId): Context
    {
        $this->workspaceId = $workspaceId;
        return $this;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function setLanguageId(int $languageId): Context
    {
        $this->languageId = $languageId;
        return $this;
    }
}
