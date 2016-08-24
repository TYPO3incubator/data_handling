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

class SqlDriverIterator implements \Iterator, EventTraversable
{
    /**
     * @param Statement $statement
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
        return ($this->event->getEventId() ?? null);
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
        // ignore rewind
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
        if (!is_a($eventClassName, AbstractEvent::class, true)) {
            return $this->invalidate();
        }

        // @todo microsecond part is omitted if fetching from database
        $eventDate = new \DateTime($rawEvent['event_date']);

        $data = $rawEvent['data'];
        $metadata = $rawEvent['metadata'];

        if ($data !== null) {
            $data = json_decode($data, true);
        }
        if ($metadata !== null) {
            $metadata = json_decode($metadata, true);
        }

        $this->event = call_user_func(
            $eventClassName . '::reconstitute',
            $rawEvent['event_name'],
            $rawEvent['event_id'],
            $rawEvent['event_version'],
            $eventDate,
            $data,
            $metadata
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
