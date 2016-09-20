<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Event;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

class ModifiedEntityEvent extends AbstractEvent implements Instantiable
{
    /**
     * @return ModifiedEntityEvent
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ModifiedEntityEvent::class);
    }

    /**
     * @param Context $context
     * @param EntityReference $aggregateReference
     * @param array $values
     * @return ModifiedEntityEvent
     */
    public static function create(Context $context, EntityReference $aggregateReference, array $values)
    {
        $event = static::instance();
        $event->context = $context;
        $event->aggregateReference = $aggregateReference;
        $event->values = $values;
        return $event;
    }

    /**
     * @var array
     */
    protected $values;

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function exportData()
    {
        $data = parent::exportData();
        $data['values'] = $this->values;
        return $data;
    }

    /**
     * @param array|null $data
     */
    public function importData($data)
    {
        parent::importData($data);
        $this->values = $data['values'];
    }
}
