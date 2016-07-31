<?php
namespace TYPO3\CMS\DataHandling\Domain\Object\Record;

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

class Change
{
    /**
     * @return Change
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Change::class);
    }

    /**
     * @var State
     */
    protected $previousState;

    /**
     * @var State
     */
    protected $currentState;

    public function getPreviousState(): State
    {
        return $this->previousState;
    }

    public function setPreviousState(State $previousState): State
    {
        return $this->previousState = $previousState;
    }

    public function getCurrentState(): State
    {
        return $this->currentState;
    }

    public function setCurrentState(State $currentState): State
    {
        return $this->currentState = $currentState;
    }
}
