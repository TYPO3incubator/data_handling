<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['sys_template']['ctrl']['eventSourcing'] = [
    'listenEvents' => true,
    'recordEvents' => true,
    'projectEvents' => true,
];