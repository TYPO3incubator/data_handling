<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_file']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => false,
];