<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_template']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];