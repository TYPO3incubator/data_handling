<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Object\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Context;

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
     * @var bool
     */
    protected $new = false;

    /**
     * @var State
     */
    protected $sourceState;

    /**
     * @var State
     */
    protected $targetState;

    public function isNew(): bool
    {
        return $this->new;
    }

    public function setNew(bool $new): Change
    {
        $this->new = $new;
        return $this;
    }

    public function getSourceState(): State
    {
        return $this->sourceState;
    }

    public function setSourceState(State $previousState): Change
    {
        $this->sourceState = $previousState;
        return $this;
    }

    public function getTargetState(): State
    {
        return $this->targetState;
    }

    public function setTargetState(State $currentState): Change
    {
        $this->targetState = $currentState;
        return $this;
    }
}
