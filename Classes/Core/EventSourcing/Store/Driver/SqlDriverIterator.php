<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver;

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

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;

class SqlDriverIterator implements \Iterator
{
    /**
     * @return SqlDriverIterator
     */
    public static function create(Statement $statement)
    {
        return GeneralUtility::makeInstance(SqlDriverIterator::class, $statement);
    }

    /**
     * @var Statement
     */
    protected $statement;

    /**
     * @var AbstractEvent
     */
    protected $event;

    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
        $this->reconstituteNext();
    }

    /**
     * @return null|false|array
     */
    public function current()
    {
        return $this->event;
    }

    /**
     * @return null|string
     */
    public function key()
    {
        return ($this->event->getUuid() ?? null);
    }

    public function next()
    {
        $this->reconstituteNext();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return ($this->event !== null);
    }

    public function rewind()
    {
        throw new \RuntimeException('Cannot rewind iterator', 1471019079);
    }

    /**
     * @return bool
     */
    protected function reconstituteNext()
    {
        $rawEvent = $this->statement->fetch();
        if (!$rawEvent) {
            return $this->invalidate();
        }

        $eventClassName = $rawEvent['event_name'];
        if (is_a($eventClassName, AbstractEvent::class, true)) {
            return $this->invalidate();
        }

        $eventDate = \DateTime::createFromFormat(
            SqlDriver::FORMAT_DATETIME,
            $rawEvent['event_date']
        );

        $this->event = call_user_func(
            $eventClassName . '::reconstitute',
            $rawEvent['event_name'],
            $rawEvent['event_uuid'],
            $eventDate,
            $rawEvent['data'],
            $rawEvent['metadata']
        );

        return true;
    }

    /**
     * @return bool
     */
    protected function invalidate()
    {
        $this->event = null;
        return false;
    }
}
