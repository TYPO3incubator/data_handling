<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_history']['ctrl']['eventSourcing'] = [
    'listenEvents' => false,
    'recordEvents' => false,
    'projectEvents' => false,
];