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

/**
 * @deprecated Not required anymore, switch to Change directly
 */
class Bundle
{
    /**
     * @return Bundle
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Bundle::class);
    }

    /**
     * @var Reference
     */
    protected $reference;

    /**
     * @var Bundle
     */
    protected $baseBundle;

    /**
     * @var array
     */
    protected $values;

    public function __construct()
    {
        $this->reference = Reference::instance();
    }

    public function getReference(): Reference
    {
        return $this->reference;
    }

    public function setReference(Reference $reference): Bundle
    {
        $this->reference = $reference;
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): Bundle
    {
        $this->values = $values;
        return $this;
    }

    public function getValue(string $propertyName)
    {
        return ($this->values[$propertyName] ?? null);
    }

    public function getBaseBundle(): Bundle
    {
        return $this->baseBundle;
    }

    public function setBaseBundle(Bundle $baseBundle): Bundle
    {
        $this->baseBundle = $baseBundle;
        return $this;
    }
}
