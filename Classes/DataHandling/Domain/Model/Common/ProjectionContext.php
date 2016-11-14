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

use TYPO3\CMS\DataHandling\Core\Database\LocalStoragePool;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\Instantiable;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\Providable;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\RepresentableAsLocalStorageName;

class ProjectionContext implements Instantiable, Providable, RepresentableAsLocalStorageName
{
    /**
     * @var ProjectionContext
     */
    private static $projectionContext;

    /**
     * @param bool $force
     * @return ProjectionContext
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(self::$projectionContext)) {
            self::$projectionContext = static::instance();
        }
        return self::$projectionContext;
    }

    /**
     * @return ProjectionContext
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param int $workspaceId
     * @param array $languageChain
     * @return string
     */
    public static function asString(int $workspaceId, array $languageChain = [])
    {
        $stringRepresentation = 'workspace-' . $workspaceId;
        if (!empty($languageChain)) {
            $stringRepresentation .= '-languages';
            foreach ($languageChain as $languageId) {
                $stringRepresentation .= '-' . $languageId;
            }
        }
        return $stringRepresentation;
    }

    public function enforceLocalStorage()
    {
        LocalStoragePool::instance()->setAsDefault(
            $this->asLocalStorageName()
        );
    }

    /**
     * @var int
     */
    private $workspaceId = 0;

    /**
     * @var int[]
     */
    private $languageChain = [];

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @return string
     */
    public function asLocalStorageName()
    {
        return static::asString(
            $this->workspaceId,
            $this->languageChain
        );
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

    /**
     * @return \int[]
     */
    public function getLanguageChain()
    {
        return $this->languageChain;
    }

    /**
     * @param array|null $languageChain
     * @return ProjectionContext
     */
    public function setLanguageChain(array $languageChain = [])
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
