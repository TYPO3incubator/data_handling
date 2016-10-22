<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_file_reference']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];