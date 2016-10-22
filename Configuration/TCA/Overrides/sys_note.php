<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_note']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];