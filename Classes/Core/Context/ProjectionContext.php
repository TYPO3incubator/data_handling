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
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class ProjectionContext implements Instantiable, Providable
{
    /**
     * @var ProjectionContext
     */
    static $projectionContext;

    /**
     * @param bool $force
     * @return ProjectionContext
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(static::$projectionContext)) {
            static::$projectionContext = static::instance();
        }
        return static::$projectionContext;
    }

    /**
     * @return ProjectionContext
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ProjectionContext::class);
    }

    /**
     * @var int
     */
    protected $workspaceId = 0;

    /**
     * @var int[]
     */
    protected $languageChain = null;

    /**
     * @var mixed
     * @todo Add meaning and handling
     */
    protected $permissions;

    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * @return string
     */
    public function __toString()
    {
        $stringRepresentation = 'workspace-' . $this->workspaceId;
        if (!empty($this->languageChain)) {
            $stringRepresentation .= '-languages';
            foreach ($this->languageChain as $languageId) {
                $stringRepresentation .= '-' . $languageId;
            }
        }
        return $stringRepresentation;
    }

    public function lock()
    {
        $this->locked = true;
    }

    public function getWorkspaceId()
    {
        return $this->workspaceId;
    }

    /**
     * @param int $workspaceId
     * @return ProjectionContext
     */
    public function setWorkspaceId(int $workspaceId)
    {
        $this->handleLock();
        $this->workspaceId = $workspaceId;
        return $this;
    }

    public function getLanguageChain()
    {
        return $this->languageChain;
    }

    /**
     * @param array|null $languageChain
     * @return ProjectionContext
     */
    public function setLanguageChain(array $languageChain = null)
    {
        $this->handleLock();
        $this->languageChain = $languageChain;
        return $this;
    }

    protected function handleLock()
    {
        if ($this->locked) {
            throw new \RuntimeException('ProjectionContext is locked', 1471178669);
        }
    }
}
