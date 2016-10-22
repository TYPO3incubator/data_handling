<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_language']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];