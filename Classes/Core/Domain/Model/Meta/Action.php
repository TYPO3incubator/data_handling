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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;

class Action
{
    /**
     * @param string $name
     * @return Action
     */
    public static function create(string $name)
    {
        $action = new static();
        $action->name = $name;
        $action->context = Context::create();
        return $action;
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var EntityReference
     */
    private $node;

    /**
     * @var EntityReference
     */
    private $subject;

    /**
     * @var array|string|int
     */
    private $payload;

    /**
     * @var GenericEntity
     */
    private $state;

    private function __construct()
    {
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    public function getNode(): EntityReference
    {
        return $this->node;
    }

    /**
     * @param EntityReference $node
     */
    public function setNode(EntityReference $node)
    {
        $this->node = $node;
    }

    /**
     * @return EntityReference
     */
    public function getSubject(): EntityReference
    {
        return $this->subject;
    }

    /**
     * @param EntityReference $subject
     */
    public function setSubject(EntityReference $subject)
    {
        $this->subject = $subject;
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

    /**
     * @return GenericEntity
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param GenericEntity $state
     */
    public function setState(GenericEntity $state)
    {
        $this->state = $state;
    }
}
