<?php
namespace TYPO3\CMS\DataHandling\Tests\Functional\DataHandling\Infrastructure\EventStore\Fixtures;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\StorableEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Instantiable;

/**
 * Event fixture for performance tests.
 */
class EventFixture extends BaseEvent implements StorableEvent, Instantiable
{
    /**
     * @param int $value
     * @return EventFixture
     */
    public static function create(int $value)
    {
        $event = static::instance();
        $event->value = $value;
        return $event;
    }

    /**
     * @return EventFixture
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var int
     */
    private $value;

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function exportData()
    {
        return ['value' => $this->value];
    }

    /**
     * @param array $data
     */
    public function importData($data)
    {
        $this->value = $data['value'];
    }

}
