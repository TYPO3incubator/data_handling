<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_file']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];