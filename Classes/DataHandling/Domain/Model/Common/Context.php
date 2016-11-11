<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common;

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

use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\RepresentableAsArray;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\RepresentableAsLocalStorageName;

class Context implements RepresentableAsLocalStorageName, RepresentableAsArray
{
    public static function create(int $workspaceId = 0, int $languageId = 0)
    {
        return new static($workspaceId, $languageId);
    }

    /**
     * @param array $properties
     * @return Context
     */
    public static function fromArray(array $properties)
    {
        return static::create(
            $properties['workspace'],
            $properties['language']
        );
    }

    /**
     * @param int $workspaceId
     * @param int $languageId
     */
    private function __construct(int $workspaceId, int $languageId)
    {
        $this->workspaceId = $workspaceId;
        $this->languageId = $languageId;
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
     * @return array
     */
    public function toArray()
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
    public function asLocalStorageName()
    {
        return sprintf('workspace-%d', $this->workspaceId);
    }

    /**
     * @return int
     */
    public function getWorkspaceId()
    {
        return $this->workspaceId;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }
}
