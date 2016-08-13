<?php
namespace TYPO3\CMS\DataHandling\Core\Service;

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

class FileSystemService
{
    static $htAccessFileContent = [
        '# Apache < 2.3',
        '<IfModule !mod_authz_core.c>',
        '	Order allow,deny',
        '	Deny from all',
        '   Satisfy All',
        '</IfModule>',
        '# Apache >= 2.3',
        '<IfModule mod_authz_core.c>',
        '	Require all denied',
        '</IfModule>',
    ];

    /**
     * @return FileSystemService
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(FileSystemService::class);
    }

    /**
     * @param string $path
     */
    public function ensureHtAccessDenyFile(string $path)
    {
        $file = rtrim($path, '/') . '/.htaccess';
        if (file_exists($file)) {
            return;
        }
        if (!is_dir($path)) {
            GeneralUtility::mkdir_deep($path);
        }
        GeneralUtility::writeFile($file, implode(PHP_EOL, static::$htAccessFileContent));
    }
}
