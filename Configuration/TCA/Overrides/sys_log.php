<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_log']['ctrl']['eventSourcing'] = [
    'listenEvents' => false,
    'recordEvents' => false,
    'projectEvents' => false,
];