<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_language']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];