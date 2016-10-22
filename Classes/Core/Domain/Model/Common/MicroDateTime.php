<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Common;

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


class MicroDateTime
{
    /**
     * @param string $time
     * @param \DateTimeZone $timeZone
     * @return \DateTime
     */
    public static function create($time = 'now', \DateTimeZone $timeZone = null)
    {
        if ($time === 'now') {
            $microTime = sprintf('%.6f', microtime(true));
            $dateTime = new \DateTime($time);
            $targetDateTime = \DateTime::createFromFormat('U.u', $microTime);
            $targetDateTime->setTimezone($dateTime->getTimezone());
            return $targetDateTime;
        }

        return new \DateTime($time, $timeZone);
    }
}
