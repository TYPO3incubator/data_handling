<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Meta;

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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;

class Action
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var State
     */
    private $sourceState;

    /**
     * @var Context
     */
    private $targetContext;

    /**
     * @var EntityReference
     */
    private $targetNode;

    /**
     * @var array|string|int
     */
    private $payload;

    /**
     * @param string $name
     * @param State $sourceState
     * @param Context $targetContext
     */
    public function __construct(string $name, State $sourceState, Context $targetContext)
    {
        $this->name = $name;
        $this->sourceState = $sourceState;
        $this->targetContext = $targetContext;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return State
     */
    public function getSourceState(): State
    {
        return $this->sourceState;
    }

    /**
     * @return Context
     */
    public function getTargetContext(): Context
    {
        return $this->targetContext;
    }

    public function getTargetNode(): ?EntityReference
    {
        return $this->targetNode;
    }

    /**
     * @param EntityReference $targetNode
     */
    public function setTargetNode(EntityReference $targetNode)
    {
        $this->targetNode = $targetNode;
    }

    /**
     * @return array|int|string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param array|int|string $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }
}
