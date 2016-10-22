<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['sys_category']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];