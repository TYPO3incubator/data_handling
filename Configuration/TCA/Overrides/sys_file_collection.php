<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_file_collection']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];