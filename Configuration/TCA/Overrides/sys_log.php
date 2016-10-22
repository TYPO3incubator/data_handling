<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_log']['ctrl']['eventSourcing'] = [
    'listenEvents' => false,
    'recordEvents' => false,
    'projectEvents' => false,
];