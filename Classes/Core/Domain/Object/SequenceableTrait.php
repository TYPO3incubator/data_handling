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

use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\AbstractSequence;

trait SequenceableTrait
{
    /**
     * @var AbstractSequence
     */
    protected $sequence;

    /**
     * @return null|AbstractSequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    public function setSequence(AbstractSequence $sequence)
    {
        $this->sequence = $sequence;
    }
}
