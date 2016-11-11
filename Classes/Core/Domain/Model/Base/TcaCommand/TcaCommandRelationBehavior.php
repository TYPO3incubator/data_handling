<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Base\TcaCommand;

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

use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\Instantiable;

final class TcaCommandRelationBehavior implements Instantiable
{
    public static function instance()
    {
        return new static();
    }

    /**
     * @var bool
     */
    private $attachAllowed = false;

    /**
     * @var bool
     */
    private $removeAllowed = false;

    /**
     * @var bool
     */
    private $orderAllowed = false;

    /**
     * @return boolean
     */
    public function isAttachAllowed()
    {
        return $this->attachAllowed;
    }

    /**
     * @param boolean $attachAllowed
     * @return static
     */
    public function setAttachAllowed(bool $attachAllowed)
    {
        $this->attachAllowed = $attachAllowed;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isRemoveAllowed()
    {
        return $this->removeAllowed;
    }

    /**
     * @param boolean $removeAllowed
     * @return static
     */
    public function setRemoveAllowed(bool $removeAllowed)
    {
        $this->removeAllowed = $removeAllowed;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOrderAllowed()
    {
        return $this->orderAllowed;
    }

    /**
     * @param boolean $orderAllowed
     * @return static
     */
    public function setOrderAllowed(bool $orderAllowed)
    {
        $this->orderAllowed = $orderAllowed;
        return $this;
    }
}
