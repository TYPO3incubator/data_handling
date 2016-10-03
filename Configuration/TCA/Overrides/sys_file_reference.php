<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_file_reference']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];