<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_collection']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];