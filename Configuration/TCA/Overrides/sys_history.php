<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_history']['ctrl']['eventSourcing'] = [
    'listenEvents' => false,
    'recordEvents' => false,
    'projectEvents' => false,
];