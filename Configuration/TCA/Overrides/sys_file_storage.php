<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_file_storage']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];