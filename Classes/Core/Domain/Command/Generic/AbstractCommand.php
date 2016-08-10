<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Command\Generic;

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

use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;

abstract class AbstractCommand extends \TYPO3\CMS\DataHandling\Core\Domain\Command\AbstractCommand
{
    /**
     * Subject the command is applied to.
     *
     * @var EntityReference
     */
    protected $subject;

    /**
     * @return null|EntityReference
     */
    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject(EntityReference $subject): AbstractCommand
    {
        $this->subject = $subject;
        return $this;
    }
}
