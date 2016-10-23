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

class SuggestedState extends State
{
    /**
     * @return SuggestedState
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var array
     */
    private $suggestedValues = [];

    /**
     * @param string $propertyName
     * @return int|string|null
     */
    public function getSuggestedValue(string $propertyName)
    {
        return ($this->suggestedValues[$propertyName] ?? null);
    }

    /**
     * @return array
     */
    public function getSuggestedValues()
    {
        return $this->suggestedValues;
    }

    /**
     * @param array $suggestedValues
     * @return static
     */
    public function setSuggestedValues($suggestedValues)
    {
        $this->suggestedValues = $suggestedValues;
        return $this;
    }
}
