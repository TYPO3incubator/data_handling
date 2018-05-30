<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_file_storage'])) {
    $GLOBALS['TCA']['sys_file_storage']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
