<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_workspace']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];