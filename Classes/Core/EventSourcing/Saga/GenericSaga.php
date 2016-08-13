<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Saga;

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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\State;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Applicable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\GenericStream;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\StreamProvider;

class GenericSaga
{
    /**
     * @param string $name
     * @return GenericSaga
     */
    public static function create(string $name)
    {
        return GeneralUtility::makeInstance(GenericSaga::class, $name);
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param State $state
     * @param string $epic
     */
    public function tell(State $state, string $epic)
    {
        if (!($state instanceof Applicable)) {
            throw new \RuntimeException('State "' . get_class($state). '" is not applicable', 1471109535);
        }

        $applicableState = array($state, 'apply');

        StreamProvider::create($this->name)
            ->setStore(EventStorePool::provide()->getDefault())
            ->setStream(GenericStream::instance())
            ->subscribe($applicableState)
            ->replay($epic);
    }
}
