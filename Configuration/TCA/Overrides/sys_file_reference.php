<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_file_reference'])) {
    $GLOBALS['TCA']['sys_file_reference']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => true,
    ];
}
