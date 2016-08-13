<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object;

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
        return GeneralUtility::makeInstance(Context::class);
    }

    /**
     * @var int
     */
    protected $workspace = 0;

    /**
     * @var int
     */
    protected $language = 0;

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('workspace-%d-language-%d', $this->workspace, $this->language);
    }

    public function getWorkspace(): int
    {
        return $this->workspace;
    }

    public function setWorkspace(int $workspace): Context
    {
        $this->workspace = $workspace;
        return $this;
    }

    public function getLanguage(): int
    {
        return $this->language;
    }

    public function setLanguage(int $language): Context
    {
        $this->language = $language;
        return $this;
    }
}
