<?php
defined('TYPO3_MODE') || die();

if (!empty($GLOBALS['TCA']['sys_file_metadata'])) {
    $GLOBALS['TCA']['sys_file_metadata']['ctrl']['eventSourcing'] = [
        'listenEvents' => true,
        'recordEvents' => true,
        'projectEvents' => false,
    ];
}
