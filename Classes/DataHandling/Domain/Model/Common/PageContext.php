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

use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\Providable;

class PageContext implements Providable
{
    /**
     * @var PageContext
     */
    private static $instance;

    /**
     * @param bool $force
     * @return PageContext
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    /**
     * @var int
     */
    private $pageId;

    public function getPageId()
    {
        if (!isset($this->pageId) && !empty($GLOBALS['TSFE']->id)) {
            return (int)$GLOBALS['TSFE']->id;
        }
        return $this->pageId;
    }

    /**
     * @param int $pageId
     */
    public function setPageId(int $pageId)
    {
        $this->pageId = $pageId;
    }
}
